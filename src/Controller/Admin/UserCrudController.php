<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\EmailService;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Autowired;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowired(service: 'monolog.logger.admin')]
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailService $emailService,
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', '<i class="fas fa-users"></i> Użytkownicy')
            ->setPageTitle('new', '<i class="fas fa-user-plus"></i> Dodaj użytkownika')
            ->setPageTitle('edit', '<i class="fas fa-user-edit"></i> Edytuj użytkownika')
            ->setPageTitle('detail', '<i class="fas fa-user"></i> Szczegóły użytkownika')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(20)
            ->setEntityLabelInSingular('Użytkownik')
            ->setEntityLabelInPlural('Użytkownicy')
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureActions(Actions $actions): Actions
    {
        $banFor1Hour = Action::new('banFor1Hour', 'Ban na 1 godzinę')
            ->linkToCrudAction('banUser')
            ->addCssClass('btn btn-danger')
            ->setIcon('fa fa-ban')
            ->setHtmlAttributes(['data-duration' => 'PT1H']);

        $banFor1Day = Action::new('banFor1Day', 'Ban na 1 dzień')
            ->linkToCrudAction('banUser')
            ->addCssClass('btn btn-danger')
            ->setIcon('fa fa-ban')
            ->setHtmlAttributes(['data-duration' => 'P1D']);

        $banFor7Days = Action::new('banFor7Days', 'Ban na 7 dni')
            ->linkToCrudAction('banUser')
            ->addCssClass('btn btn-danger')
            ->setIcon('fa fa-ban')
            ->setHtmlAttributes(['data-duration' => 'P7D']);

        $banPermanent = Action::new('banPermanent', 'Ban permanentny')
            ->linkToCrudAction('banUser')
            ->addCssClass('btn btn-danger')
            ->setIcon('fa fa-ban')
            ->setHtmlAttributes(['data-duration' => 'P99Y']);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel('Dodaj użytkownika');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit')->setLabel('Edytuj');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel('Usuń');
            })
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel('Szczegóły');
            })
            ->add(Crud::PAGE_INDEX, $banFor1Hour)
            ->add(Crud::PAGE_INDEX, $banFor1Day)
            ->add(Crud::PAGE_INDEX, $banFor7Days)
            ->add(Crud::PAGE_INDEX, $banPermanent);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('email')
            ->setColumns(6)
            ->setHelp('Wprowadź poprawny adres email');
            
        yield TextField::new('username')
            ->setColumns(6)
            ->setHelp('Wprowadź nazwę użytkownika');
            
        yield TextField::new('firstName', 'Imię')
            ->setColumns(6)
            ->setHelp('Wprowadź imię użytkownika');
            
        yield TextField::new('lastName', 'Nazwisko')
            ->setColumns(6)
            ->setHelp('Wprowadź nazwisko użytkownika');

        yield DateField::new('birthDate', 'Data urodzenia')
            ->setColumns(6)
            ->setHelp('Wprowadź datę urodzenia użytkownika');

        yield ChoiceField::new('gender', 'Płeć')
            ->setColumns(6)
            ->setChoices([
                'Mężczyzna' => 'male',
                'Kobieta' => 'female',
                'Inne' => 'other'
            ])
            ->setHelp('Wybierz płeć użytkownika');

        yield BooleanField::new('isActive', 'Aktywny')
            ->renderAsSwitch(true)
            ->setColumns(6)
            ->setHelp('Zaznacz, jeśli użytkownik jest aktywny');

        yield TextField::new('lastIpAddress', 'Ostatnie IP')
            ->hideOnForm()
            ->setColumns(6)
            ->setHelp('Ostatnie IP użytkownika');

        yield BooleanField::new('isBanned', 'Zbanowany')
            ->renderAsSwitch(true)
            ->setColumns(6)
            ->setHelp('Zaznacz, jeśli użytkownik jest zbanowany');

        yield DateTimeField::new('bannedUntil', 'Zbanowany do')
            ->setRequired(false)
            ->setColumns(6)
            ->setHelp('Data, do której użytkownik jest zbanowany');

        if ($pageName === Crud::PAGE_NEW) {
            yield TextField::new('plainPassword', 'Hasło')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Hasło'],
                    'second_options' => ['label' => 'Powtórz hasło'],
                    'mapped' => false,
                ])
                ->setColumns(6)
                ->setHelp('Wprowadź hasło dla użytkownika');

            yield BooleanField::new('forcePasswordChange', 'Wymuś zmianę hasła')
                ->setColumns(6)
                ->setHelp('Zaznacz, jeśli użytkownik ma zmienić hasło przy pierwszym logowaniu');
        }
            
        yield ChoiceField::new('roles', 'Role')
            ->setColumns(6)
            ->setChoices([
                'Administrator' => 'ROLE_ADMIN',
                'Moderator' => 'ROLE_MODERATOR',
                'Użytkownik' => 'ROLE_USER'
            ])
            ->allowMultipleChoices()
            ->renderAsBadges()
            ->setHelp('Wybierz role użytkownika');
            
        yield DateTimeField::new('createdAt', 'Data utworzenia')
            ->hideOnForm()
            ->setColumns(6)
            ->setFormat('d.m.Y H:i:s');
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->logger->info('Usunięto użytkownika', [
            'admin_user' => $this->getUser()?->getUserIdentifier(),
            'deleted_user' => $entityInstance->getUserIdentifier(),
            'ip' => $this->container->get('request_stack')->getCurrentRequest()->getClientIp()
        ]);

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;

        // Jeśli użytkownik jest odbanowany, wyczyść datę banu
        if (!$user->isBanned()) {
            $user->setBannedUntil(null);
        }

        $this->logger->info('Zaktualizowano użytkownika', [
            'admin_user' => $this->getUser()?->getUserIdentifier(),
            'updated_user' => $entityInstance->getUserIdentifier(),
            'ip' => $this->container->get('request_stack')->getCurrentRequest()->getClientIp()
        ]);

        parent::updateEntity($entityManager, $user);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;

        // Ustaw username na podstawie emaila jeśli nie został podany
        if (empty($user->getUsername())) {
            $email = $user->getEmail();
            $username = explode('@', $email)[0]; // bierzemy część przed @
            $user->setUsername($username);
        }

        // Generuj losowe hasło jeśli nie zostało podane
        if (empty($user->getPlainPassword())) {
            $plainPassword = bin2hex(random_bytes(8));
            $user->setPlainPassword($plainPassword);
        }

        // Hashuj hasło
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );
        $user->setPassword($hashedPassword);

        // Ustaw datę utworzenia
        if (!$user->getCreatedAt()) {
            $user->setCreatedAt(new \DateTime());
        }

        // Zapisz użytkownika
        parent::persistEntity($entityManager, $user);

        // Wyślij email z danymi do logowania
        try {
            $this->emailService->sendNewAccountEmail(
                $user->getEmail(),
                $user->getPlainPassword(),
                $user->isForcePasswordChange()
            );

            $this->addFlash('success', 'Użytkownik został utworzony');
            $this->logger->info('Wysłano email z danymi do logowania', [
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Błąd podczas wysyłania emaila z danymi do logowania');
            $this->logger->error('Błąd podczas wysyłania emaila z danymi do logowania', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function banUser(AdminContext $context): Response
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        
        try {
            $bannedUntil = new \DateTime('+1 hour');
            $user->setIsBanned(true);
            $user->setBannedUntil($bannedUntil);
            
            $this->entityManager->flush();
            
            $message = sprintf('Użytkownik został zbanowany do %s', $bannedUntil->format('Y-m-d H:i:s'));
            $_SESSION['flash_messages']['success'][] = $message;
            
            $this->logger->info('Użytkownik został zbanowany', [
                'user_id' => $user->getId(),
                'banned_until' => $bannedUntil->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $_SESSION['flash_messages']['danger'][] = 'Wystąpił błąd podczas banowania użytkownika';
            
            $this->logger->error('Błąd podczas banowania użytkownika', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
        }
        
        $url = $this->container->get('router')->generate('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class
        ]);
        
        return $this->redirect($url);
    }
}
