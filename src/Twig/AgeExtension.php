<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AgeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('age', [$this, 'calculateAge']),
        ];
    }

    public function calculateAge(string $birthDate): int
    {
        $birth = new \DateTime($birthDate);
        $now = new \DateTime();
        return $birth->diff($now)->y;
    }
}
