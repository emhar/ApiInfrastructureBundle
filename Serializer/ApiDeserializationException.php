<?php

namespace Emhar\ApiInfrastructureBundle\Serializer;

/**
 * {@inheritDoc}
 */
class ApiDeserializationException extends \Exception
{

    /**
     * @var array
     */
    protected $jmsDeserializationExceptions;

    /**
     * @var mixed
     */
    protected $incompleteObject;

    /**
     * @param array $jmsDeserializationExceptions
     * @param mixed $incompleteObject
     */
    public function __construct(array $jmsDeserializationExceptions, $incompleteObject)
    {
        $this->jmsDeserializationExceptions = $jmsDeserializationExceptions;
        $this->incompleteObject = $incompleteObject;
    }

    /**
     * @return array
     */
    public function getJmsDeserializationExceptions(): array
    {
        return $this->jmsDeserializationExceptions;
    }

    /**
     * @return mixed
     */
    public function getIncompleteObject()
    {
        return $this->incompleteObject;
    }
}