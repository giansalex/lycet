<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 21/02/2018
 * Time: 03:30 PM
 */

namespace App\Controller\v1;

use App\Service\CompanyConfigResolver;
use App\Service\ConfigProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigurationController
 *
 * @Route("/api/v1/configuration")
 */
class ConfigurationController extends AbstractController
{
    private const NAME_PATTERN = '/^[A-Za-z0-9_.-]{1,50}$/';

    private const ROOT_OPTIONAL_KEYS = ['FE_URL', 'RE_URL', 'GUIA_URL', 'AUTH_URL', 'API_URL', 'CLIENT_ID', 'CLIENT_SECRET'];

    /**
     * @var ConfigProviderInterface
     */
    private $fileStore;

    /**
     * @var string
     */
    private $dataPath;

    /**
     * @var CompanyConfigResolver
     */
    private $resolver;

    /**
     * ConfigurationController constructor.
     * @param ConfigProviderInterface $fileStore
     * @param string $dataPath
     * @param CompanyConfigResolver $resolver
     */
    public function __construct(ConfigProviderInterface $fileStore, string $dataPath, CompanyConfigResolver $resolver)
    {
        $this->fileStore = $fileStore;
        $this->dataPath = $dataPath;
        $this->resolver = $resolver;
    }

    /**
     * @Route("/", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function config(Request $request) : Response
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $fileContent = base64_decode($value);
            $this->fileStore->store($key, $fileContent);
        }

        return new Response();
    }

    /**
     * @Route("/company/{ruc}", methods={"GET"})
     *
     * @param string $ruc
     * @return Response
     */
    public function showCompany(string $ruc): Response
    {
        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound();
        }

        $company = $companies[$ruc];

        // Explicit allowlist: secrets (SOL_PASS, CLIENT_SECRET, certificates) are never exposed.
        $result = [
            'ruc' => $ruc,
            'environment' => $this->resolver->getActiveEnvironment($company),
            'environments' => $this->resolver->getEnvironmentNames($company),
            'hasLogo' => !empty($company['logo']) && file_exists($this->dataFile($company['logo'])),
            'hasCertificate' => $this->resolver->resolveCertificate($company) !== '',
            'SOL_USER' => $this->resolver->resolve($company, 'SOL_USER'),
            'FE_URL' => $this->resolver->resolve($company, 'FE_URL'),
            'RE_URL' => $this->resolver->resolve($company, 'RE_URL'),
            'GUIA_URL' => $this->resolver->resolve($company, 'GUIA_URL'),
            'AUTH_URL' => $this->resolver->resolve($company, 'AUTH_URL'),
            'API_URL' => $this->resolver->resolve($company, 'API_URL'),
        ];

        $clientId = $this->resolver->resolve($company, 'CLIENT_ID');
        if ($clientId !== null) {
            $result['CLIENT_ID'] = $clientId;
        }

