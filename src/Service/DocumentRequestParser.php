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
     * @return mixed
     */
    function getObject(Request $request, string $class): DocumentInterface
    {
        $data = $request->getContent();

        return $this->serializer->deserialize(
            $data,
            $class,
            'json'
        );
    }
}