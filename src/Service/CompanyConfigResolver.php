<?php

declare(strict_types=1);

namespace App\Service;

use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Perception\Perception;
use Greenter\Model\Retention\Retention;
use Greenter\Model\Voided\Reversion;
use Greenter\Ws\Services\SunatEndpoints;

/**
 * Resolves per-company configuration stored in data/empresas.json, applying
 * the environment inheritance chain:
 *
 *  1. environments[<active environment>][key]
 *  2. company root [key]
 *  3. SUNAT official endpoints (only FE_URL/RE_URL/GUIA_URL and only when the
 *     active environment is one of the reserved names "beta" / "production")
 *  4. .env (only for keys listed in ENV_KEYS)
 */
class CompanyConfigResolver
{
    public const ENV_BETA = 'beta';
    public const ENV_PRODUCTION = 'production';
    public const PRESET_ENVIRONMENTS = [self::ENV_BETA, self::ENV_PRODUCTION];

    /**
     * Keys that fall back to the .env configuration.
     */
    public const ENV_KEYS = ['SOL_USER', 'SOL_PASS', 'FE_URL', 'RE_URL', 'GUIA_URL', 'AUTH_URL', 'API_URL', 'CLIENT_ID', 'CLIENT_SECRET'];

    /**
     * Keys accepted inside an environment block.
     */
    public const ENVIRONMENT_KEYS = ['SOL_USER', 'SOL_PASS', 'certificate', 'FE_URL', 'RE_URL', 'GUIA_URL', 'AUTH_URL', 'API_URL', 'CLIENT_ID', 'CLIENT_SECRET'];

    private const SUNAT_PRESETS = [
        self::ENV_BETA => [
            'FE_URL' => SunatEndpoints::FE_BETA,
            'RE_URL' => SunatEndpoints::RETENCION_BETA,
            'GUIA_URL' => SunatEndpoints::GUIA_BETA,
        ],
        self::ENV_PRODUCTION => [
            'FE_URL' => SunatEndpoints::FE_PRODUCCION,
            'RE_URL' => SunatEndpoints::RETENCION_PRODUCCION,
            'GUIA_URL' => SunatEndpoints::GUIA_PRODUCCION,
        ],
    ];

    private ConfigProviderInterface $fileProvider;
    private ConfigProviderInterface $envProvider;
    private FileDataReader $fileReader;

    public function __construct(ConfigProviderInterface $fileProvider, ConfigProviderInterface $envProvider, FileDataReader $fileReader)
    {
        $this->fileProvider = $fileProvider;
        $this->envProvider = $envProvider;
        $this->fileReader = $fileReader;
    }

    /**
     * Decoded content of empresas.json, or an empty array when missing/invalid.
     */
    public function getCompanies(): array
    {
        $json = $this->fileProvider->get('companies');
        if (empty($json)) {
            return [];
        }

        $companies = json_decode($json, true);

        return is_array($companies) ? $companies : [];
    }

    public function getCompany(string $ruc): ?array
    {
        $companies = $this->getCompanies();
        if (!array_key_exists($ruc, $companies) || !is_array($companies[$ruc])) {
            return null;
        }

        return $companies[$ruc];
    }

    public function getActiveEnvironment(array $company): ?string
    {
        $name = $company['environment'] ?? null;

        return (is_string($name) && $name !== '') ? $name : null;
    }

    public function getActiveEnvironmentConfig(array $company): array
    {
        $name = $this->getActiveEnvironment($company);
        if ($name === null) {
            return [];
        }

        $block = $company['environments'][$name] ?? null;

        return is_array($block) ? $block : [];
    }

    /**
     * @return string[]
     */
    public function getEnvironmentNames(array $company): array
    {
        $environments = $company['environments'] ?? null;
        if (!is_array($environments)) {
            return [];
        }

        return array_values(array_map('strval', array_keys($environments)));
    }

    public static function isPresetEnvironment(string $name): bool
    {
        return in_array($name, self::PRESET_ENVIRONMENTS, true);
    }

    public function resolve(array $company, string $key): ?string
    {
        $value = $this->pick($this->getActiveEnvironmentConfig($company), $key);
        if ($value !== null) {
            return $value;
        }

        $value = $this->pick($company, $key);
        if ($value !== null) {
            return $value;
        }

        $active = $this->getActiveEnvironment($company);
        if ($active !== null && isset(self::SUNAT_PRESETS[$active][$key])) {
            return self::SUNAT_PRESETS[$active][$key];
        }

        if (in_array($key, self::ENV_KEYS, true)) {
            $env = $this->envProvider->get($key);
            if ($env !== false && $env !== null && $env !== '') {
                return (string) $env;
            }
        }

        return null;
    }

    /**
     * Contents of the resolved certificate file; falls back to the global data/cert.pem.
     */
    public function resolveCertificate(array $company): string
    {
        $filename = $this->resolve($company, 'certificate');
        if ($filename !== null) {
            return (string) $this->fileReader->getContents($filename);
        }

        return (string) $this->fileProvider->get('certificate');
    }

    /**
     * Configuration key holding the SUNAT/OSE service URL for a document class.
     */
    public static function urlKeyForClass(string $class): string
    {
        switch ($class) {
            case Perception::class:
            case Retention::class:
            case Reversion::class:
                return 'RE_URL';
            case Despatch::class:
                return 'GUIA_URL';
        }

        return 'FE_URL';
    }

    private function pick(array $source, string $key): ?string
    {
        if (!isset($source[$key]) || !is_scalar($source[$key])) {
            return null;
        }

        $value = (string) $source[$key];

        return $value === '' ? null : $value;
    }
}
