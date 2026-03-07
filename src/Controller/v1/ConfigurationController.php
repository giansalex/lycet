<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 21/02/2018
 * Time: 03:30 PM
 */

namespace App\Controller\v1;

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
    /**
     * @var ConfigProviderInterface
     */
    private $fileStore;

    /**
     * @var string
     */
    private $dataPath;

    /**
     * ConfigurationController constructor.
     * @param ConfigProviderInterface $fileStore
     * @param string $dataPath
     */
    public function __construct(ConfigProviderInterface $fileStore, string $dataPath)
    {
        $this->fileStore = $fileStore;
        $this->dataPath = $dataPath;
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
     * @Route("/company/{ruc}", methods={"PUT"})
     *
     * @param string $ruc
     * @param Request $request
     * @return Response
     */
    public function upsertCompany(string $ruc, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['SOL_USER']) || empty($data['SOL_PASS']) || empty($data['certificate'])) {
            return $this->json(
                ['error' => 'SOL_USER, SOL_PASS and certificate are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $certFilename = $ruc . '-cert.pem';
        file_put_contents(
            $this->dataPath . DIRECTORY_SEPARATOR . $certFilename,
            base64_decode($data['certificate'])
        );

        if (!empty($data['logo'])) {
            $logoFilename = $ruc . '-logo.png';
            file_put_contents(
                $this->dataPath . DIRECTORY_SEPARATOR . $logoFilename,
                base64_decode($data['logo'])
            );
        }

        $companiesJson = $this->fileStore->get('companies');
        $companies = !empty($companiesJson) ? json_decode($companiesJson, true) : [];

        $company = [
            'SOL_USER' => $data['SOL_USER'],
            'SOL_PASS' => $data['SOL_PASS'],
            'certificate' => $certFilename,
        ];

        if (!empty($data['logo'])) {
            $company['logo'] = $ruc . '-logo.png';
        }

        foreach (['FE_URL', 'RE_URL', 'GUIA_URL', 'AUTH_URL', 'API_URL', 'CLIENT_ID', 'CLIENT_SECRET'] as $key) {
            if (!empty($data[$key])) {
                $company[$key] = $data[$key];
            }
        }

        $companies[$ruc] = $company;

        $this->fileStore->store(
            'companies',
            json_encode($companies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $this->json(['ruc' => $ruc, 'message' => 'Company configured']);
    }

    /**
     * @Route("/company/{ruc}", methods={"DELETE"})
     *
     * @param string $ruc
     * @return Response
     */
    public function removeCompany(string $ruc): Response
    {
        $companiesJson = $this->fileStore->get('companies');
        $companies = !empty($companiesJson) ? json_decode($companiesJson, true) : [];

        if (!array_key_exists($ruc, $companies)) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        unset($companies[$ruc]);

        $this->fileStore->store(
            'companies',
            json_encode($companies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        @unlink($this->dataPath . DIRECTORY_SEPARATOR . $ruc . '-cert.pem');
        @unlink($this->dataPath . DIRECTORY_SEPARATOR . $ruc . '-logo.png');

        return $this->json(['ruc' => $ruc, 'message' => 'Company removed']);
    }
}