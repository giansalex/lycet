<?php

declare(strict_types=1);

namespace App\Serializer;

use Greenter\Model\Despatch\Contenedor;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;

/**
 * Shipment::contenedores acepta elementos string (precinto, formato anterior)
 * o Contenedor {numero, precinto} (greenter/core >= 5.5).
 */
class ContenedorHandler implements SubscribingHandlerInterface
{
    public const TYPE = 'ContenedorList';

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => self::TYPE,
                'method' => 'deserialize',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => self::TYPE,
                'method' => 'serialize',
            ],
        ];
    }

    public function deserialize(JsonDeserializationVisitor $visitor, $data, array $type, Context $context): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        $items = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $items[] = (new Contenedor())
                    ->setNumero(isset($item['numero']) ? (string)$item['numero'] : null)
                    ->setPrecinto(isset($item['precinto']) ? (string)$item['precinto'] : null);
            } elseif ($item !== null) {
                $items[] = (string)$item;
            }
        }

        return $items;
    }

    public function serialize(JsonSerializationVisitor $visitor, ?array $data, array $type, Context $context)
    {
        if ($data === null) {
            return null;
        }

        return array_map(static function ($item) {
            if ($item instanceof Contenedor) {
                return ['numero' => $item->getNumero(), 'precinto' => $item->getPrecinto()];
            }

            return (string)$item;
        }, array_values($data));
    }
}
