<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GravatarService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getProfileImage(string $email, int $size = 200): string
    {
        $hash = md5(strtolower(trim($email)));
        return sprintf('https://www.gravatar.com/avatar/%s?s=%d&d=mp', $hash, $size);
    }
}
