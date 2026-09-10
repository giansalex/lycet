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
     * @var CompanyConfigResolver
     */
    private $resolver;

    /**
     * ConsultCdrServiceFactory constructor.
     * @param ConfigProviderInterface $config
     * @param CompanyConfigResolver $resolver
     */
    public function __construct(ConfigProviderInterface $config, CompanyConfigResolver $resolver)
    {
        $this->config = $config;
        $this->resolver = $resolver;
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
        $ws->setCredentials(
            $this->resolveCredential('SOL_USER', $ruc),
            $this->resolveCredential('SOL_PASS', $ruc)
        );

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
        return $this->resolveCredential('SOL_USER', $ruc);
    }

    /**
     * Company value (following the environment inheritance chain, which already
     * falls back to .env) or the .env value when the RUC is unknown/not provided.
     */
    private function resolveCredential(string $key, ?string $ruc): string
    {
        $company = !empty($ruc) ? $this->resolver->getCompany($ruc) : null;
        if ($company !== null) {
            return (string) $this->resolver->resolve($company, $key);
        }

        $value = $this->config->get($key);

        return ($value === false || $value === null) ? '' : (string) $value;
    }
}
