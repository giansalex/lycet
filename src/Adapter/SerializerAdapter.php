<?php
/**
 * Created by PhpStorm.
 * User: Soporte
 * Date: 13/02/2019
 * Time: 18:59
 */

namespace App\Adapter;

use JMS\Serializer\SerializerInterface;

/**
 * Class SerializerAdapter.
 */
class SerializerAdapter
{
    /**
     * @var SerializerInterface
     */
    private $jmsSerializer;
    /**
     * SerializerAdapter constructor.
     * @param SerializerInterface $jmsSerializer
     */
    public function __construct(SerializerInterface $jmsSerializer)
    {
        $this->jmsSerializer = $jmsSerializer;
    }
    /**
     * @param $data
     * @param $format
     * @param array $context
     * @return string
     */
    public function serialize($data, $format, array $context = array())
    {
        return $this->jmsSerializer->serialize($data, $format);
    }
}