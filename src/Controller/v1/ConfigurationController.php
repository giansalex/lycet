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
use OpenApi\Annotations as OA;

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
     * @OA\Post(
     *     path="/configuration/",
     *     tags={"configuration"},
     *     summary="Guarda el certificado y logo",
     *     description="Guarda archivos de configuración",
     *     operationId="saveConfiguration",
     *     @OA\RequestBody(required=true, description="archivos en base64",
     *         @OA\JsonContent(ref="#/components/schemas/FilesData")),
     *     @OA\Response(response=200, description="Operacion exitosa")
     * )
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
     * @OA\Put(
     *     path="/configuration/company/{ruc}",
     *     tags={"configuration"},
     *     summary="Registrar o actualizar empresa",
     *     description="Registra o actualiza la configuración de una empresa para facturación electrónica.",
     *     operationId="saveCompanyConfiguration",
     *     @OA\Parameter(name="ruc", in="path", required=true, description="RUC de la empresa", @OA\Schema(type="string")),
     *     @OA\Parameter(name="token", in="query", required=true, description="Token de autenticación", @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"SOL_USER", "SOL_PASS", "certificate", "FE_URL"},
     *             @OA\Property(property="SOL_USER", type="string", example="20000000001MODDATOS"),
     *             @OA\Property(property="SOL_PASS", type="string", example="moddatos"),
     *             @OA\Property(property="certificate", type="string", description="Certificado .pem codificado en base64", example="<certificado .pem en base64>"),
     *             @OA\Property(property="FE_URL", type="string", example="https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Empresa registrada o actualizada correctamente")
     * )
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

        $companiesJson = $this->fileStore->get('companies');
        $companies = !empty($companiesJson) ? json_decode($companiesJson, true) : [];

        $company = [
            'SOL_USER' => $data['SOL_USER'],
            'SOL_PASS' => $data['SOL_PASS'],
            'certificate' => $certFilename,
        ];

        // Preserve existing logo if already uploaded
        if (!empty($companies[$ruc]['logo'])) {
            $company['logo'] = $companies[$ruc]['logo'];
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
     * @Route("/company/{ruc}/logo", methods={"PUT"})
     *
     * @OA\Put(
     *     path="/configuration/company/{ruc}/logo",
     *     tags={"configuration"},
     *     summary="Subir logo de empresa",
     *     description="Sube o actualiza el logo de una empresa en base64.",
     *     operationId="saveCompanyLogo",
     *     @OA\Parameter(name="ruc", in="path", required=true, description="RUC de la empresa", @OA\Schema(type="string")),
     *     @OA\Parameter(name="token", in="query", required=true, description="Token de autenticación", @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"logo"},
     *             @OA\Property(property="logo", type="string", description="Logo codificado en base64", example="<logo en base64>")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Logo registrado o actualizado correctamente")
     * )
     *
     * @param string $ruc
     * @param Request $request
     * @return Response
     */
    public function uploadCompanyLogo(string $ruc, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['logo'])) {
            return $this->json(
                ['error' => 'logo is required (base64 encoded image)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $companiesJson = $this->fileStore->get('companies');
        $companies = !empty($companiesJson) ? json_decode($companiesJson, true) : [];

        if (!array_key_exists($ruc, $companies)) {
            return $this->json(['error' => 'Company not found. Register the company first.'], Response::HTTP_NOT_FOUND);
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
            @unlink($this->dataPath . DIRECTORY_SEPARATOR . $companies[$ruc]['logo']);
        }

        $logoFilename = $ruc . '-logo.' . $ext;
        file_put_contents(
            $this->dataPath . DIRECTORY_SEPARATOR . $logoFilename,
            $imageData
        );

        $companies[$ruc]['logo'] = $logoFilename;

        $this->fileStore->store(
            'companies',
            json_encode($companies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $this->json(['ruc' => $ruc, 'message' => 'Logo uploaded']);
    }

    /**
     * @Route("/company/{ruc}/logo", methods={"DELETE"})
     *
     * @OA\Delete(
     *     path="/configuration/company/{ruc}/logo",
     *     tags={"configuration"},
     *     summary="Eliminar logo de empresa",
     *     description="Elimina el logo registrado de una empresa.",
     *     operationId="deleteCompanyLogo",
     *     @OA\Parameter(name="ruc", in="path", required=true, description="RUC de la empresa", @OA\Schema(type="string")),
     *     @OA\Parameter(name="token", in="query", required=true, description="Token de autenticación", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Logo eliminado correctamente")
     * )
     *
     * @param string $ruc
     * @return Response
     */
    public function removeCompanyLogo(string $ruc): Response
    {
        $companiesJson = $this->fileStore->get('companies');
        $companies = !empty($companiesJson) ? json_decode($companiesJson, true) : [];

        if (!array_key_exists($ruc, $companies)) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        if (!empty($companies[$ruc]['logo'])) {
            @unlink($this->dataPath . DIRECTORY_SEPARATOR . $companies[$ruc]['logo']);
        }

        unset($companies[$ruc]['logo']);

        $this->fileStore->store(
            'companies',
            json_encode($companies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $this->json(['ruc' => $ruc, 'message' => 'Logo removed']);
    }

    /**
     * @Route("/company/{ruc}", methods={"DELETE"})
     *
     * @OA\Delete(
     *     path="/configuration/company/{ruc}",
     *     tags={"configuration"},
     *     summary="Eliminar empresa",
     *     description="Elimina la configuración registrada de una empresa.",
     *     operationId="deleteCompanyConfiguration",
     *     @OA\Parameter(name="ruc", in="path", required=true, description="RUC de la empresa", @OA\Schema(type="string")),
     *     @OA\Parameter(name="token", in="query", required=true, description="Token de autenticación", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Empresa eliminada correctamente")
     * )
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

        if (!empty($companies[$ruc]['logo'])) {
            @unlink($this->dataPath . DIRECTORY_SEPARATOR . $companies[$ruc]['logo']);
        }

        unset($companies[$ruc]);

        $this->fileStore->store(
            'companies',
            json_encode($companies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        @unlink($this->dataPath . DIRECTORY_SEPARATOR . $ruc . '-cert.pem');

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
}