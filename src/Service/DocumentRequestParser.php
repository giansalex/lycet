<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 13/02/2018
 * Time: 22:17
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;

class DocumentRequestParser implements RequestParserInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * DocumentRequestParser constructor.
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Request $request
     * @param string $class
     * @param string $key
     * @return mixed
     */
    function getObject(Request $request, string $class, string $key = ''): DocumentInterface
    {
        $data = $request->getContent();

        if(!empty($key)) {
            $dataJson = json_decode($data, true);
            if(array_key_exists($key, $dataJson)) {
                return $this->serializer->deserialize(
                    json_encode($dataJson[$key]),
                    $class,
                    'json'
                );
            }
        }

        return $this->serializer->deserialize(
            $data,
            $class,
            'json'
        );
    }

    /**
     * @param Request $request
     * @param string $key
     * @return Array
     */
    function getKey(Request $request, string $key): ?Array
    {
        $data = json_decode($request->getContent(), true);

        return array_key_exists($key, $data) ? $data[$key] : null;
    }
}