<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class LogController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/log', name: 'app_log', methods: ['POST'])]
    public function log(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $message = $data['message'] ?? 'No message provided';
        $level = $data['level'] ?? 'info';
        $context = $data['context'] ?? [];

        // Dodaj informacje o użytkowniku do kontekstu
        if ($this->getUser()) {
            $context['user_id'] = $this->getUser()->getId();
            $context['username'] = $this->getUser()->getUsername();
        }

        // Dodaj informacje o żądaniu
        $context['request'] = [
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('referer'),
        ];

        switch ($level) {
            case 'error':
                $this->logger->error($message, $context);
                break;
            case 'warning':
                $this->logger->warning($message, $context);
                break;
            case 'debug':
                $this->logger->debug($message, $context);
                break;
            default:
                $this->logger->info($message, $context);
        }

        return new JsonResponse(['status' => 'logged']);
    }
}
