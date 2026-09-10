<?php

declare(strict_types=1);

namespace App\Service;

use Greenter\Api;

class SeeApiFactory
{
    private ConfigProviderInterface $config;
    private ConfigProviderInterface $fileProvider;
    private CompanyConfigResolver $resolver;
    private Api $see;
    private string $cacheDir;

    /**
     * SeeApiFactory constructor.
     * @param ConfigProviderInterface $config
     * @param ConfigProviderInterface $fileProvider
     * @param CompanyConfigResolver $resolver
     * @param Api $see
     * @param string $cacheDir
     */
    public function __construct(ConfigProviderInterface $config, ConfigProviderInterface $fileProvider, CompanyConfigResolver $resolver, Api $see, string $cacheDir)
    {
        $this->config = $config;
        $this->fileProvider = $fileProvider;
        $this->resolver = $resolver;
        $this->see = $see;
        $this->cacheDir = $cacheDir;
    }

    public function build(?string $ruc): Api
    {
        $company = !empty($ruc) ? $this->resolver->getCompany($ruc) : null;
        if ($company !== null) {
            return $this->buildForCompany($company);
        }

        $this->configureSeeWithEnv();
        return $this->see;
    }

    /**
     * Companies may override AUTH_URL/API_URL (per environment), and Greenter\Api only
     * accepts endpoints at construction time, so a dedicated instance is built.
     */
    private function buildForCompany(array $company): Api
    {
        $api = new Api([
            'auth' => (string) $this->resolver->resolve($company, 'AUTH_URL'),
            'cpe' => (string) $this->resolver->resolve($company, 'API_URL'),
        ]);
        $api->setBuilderOptions(['cache' => $this->cacheDir]);

        list ($ruc, $user) = $this->getRucAndUser((string) $this->resolver->resolve($company, 'SOL_USER'));
        $api->setClaveSOL($ruc, $user, (string) $this->resolver->resolve($company, 'SOL_PASS'));
        $api->setCertificate($this->resolver->resolveCertificate($company));
        $api->setApiCredentials(
            (string) $this->resolver->resolve($company, 'CLIENT_ID'),
            (string) $this->resolver->resolve($company, 'CLIENT_SECRET')
        );

        return $api;
    }

    private function configureSeeWithEnv()
    {
        list ($ruc, $user) = $this->getRucAndUser((string) $this->config->get('SOL_USER'));
        $this->see->setClaveSOL($ruc, $user, (string) $this->config->get('SOL_PASS'));
        $this->see->setApiCredentials((string) $this->config->get('CLIENT_ID'), (string) $this->config->get('CLIENT_SECRET'));
        $this->see->setCertificate((string) $this->fileProvider->get('certificate'));
    }

    private function getRucAndUser(string $username): array
    {
        $ruc = substr($username, 0, 11);
        $user = substr($username, 11);

        return [$ruc, $user];
    }
}
