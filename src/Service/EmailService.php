<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $mailerFromAddress = 'no-reply@example.com'
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->mailerFromAddress)
            ->to($user->getEmail())
            ->subject('Witaj w Miłość w sieci!')
            ->html($this->getWelcomeEmailTemplate($user->getUsername(), $loginUrl));

        $this->mailer->send($email);
    }

    private function getWelcomeEmailTemplate(string $username, string $loginUrl): string
    {
        return <<<HTML
            <h1>Witaj {$username}!</h1>
            <p>Dziękujemy za dołączenie do Miłość w sieci!</p>
            <p>Twoje konto zostało pomyślnie utworzone. Możesz teraz zalogować się i rozpocząć swoją przygodę:</p>
            <p><a href="{$loginUrl}" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Zaloguj się</a></p>
            <p>Życzymy powodzenia w znalezieniu swojej drugiej połówki!</p>
            <p>Zespół Miłość w sieci</p>
        HTML;
    }

    public function sendPasswordResetEmail(string $toEmail, string $resetToken): void
    {
        $resetUrl = $this->urlGenerator->generate('app_reset_password', [
            'token' => $resetToken
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->mailerFromAddress)
            ->to($toEmail)
            ->subject('Reset hasła')
            ->html(
                $this->getPasswordResetEmailTemplate($resetUrl)
            );

        $this->mailer->send($email);
    }

    private function getPasswordResetEmailTemplate(string $resetUrl): string
    {
        return <<<HTML
            <h1>Reset hasła</h1>
            <p>Otrzymaliśmy prośbę o reset hasła do Twojego konta.</p>
            <p>Aby zresetować hasło, kliknij w poniższy link:</p>
            <p><a href="{$resetUrl}">Resetuj hasło</a></p>
            <p>Link jest ważny przez 1 godzinę.</p>
            <p>Jeśli nie prosiłeś/aś o reset hasła, zignoruj tę wiadomość.</p>
        HTML;
    }

    public function sendNewAccountEmail(string $toEmail, string $password, bool $forcePasswordChange): void
    {
        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $email = (new Email())
            ->from($this->mailerFromAddress)
            ->to($toEmail)
            ->subject('Twoje konto zostało utworzone')
            ->html($this->getNewAccountEmailTemplate($toEmail, $password, $forcePasswordChange));

        $this->mailer->send($email);
    }

    private function getNewAccountEmailTemplate(string $email, string $password, bool $forcePasswordChange): string
    {
        return <<<HTML
            <h1>Twoje konto zostało utworzone</h1>
            <p>Witaj!</p>
            <p>Twoje konto zostało utworzone w systemie. Poniżej znajdziesz dane do logowania:</p>
            <ul>
                <li><strong>Email:</strong> {$email}</li>
                <li><strong>Hasło:</strong> {$password}</li>
            </ul>
            <p><a href="{$this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL)}">Kliknij tutaj aby się zalogować</a></p>
            
            {$this->getPasswordChangeInfo($forcePasswordChange)}
            
            <p>Pozdrawiamy,<br>Zespół wsparcia</p>
        HTML;
    }

    private function getPasswordChangeInfo(bool $forcePasswordChange): string
    {
        if ($forcePasswordChange) {
            return '<p><strong>Uwaga:</strong> Przy pierwszym logowaniu będziesz musiał zmienić swoje hasło.</p>';
        }
        return '<p>Ze względów bezpieczeństwa zalecamy zmianę hasła po pierwszym logowaniu.</p>';
    }
}
