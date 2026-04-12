<?php

namespace App\Service;

use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;

class ConsultCdrServiceFactory
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
     * ConsultCdrServiceFactory constructor.
     * @param ConfigProviderInterface $config
     * @param ConfigProviderInterface $fileProvider
     */
    public function __construct(ConfigProviderInterface $config, ConfigProviderInterface $fileProvider)
    {
        $this->config = $config;
        $this->fileProvider = $fileProvider;
    }

    /**
     * Build a configured ConsultCdrService for the given RUC (or the env defaults).
     *
     * @param string|null $ruc
     * @return ConsultCdrService
     */
    public function build(?string $ruc): ConsultCdrService
    {
        $ws = new SoapClient(SunatEndpoints::FE_CONSULTA_CDR . '?wsdl');

        $user = $this->getConfig('SOL_USER', $ruc);
        $pass = $this->getConfig('SOL_PASS', $ruc);

        // Si no se encontró en companies (o no se proporcionó RUC), usar credenciales de entorno
        if ($user === false || $user === null) {
            $user = $this->config->get('SOL_USER');
            $pass = $this->config->get('SOL_PASS');
        }

        $ws->setCredentials($user, $pass);

        $service = new ConsultCdrService();
        $service->setClient($ws);

        return $service;
    }

    /**
     * Return the full SOL_USER credential string for the given RUC (or the env default).
     * The caller can extract the issuer RUC with substr($user, 0, 11).
     *
     * @param string|null $ruc
     * @return string
     */
    public function getCredentialUser(?string $ruc): string
    {
        $user = $this->getConfig('SOL_USER', $ruc);

        // Si no se encontró en companies (o no se proporcionó RUC), usar credencial de entorno
        if ($user === false || $user === null) {
            $user = $this->config->get('SOL_USER');
        }

        return (string) $user;
    }

    /**
     * @param string      $key
     * @param string|null $ruc
     * @return mixed
     */
    private function getConfig(string $key, ?string $ruc = null)
    {
        if (empty($ruc)) {
            return $this->config->get($key);
        }

        $jsonCompanies = $this->fileProvider->get('companies');
        if (empty($jsonCompanies)) {
            return false;
        }

        $companies = json_decode($jsonCompanies, true);
        if (!is_array($companies)) {
            return false;
        }

        if (!array_key_exists($ruc, $companies)) {
            return false;
        }

        if (!array_key_exists($key, $companies[$ruc])) {
            return false;
        }

        return $companies[$ruc][$key];
    }
}
