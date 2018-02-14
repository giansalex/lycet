<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 13/02/2018
 * Time: 22:17
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DocumentRequestParser implements RequestParserInterface
{
    /**
     * @var ContextAwareDenormalizerInterface
     */
    private $denormalizer;

    /**
     * DocumentRequestParser constructor.
     * @param ContextAwareDenormalizerInterface $denormalizer
     */
    public function __construct(ContextAwareDenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * @param Request $request
     * @param string $class
     * @return mixed
     */
    function getObject(Request $request, string $class): DocumentInterface
    {
        $context = array(ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true);

        $data = $request->getContent();
        $decode = json_decode($data, true);

        $document = $this->denormalizer->denormalize(
            $decode,
            $class, null, $context
        );

        return $document;
    }
}