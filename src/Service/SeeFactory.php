<?php

namespace App\Service;

use Greenter\See;

class SeeFactory
{
    /**
     * @var ConfigProviderInterface
     */
    private $config;

    /**
     * @var ConfigProviderInterface
     */
    private $fileProvider;

    /**
     * @var CompanyConfigResolver
     */
    private $resolver;

    /**
     * @var See
     */
    private $see;

    /**
     * SeeFactory constructor.
     * @param ConfigProviderInterface $config
     * @param ConfigProviderInterface $fileProvider
     * @param CompanyConfigResolver $resolver
     * @param See $see
     */
    public function __construct(ConfigProviderInterface $config, ConfigProviderInterface $fileProvider, CompanyConfigResolver $resolver, See $see)
    {
        $this->config = $config;
        $this->fileProvider = $fileProvider;
        $this->resolver = $resolver;
        $this->see = $see;
    }

    public function build(string $class, ?string $ruc): See
    {
        if (!empty($ruc) && $this->configureSeeWithRuc($ruc, $class)) {
            return $this->see;
        }

        $this->configureSeeWithEnv($class);
        return $this->see;
    }

    private function configureSeeWithRuc(string $ruc, string $class): bool
    {
        $company = $this->resolver->getCompany($ruc);
        if ($company === null) {
            return false;
        }

        $this->see->setCredentials(
            (string) $this->resolver->resolve($company, 'SOL_USER'),
            (string) $this->resolver->resolve($company, 'SOL_PASS')
        );
        $this->see->setCertificate($this->resolver->resolveCertificate($company));
        $this->see->setService($this->resolver->resolve($company, CompanyConfigResolver::urlKeyForClass($class)));

        return true;
    }

    private function configureSeeWithEnv(string $class)
    {
        $this->see->setCredentials($this->config->get('SOL_USER'), $this->config->get('SOL_PASS'));
        $this->see->setCertificate($this->fileProvider->get('certificate'));
        $this->see->setService($this->config->get(CompanyConfigResolver::urlKeyForClass($class)));
    }
}
