<?php

namespace Emhar\ApiInfrastructureBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BadRequestHttpExceptionHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => BadRequestHttpException::class
            ),
        );
    }

    public function serializeBadRequestHttpExceptionToJson(JsonSerializationVisitor $visitor, BadRequestHttpException $exception, array $type, Context $context)
    {
        $exceptionData = array();
        $exceptionData['property_path'] = null;
        $exceptionData['message'] = $exception->getMessage();

        return $visitor->visitArray(array($exceptionData), $type, $context);
    }
}