<?php

declare(strict_types=1);

namespace App\Service;

use Greenter\Api;

class SeeApiFactory
{
    private ConfigProviderInterface $config;
    private ConfigProviderInterface $fileProvider;
    private FileDataReader $fileReader;
    private Api $see;

    /**
     * SeeFactory constructor.
     * @param ConfigProviderInterface $config
     * @param ConfigProviderInterface $fileProvider
     * @param FileDataReader $fileReader
     * @param Api $see
     */
    public function __construct(ConfigProviderInterface $config, ConfigProviderInterface $fileProvider, FileDataReader $fileReader, Api $see)
    {
        $this->config = $config;
        $this->fileProvider = $fileProvider;
        $this->fileReader = $fileReader;
        $this->see = $see;
    }

    public function build(?string $ruc): Api
    {
        if (!empty($ruc) && $this->configureSeeWithRuc($ruc)) {
            return $this->see;
        }

        $this->configureSeeWithEnv();
        return $this->see;
    }

    private function configureSeeWithRuc(string $ruc): bool
    {
        $jsonCompanies = $this->fileProvider->get('companies');
        if (empty($jsonCompanies)) {
            return false;
        }

        $companies = json_decode($jsonCompanies, true);

        if (!array_key_exists($ruc, $companies)) {
            return false;
        }

        $config = $companies[$ruc];
        list ($ruc, $user) = $this->getRucAndUser($config['SOL_USER']);
        $this->see->setClaveSOL($ruc, $user, $config['SOL_PASS']);
        $this->see->setCertificate($this->fileReader->getContents($config['certificate']));
        $this->see->setApiCredentials($config['CLIENT_ID'], $config['CLIENT_SECRET']);

        return true;
    }

    private function configureSeeWithEnv()
    {
        list ($ruc, $user) = $this->getRucAndUser($this->config->get('SOL_USER'));
        $this->see->setClaveSOL($ruc, $user, $this->config->get('SOL_PASS'));
        $this->see->setApiCredentials($this->config->get('CLIENT_ID'), $this->config->get('CLIENT_SECRET'));
        $this->see->setCertificate($this->fileProvider->get('certificate'));
    }

    private function getRucAndUser(string $username): array
    {
        $ruc = substr($username, 0, 11);
        $user = substr($username, 11);

        return [$ruc, $user];
    }
}
