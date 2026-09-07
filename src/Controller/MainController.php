<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route("/", name: "homepage")]
    public function index(): Response
    {
        return $this->render('homepage/index.html.twig', [
            'cc' => $this->platform(),
        ]);
    }

    /**
     * Sonde de vie pour Clever Cloud (CC_HEALTH_CHECK_PATH=/health) : 200 sans toucher la base.
     */
    #[Route("/health", name: "health", methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Rapports k6 committés dans benchmark/ (nom strictement contraint par la route).
     */
    #[Route("/benchmark/{name}", name: "benchmark_report", requirements: ['name' => 'summary-(10|100)-vus-(fpm|no-worker|worker)'])]
    public function benchmarkReport(string $name): BinaryFileResponse
    {
        $response = new BinaryFileResponse(__DIR__.'/../../benchmark/'.$name.'.html');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }

    #[Route("/download-logo", name: "download_logo")]
    public function downloadLogo(): BinaryFileResponse
    {
        $response = new BinaryFileResponse(__DIR__.'/../../private-files/frankenphp.png');
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    /**
     * Variables injectées par Clever Cloud. En local, APP_ID est absent : le panneau affiche « hors Clever Cloud ».
     */
    private function platform(): array
    {
        $g = static fn (string $k): ?string => ($v = getenv($k)) !== false && $v !== '' ? $v : ($_SERVER[$k] ?? null);
        $cut = static fn (?string $v, int $n): string => $v === null ? '—' : mb_substr($v, 0, $n);
        $instance = $g('INSTANCE_NUMBER') !== null
            ? '#'.$g('INSTANCE_NUMBER').($g('CC_PRETTY_INSTANCE_NAME') ? ' · '.$g('CC_PRETTY_INSTANCE_NAME') : '')
            : '—';

        return [
            'live' => $g('APP_ID') !== null,
            'appName' => $g('CC_APP_NAME') ?? '—',
            'appId' => $g('APP_ID') ?? '—',
            'instance' => $instance,
            'instanceType' => $g('INSTANCE_TYPE') ?? '—',
            'commit' => $cut($g('CC_COMMIT_ID'), 7),
            'deployment' => $cut($g('CC_DEPLOYMENT_ID'), 16),
            'php' => PHP_VERSION,
            'worker' => (bool) ($_SERVER['FRANKENPHP_WORKER'] ?? false),
        ];
    }
}
