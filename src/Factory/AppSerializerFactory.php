<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 10/02/2018
 * Time: 01:08 PM
 */

namespace App\Factory;


use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class AppSerializerFactory
{
    public static function createSerializer()
    {
        $extractor = new PropertyInfoExtractor(array(), array(new PhpDocExtractor()));
        $normalizer = new ObjectNormalizer(null, null, null, $extractor);
        $normalizers = array(new DateTimeNormalizer(), new ArrayDenormalizer(), $normalizer);

        $serializer = new Serializer($normalizers, []);

        return $serializer;
    }
}