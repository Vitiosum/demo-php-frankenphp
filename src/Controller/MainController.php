<?php

namespace App\Controller;

use App\Demo\WorkerStats;
use App\Repository\MonsterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(MonsterRepository $monsters): Response
    {
        $response = $this->render('homepage/index.html.twig', [
            'w' => WorkerStats::snapshot(),
            'monsters' => $monsters->findBy([], ['id' => 'ASC'], 8),
            'monsterCount' => $monsters->count([]),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * État du worker qui sert cette requête : la rafale de l'accueil appelle cette route N fois.
     */
    #[Route('/worker', name: 'worker', methods: ['GET'])]
    public function worker(): JsonResponse
    {
        $response = new JsonResponse(WorkerStats::snapshot());
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * Sonde de vie pour Clever Cloud (CC_HEALTH_CHECK_PATH=/health) : 200 sans toucher la base.
     */
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Rapports k6 committés dans benchmark/ (nom strictement contraint par la route).
     */
    #[Route('/benchmark/{name}', name: 'benchmark_report', requirements: ['name' => 'summary-(10|100)-vus-(fpm|no-worker|worker)'], methods: ['GET'])]
    public function benchmarkReport(string $name): BinaryFileResponse
    {
        $response = new BinaryFileResponse(__DIR__.'/../../benchmark/'.$name.'.html');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }

    #[Route('/download-logo', name: 'download_logo', methods: ['GET'])]
    public function downloadLogo(): BinaryFileResponse
    {
        $response = new BinaryFileResponse(__DIR__.'/../../private-files/frankenphp.png');
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }
}
