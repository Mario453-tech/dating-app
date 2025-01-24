<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GravatarExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gravatar', [$this, 'getGravatarUrl']),
        ];
    }

    public function getGravatarUrl(string $email, int $size = 80): string
    {
        $hash = md5(strtolower(trim($email)));
        return sprintf('https://www.gravatar.com/avatar/%s?s=%d&d=mp', $hash, $size);
    }
}
