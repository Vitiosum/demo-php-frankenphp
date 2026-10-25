<?php

namespace App\Demo;

/**
 * Variables d'environnement lues par la démo (console Clever Cloud → Variables d'environnement).
 * Chaque valeur absente ou invalide retombe sur un défaut sûr : la page ne casse jamais.
 */
final class DemoSettings
{
    public readonly string $envName;
    public readonly bool $isProduction;
    public readonly string $announcement;
    public readonly int $latencyBudgetMs;
    public readonly int $burstSize;
    /** @var array<string, string|null> variables injectées par Clever Cloud (null en local) */
    public readonly array $platform;
    public readonly bool $live;

    public function __construct()
    {
        $this->envName = self::get('APP_ENV') ?? 'local';
        $this->isProduction = \in_array(strtolower($this->envName), ['prod', 'production'], true);
        $this->announcement = mb_substr(self::get('ANNOUNCEMENT') ?? '', 0, 160);
        $this->latencyBudgetMs = self::int('LATENCY_BUDGET_MS', 10, 1, 10000);
        $this->burstSize = self::int('BURST_SIZE', 40, 5, 200);

        $number = self::get('INSTANCE_NUMBER');
        $pretty = self::get('CC_PRETTY_INSTANCE_NAME');
        $commit = self::get('CC_COMMIT_ID');
        $deployment = self::get('CC_DEPLOYMENT_ID');
        $this->platform = [
            'app' => self::get('CC_APP_NAME'),
            'instance' => null === $number ? null : '#'.$number.(null === $pretty ? '' : ' · '.$pretty),
            'type' => self::get('INSTANCE_TYPE'),
            'commit' => null === $commit ? null : substr($commit, 0, 7),
            'deployment' => null === $deployment ? null : substr($deployment, 0, 16),
        ];
        $this->live = null !== self::get('APP_ID');
    }

    private static function get(string $key): ?string
    {
        // Environnement du processus d'abord (console Clever Cloud) : Dotenv ne doit pas masquer
        // une variable réelle sous php -S, où $_SERVER ne contient pas l'environnement.
        $value = getenv($key);
        if (false === $value) {
            $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        }
        if (!\is_string($value)) {
            return null;
        }
        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private static function int(string $key, int $default, int $min, int $max): int
    {
        $value = filter_var(self::get($key), \FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return false === $value ? $default : $value;
    }
}
