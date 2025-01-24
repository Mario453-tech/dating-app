<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Autowired;

#[Route('/admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        #[Autowired(service: 'monolog.logger.admin')]
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ) {
    }

    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request ? $request->getClientIp() : 'unknown';
        
        $this->logger->info('Dostęp do panelu administracyjnego', [
            'user' => $this->getUser()?->getUserIdentifier(),
            'ip' => $ip
        ]);

        return $this->redirect($this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Panel Administracyjny')
            ->setFaviconPath('favicon.svg')
            ->setTranslationDomain('admin')
            ->setTextDirection('ltr')
            ->generateRelativeUrls();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Użytkownicy');
        yield MenuItem::linkToCrud('Zarządzaj użytkownikami', 'fa fa-user', User::class)
            ->setDefaultSort(['createdAt' => 'DESC']);
        
        yield MenuItem::section('System');
        yield MenuItem::linkToRoute('Powrót do strony głównej', 'fa fa-arrow-left', 'app_home');
    }

    public function getCustomTemplate(string $templateName): ?string
    {
        return match ($templateName) {
            'layout' => 'admin/layout.html.twig',
            default => null,
        };
    }
}
