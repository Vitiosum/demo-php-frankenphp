<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * APP_ENV sert aussi de variable de démo (pastille d'environnement). Symfony ne connaît que
     * dev, test et prod : « production » ou une valeur inconnue démarrent en prod, sans debug,
     * plutôt que de faire planter l'app. dev retombe aussi sur prod si les paquets de dev ne sont
     * pas installés (Clever Cloud lance composer avec --no-dev).
     */
    public function __construct(string $environment, bool $debug)
    {
        $known = \in_array($environment, ['prod', 'test'], true)
            || ('dev' === $environment && class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class));

        parent::__construct($known ? $environment : 'prod', $known ? $debug : false);
    }
}
