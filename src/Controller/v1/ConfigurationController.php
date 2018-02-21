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
     * ConfigurationController constructor.
     * @param ConfigProviderInterface $fileStore
     */
    public function __construct(ConfigProviderInterface $fileStore)
    {
        $this->fileStore = $fileStore;
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
}