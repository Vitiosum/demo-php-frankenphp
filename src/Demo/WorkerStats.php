<?php

namespace App\Demo;

/**
 * État du worker PHP qui sert la requête.
 *
 * Les propriétés statiques vivent aussi longtemps que le script PHP : en mode worker FrankenPHP,
 * elles survivent d'une requête à l'autre (chaque thread worker a les siennes) ; en mode classique
 * (php -S, PHP-FPM, FrankenPHP sans worker), elles repartent de zéro à chaque requête.
 */
final class WorkerStats
{
    private static ?string $id = null;
    private static float $startedAt = 0.0;
    private static int $requests = 0;

    /** Appelé une fois par requête principale (WorkerRequestSubscriber). */
    public static function tick(): void
    {
        if (null === self::$id) {
            self::$id = bin2hex(random_bytes(3));
            self::$startedAt = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        }
        ++self::$requests;
    }

    public static function isWorker(): bool
    {
        return (bool) ($_SERVER['FRANKENPHP_WORKER'] ?? false) && \function_exists('frankenphp_handle_request');
    }

    /** Même règle que symfony/runtime : entier attendu, 0 ou négatif = jamais recyclé, défaut 500. */
    public static function loopMax(): int
    {
        $raw = $_SERVER['FRANKENPHP_LOOP_MAX'] ?? $_ENV['FRANKENPHP_LOOP_MAX'] ?? getenv('FRANKENPHP_LOOP_MAX');
        $raw = \is_string($raw) || \is_int($raw) ? trim((string) $raw) : false;
        $value = false === $raw || '' === $raw ? null : filter_var($raw, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE);

        return $value ?? 500;
    }

    /** Millisecondes écoulées depuis l'arrivée de la requête (inclut le boot de Symfony hors worker). */
    public static function elapsedMs(): float
    {
        $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

        return round(max(0.0, microtime(true) - $start) * 1000, 2);
    }

    /** « 0,4 s », « 42 s », « 12 min », « 2 h 05 » */
    public static function duration(float $seconds): string
    {
        return match (true) {
            $seconds < 10 => number_format($seconds, 1, ',', ' ').' s',
            $seconds < 60 => (int) $seconds.' s',
            $seconds < 3600 => intdiv((int) $seconds, 60).' min',
            default => intdiv((int) $seconds, 3600).' h '.str_pad((string) intdiv((int) $seconds % 3600, 60), 2, '0', \STR_PAD_LEFT),
        };
    }

    /** @return array<string, mixed> */
    public static function snapshot(): array
    {
        if (null === self::$id) {
            self::tick();
        }
        $worker = self::isWorker();
        $loopMax = self::loopMax();

        return [
            'mode' => $worker ? 'worker' : 'classic',
            'server' => match (true) {
                $worker => 'FrankenPHP · worker',
                'frankenphp' === \PHP_SAPI => 'FrankenPHP · classique',
                'cli-server' === \PHP_SAPI => 'php -S · serveur de dev',
                default => \PHP_SAPI,
            },
            'worker' => self::$id,
            'pid' => getmypid(),
            'request' => self::$requests,
            'loop_max' => $loopMax,
            'recycle_in' => $worker && $loopMax > 0 ? max(0, $loopMax - self::$requests) : null,
            'uptime_s' => $uptime = round(max(0.0, microtime(true) - self::$startedAt), 1),
            'uptime' => self::duration($uptime),
            'memory_mb' => round(memory_get_usage() / 1048576, 1),
            'server_ms' => self::elapsedMs(),
            'php' => \PHP_VERSION,
        ];
    }
}
