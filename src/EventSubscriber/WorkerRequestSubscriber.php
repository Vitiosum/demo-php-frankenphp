<?php

namespace App\EventSubscriber;

use App\Demo\WorkerStats;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Compte les requêtes servies par le worker courant et expose le temps serveur (Server-Timing).
 */
final class WorkerRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 4096],
            KernelEvents::RESPONSE => ['onResponse', -4096],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            WorkerStats::tick();
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getResponse()->headers->set('Server-Timing', \sprintf('app;dur=%.2f;desc="PHP"', WorkerStats::elapsedMs()));
        }
    }
}
