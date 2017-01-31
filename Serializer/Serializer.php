<?php

namespace Emhar\ApiInfrastructureBundle\Serializer;

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Serializer as BaseSerializer;
use JMS\Serializer\TypeParser;
use Metadata\MetadataFactoryInterface;
use PhpCollection\MapInterface;


/**
 * {@inheritDoc}
 */
class Serializer extends BaseSerializer
{
    /**
     * @var MapInterface
     */
    protected $pDeserializationVisitors;

    /**
     * {@inheritDoc}
     */
    public function __construct(MetadataFactoryInterface $factory, HandlerRegistryInterface $handlerRegistry, ObjectConstructorInterface $objectConstructor, MapInterface $serializationVisitors, MapInterface $deserializationVisitors, EventDispatcherInterface $dispatcher = null, TypeParser $typeParser = null)
    {
        $this->pDeserializationVisitors = $deserializationVisitors;
        parent::__construct($factory, $handlerRegistry, $objectConstructor, $serializationVisitors, $deserializationVisitors, $dispatcher, $typeParser);

    }

    /**
     * {@inheritDoc}
     * @throws \Emhar\ApiInfrastructureBundle\Serializer\ApiDeserializationException
     * @see \Emhar\ApiInfrastructureBundle\Serializer\JsonDeserializationVisitor::visitProperty()
     * @throws \RuntimeException
     */
    public function deserialize($data, $type, $format, DeserializationContext $context = null)
    {
        $result = parent::deserialize($data, $type, $format, $context);
        $visitor = $this->pDeserializationVisitors->get($format)->get();

        if ($visitor instanceof JsonDeserializationVisitor && !empty($visitor->getExceptions())) {
            throw new ApiDeserializationException($visitor->getExceptions(), $result);
        }
        return $result;
    }
}