        return $this->json($result);
    }

    /**
     * @Route("/company/{ruc}", methods={"PUT"})
     *
     * @param string $ruc
     * @param Request $request
     * @return Response
     */
    public function upsertCompany(string $ruc, Request $request): Response
    {
        $data = $this->getJsonBody($request);

        if (empty($data['SOL_USER']) || empty($data['SOL_PASS']) || empty($data['certificate'])) {
            return $this->json(
                ['error' => 'SOL_USER, SOL_PASS and certificate are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $companies = $this->loadCompanies();
        $existing = isset($companies[$ruc]) && is_array($companies[$ruc]) ? $companies[$ruc] : [];

        $certFilename = $ruc . '-cert.pem';
        file_put_contents($this->dataFile($certFilename), base64_decode($data['certificate']));

        $company = [
            'SOL_USER' => $data['SOL_USER'],
            'SOL_PASS' => $data['SOL_PASS'],
            'certificate' => $certFilename,
        ];

        // Preserve existing logo if already uploaded
        if (!empty($existing['logo'])) {
            $company['logo'] = $existing['logo'];
        }

        foreach (self::ROOT_OPTIONAL_KEYS as $key) {
            if (!empty($data[$key])) {
                $company[$key] = $data[$key];
            }
        }

        // Environments: replace when provided, otherwise preserve the existing ones
        $existingEnvironments = isset($existing['environments']) && is_array($existing['environments'])
            ? $existing['environments']
            : [];

        if (array_key_exists('environments', $data)) {
            if (!is_array($data['environments'])) {
                return $this->json(
                    ['error' => 'environments must be an object of name => config'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $environments = [];
            foreach ($data['environments'] as $name => $block) {
                $name = (string) $name;
                if (!$this->isValidEnvironmentName($name)) {
                    return $this->json(['error' => 'Invalid environment name: ' . $name], Response::HTTP_BAD_REQUEST);
                }
                if (!is_array($block)) {
                    return $this->json(
                        ['error' => 'Environment "' . $name . '" must be an object'],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $environments[$name] = $this->buildEnvironmentBlock($ruc, $name, $block, $existingEnvironments[$name] ?? []);
            }

            // Remove certificates of environments that no longer exist
            foreach ($existingEnvironments as $name => $block) {
                if (!array_key_exists($name, $environments)) {
                    $this->removeEnvironmentCertificate($block, $existing, $environments);
                }
            }
        } else {
            $environments = $existingEnvironments;
        }

        if (!empty($environments)) {
            $company['environments'] = $environments;
        }

        // Active environment: set/unset when provided, otherwise preserve if still valid
        if (array_key_exists('environment', $data)) {
            $environment = $data['environment'];
            if ($environment !== null && $environment !== '') {
                if (!$this->isValidActiveEnvironment($environment, $company)) {
                    return $this->json(
                        ['error' => 'environment must be "beta", "production" or a defined environment name'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                $company['environment'] = $environment;
            }
        } elseif (isset($existing['environment']) && $this->isValidActiveEnvironment($existing['environment'], $company)) {
            $company['environment'] = $existing['environment'];
        }

        $companies[$ruc] = $company;
        $this->saveCompanies($companies);

        return $this->json(['ruc' => $ruc, 'message' => 'Company configured']);
    }

    /**
     * @Route("/company/{ruc}/environment", methods={"PUT"})
     *
     * @param string $ruc
     * @param Request $request
     * @return Response
     */
    public function setActiveEnvironment(string $ruc, Request $request): Response
    {
        $data = $this->getJsonBody($request);
        $name = $data['environment'] ?? null;

        if (!is_string($name) || $name === '') {
            return $this->json(['error' => 'environment is required'], Response::HTTP_BAD_REQUEST);
        }

        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound();
        }

        if (!$this->isValidActiveEnvironment($name, $companies[$ruc])) {
            return $this->json(
                ['error' => 'Unknown environment "' . $name . '". Define it first or use "beta" / "production"'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $companies[$ruc]['environment'] = $name;
        $this->saveCompanies($companies);

        return $this->json(['ruc' => $ruc, 'environment' => $name, 'message' => 'Active environment updated']);
    }

    /**
     * @Route("/company/{ruc}/environments/{name}", methods={"PUT"})
     *
     * @param string $ruc
     * @param string $name
     * @param Request $request
     * @return Response
     */
    public function upsertEnvironment(string $ruc, string $name, Request $request): Response
    {
        if (!$this->isValidEnvironmentName($name)) {
            return $this->json(
                ['error' => 'Invalid environment name. Allowed: letters, digits, "_", "." and "-" (max 50 chars)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound();
        }

        $data = $this->getJsonBody($request);
        $existing = $companies[$ruc]['environments'][$name] ?? [];
        $block = $this->buildEnvironmentBlock($ruc, $name, $data, is_array($existing) ? $existing : []);

        if (empty($block)) {
            return $this->json(
                ['error' => 'At least one of ' . implode(', ', CompanyConfigResolver::ENVIRONMENT_KEYS) . ' is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!isset($companies[$ruc]['environments']) || !is_array($companies[$ruc]['environments'])) {
            $companies[$ruc]['environments'] = [];
        }
        $companies[$ruc]['environments'][$name] = $block;
        $this->saveCompanies($companies);

        return $this->json(['ruc' => $ruc, 'environment' => $name, 'message' => 'Environment configured']);
    }

    /**
     * @Route("/company/{ruc}/environments/{name}", methods={"DELETE"})
     *
     * @param string $ruc
     * @param string $name
     * @return Response
     */
    public function removeEnvironment(string $ruc, string $name): Response
    {
        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound();
        }

        $company = $companies[$ruc];
        if (!isset($company['environments'][$name]) || !is_array($company['environments'][$name])) {
            return $this->notFound('Environment not found');
        }

        $block = $company['environments'][$name];
        unset($company['environments'][$name]);

        $this->removeEnvironmentCertificate($block, $company, $company['environments']);

        if (empty($company['environments'])) {
            unset($company['environments']);
        }

        if (isset($company['environment']) && $company['environment'] === $name) {
            unset($company['environment']);
        }

        $companies[$ruc] = $company;
        $this->saveCompanies($companies);

        return $this->json(['ruc' => $ruc, 'environment' => $name, 'message' => 'Environment removed']);
    }

    /**
     * @Route("/company/{ruc}/logo", methods={"PUT"})
     *
     * @param string $ruc
     * @param Request $request
     * @return Response
     */
    public function uploadCompanyLogo(string $ruc, Request $request): Response
    {
        $data = $this->getJsonBody($request);

        if (empty($data['logo'])) {
            return $this->json(
                ['error' => 'logo is required (base64 encoded image)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound('Company not found. Register the company first.');
        }

        $imageData = base64_decode($data['logo']);
        $ext = $this->detectImageExtension($imageData);
        if ($ext === null) {
            return $this->json(
                ['error' => 'Unsupported image format. Use PNG, JPEG, or GIF.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Remove old logo if extension changed
        if (!empty($companies[$ruc]['logo'])) {
            $this->unlinkDataFile($companies[$ruc]['logo']);
        }

        $logoFilename = $ruc . '-logo.' . $ext;
        file_put_contents($this->dataFile($logoFilename), $imageData);

        $companies[$ruc]['logo'] = $logoFilename;
        $this->saveCompanies($companies);

        return $this->json(['ruc' => $ruc, 'message' => 'Logo uploaded']);
    }

    /**
     * @Route("/company/{ruc}/logo", methods={"DELETE"})
     *
     * @param string $ruc
     * @return Response
     */
    public function removeCompanyLogo(string $ruc): Response
    {
        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound();
        }

        if (!empty($companies[$ruc]['logo'])) {
            $this->unlinkDataFile($companies[$ruc]['logo']);
        }

        unset($companies[$ruc]['logo']);
        $this->saveCompanies($companies);

        return $this->json(['ruc' => $ruc, 'message' => 'Logo removed']);
    }

    /**
     * @Route("/company/{ruc}", methods={"DELETE"})
     *
     * @param string $ruc
     * @return Response
     */
    public function removeCompany(string $ruc): Response
    {
        $companies = $this->loadCompanies();

        if (!array_key_exists($ruc, $companies)) {
            return $this->notFound();
        }

        $company = is_array($companies[$ruc]) ? $companies[$ruc] : [];

        if (!empty($company['logo'])) {
            $this->unlinkDataFile($company['logo']);
        }

        if (isset($company['environments']) && is_array($company['environments'])) {
            foreach ($company['environments'] as $block) {
                if (is_array($block) && !empty($block['certificate'])) {
                    $this->unlinkDataFile($block['certificate']);
                }
            }
        }

        unset($companies[$ruc]);
        $this->saveCompanies($companies);

        $this->unlinkDataFile($ruc . '-cert.pem');
        if (!empty($company['certificate'])) {
            $this->unlinkDataFile($company['certificate']);
        }

        return $this->json(['ruc' => $ruc, 'message' => 'Company removed']);
    }

    /**
     * Detect image extension from binary data using PHP's built-in image functions.
     */
    private function detectImageExtension(string $data): ?string
    {
        $info = @getimagesizefromstring($data);
        if ($info === false) {
            return null;
        }

        $ext = image_type_to_extension($info[2], false);

        return $ext ?: null;
    }

    private function loadCompanies(): array
    {
        $json = $this->fileStore->get('companies');
        $companies = !empty($json) ? json_decode($json, true) : [];

        return is_array($companies) ? $companies : [];
    }

    private function saveCompanies(array $companies): void
    {
        $this->fileStore->store(
            'companies',
            json_encode($companies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function getJsonBody(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function notFound(string $message = 'Company not found'): Response
    {
        return $this->json(['error' => $message], Response::HTTP_NOT_FOUND);
    }

    private function dataFile(string $filename): string
    {
        return $this->dataPath . DIRECTORY_SEPARATOR . $filename;
    }

    private function unlinkDataFile(?string $filename): void
    {
        if (!empty($filename)) {
            @unlink($this->dataFile($filename));
        }
    }

    private function environmentCertFilename(string $ruc, string $name): string
    {
        return $ruc . '-' . $name . '-cert.pem';
    }

    private function isValidEnvironmentName(string $name): bool
    {
        return preg_match(self::NAME_PATTERN, $name) === 1;
    }

    /**
     * An active environment must be a reserved SUNAT preset (beta/production) or a defined block.
     */
    private function isValidActiveEnvironment($name, array $company): bool
    {
        if (!is_string($name) || $name === '') {
            return false;
        }

        if (CompanyConfigResolver::isPresetEnvironment($name)) {
            return true;
        }

        return isset($company['environments'][$name]) && is_array($company['environments'][$name]);
    }

    /**
     * Build an environment block from request data. A base64 `certificate` is written to
     * "{ruc}-{name}-cert.pem"; when omitted, the previously stored certificate is preserved.
     * Returns an empty array when no recognized key is present.
     */
    private function buildEnvironmentBlock(string $ruc, string $name, array $data, array $existing): array
    {
        $block = [];

        foreach (CompanyConfigResolver::ENVIRONMENT_KEYS as $key) {
            if ($key === 'certificate') {
                continue;
            }
            if (!empty($data[$key]) && is_scalar($data[$key])) {
                $block[$key] = (string) $data[$key];
            }
        }

        if (!empty($data['certificate']) && is_string($data['certificate'])) {
            $certFilename = $this->environmentCertFilename($ruc, $name);
            file_put_contents($this->dataFile($certFilename), base64_decode($data['certificate']));
            $block['certificate'] = $certFilename;
        } elseif (!empty($existing['certificate'])) {
            $block['certificate'] = $existing['certificate'];
        }

        return $block;
    }

    /**
     * Remove the certificate file of an environment block unless it is still referenced
     * by the company root or by another environment.
     */
    private function removeEnvironmentCertificate(array $block, array $company, array $remainingEnvironments): void
    {
        $certificate = $block['certificate'] ?? null;
        if (empty($certificate)) {
            return;
        }

        if (($company['certificate'] ?? null) === $certificate) {
            return;
        }

        foreach ($remainingEnvironments as $other) {
            if (is_array($other) && ($other['certificate'] ?? null) === $certificate) {
                return;
            }
        }

        $this->unlinkDataFile($certificate);
    }
}
