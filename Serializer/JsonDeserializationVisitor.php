<?php


namespace Emhar\ApiInfrastructureBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * Class JsonDeserializationVisitor
 *
 * This class override totally JMS JsonDeserializationVisitor
 * for one purpose : a better error handling.
 *
 * JMS serializer visitors have private properties,
 * we cannot override only one method.
 *
 * In this overridden form, two change are made :
 *
 *  - In case of exception for incompatible input data with output type (think of invalid date string)
 *    Exception is stored and deserialization continue (just doesn't set property with error)
 *  - At the end of deserialization process, if exceptions have been thrown a global is thrown.
 *    Global exception contains all sub exception indexed by a property that simulate symfony validation property path
 *
 *
 * @see JsonDeserializationVisitor::visitProperty
 * @see JsonDeserializationVisitor::visitArray
 */
class JsonDeserializationVisitor extends \JMS\Serializer\JsonDeserializationVisitor
{
    /**
     * @var \Exception[]
     */
    protected $exceptions = array();

    /**
     * @var \SplStack
     */
    private $propertyPathStack;

    /**
     * @var GraphNavigator
     */
    private $navigator;

    /**
     * @var mixed
     */
    private $result;

    /**
     * @var \SplStack
     */
    private $objectStack;

    /**
     * @var mixed
     */
    private $currentObject;

    /**
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * {@inheritDoc}
     */
    public function getNavigator()
    {
        return $this->navigator;
    }

    /**
     * {@inheritDoc}
     */
    public function setNavigator(GraphNavigator $navigator)
    {
        $this->navigator = $navigator;
        $this->result = null;
        $this->objectStack = new \SplStack;
        $this->propertyPathStack = new \SplStack;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($data)
    {
        return $this->decode($data);
    }

    protected function decode($str)
    {
        $decoded = json_decode($str, true);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $decoded;

            case JSON_ERROR_DEPTH:
                throw new RuntimeException('Could not decode JSON, maximum stack depth exceeded.');

            case JSON_ERROR_STATE_MISMATCH:
                throw new RuntimeException('Could not decode JSON, underflow or the nodes mismatch.');

            case JSON_ERROR_CTRL_CHAR:
                throw new RuntimeException('Could not decode JSON, unexpected control character found.');

            case JSON_ERROR_SYNTAX:
                throw new RuntimeException('Could not decode JSON, syntax error - malformed JSON.');

            case JSON_ERROR_UTF8:
                throw new RuntimeException('Could not decode JSON, malformed UTF-8 characters (incorrectly encoded?)');

            default:
                throw new RuntimeException('Could not decode JSON.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitNull($data, array $type, Context $context)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function visitString($data, array $type, Context $context)
    {
        $data = (string)$data;

        if (null === $this->result) {
            $this->result = $data;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function visitBoolean($data, array $type, Context $context)
    {
        $data = (Boolean)$data;

        if (null === $this->result) {
            $this->result = $data;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function visitInteger($data, array $type, Context $context)
    {
        $data = (integer)$data;

        if (null === $this->result) {
            $this->result = $data;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDouble($data, array $type, Context $context)
    {
        $data = (double)$data;

        if (null === $this->result) {
            $this->result = $data;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     * @throws \JMS\Serializer\Exception\RuntimeException
     */
    public function visitArray($data, array $type, Context $context)
    {
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Expected array, but got %s: %s', gettype($data), json_encode($data)));
        }

        // If no further parameters were given, keys/values are just passed as is.
        if (!$type['params']) {
            if (null === $this->result) {
                $this->result = $data;
            }

            return $data;
        }

        switch (count($type['params'])) {
            case 1: // Array is a list.
                $listType = $type['params'][0];

                $result = array();
                if (null === $this->result) {
                    $this->result = &$result;
                }

                foreach ($data as $k => $v) {
                    $this->propertyPathStack->rewind();
                    $propertyPath = $this->propertyPathStack->current() . '[' . $k . ']';
                    $this->propertyPathStack->push($propertyPath);
                    $result[] = $this->navigator->accept($v, $listType, $context);
                    $this->propertyPathStack->pop();
                }

                return $result;

            case 2: // Array is a map.
                list($keyType, $entryType) = $type['params'];

                $result = array();
                if (null === $this->result) {
                    $this->result = &$result;
                }

                foreach ($data as $k => $v) {
                    $this->propertyPathStack->rewind();
                    $propertyPath = $this->propertyPathStack->current() . '[' . $k . ']';
                    $this->propertyPathStack->push($propertyPath);
                    $result[$this->navigator->accept($k, $keyType, $context)] = $this->navigator->accept($v, $entryType, $context);
                    $this->propertyPathStack->pop();
                }

                return $result;

            default:
                throw new RuntimeException(sprintf('Array type cannot have more than 2 parameters, but got %s.', json_encode($type['params'])));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function startVisitingObject(ClassMetadata $metadata, $object, array $type, Context $context)
    {
        $this->setCurrentObject($object);

        if (null === $this->result) {
            $this->result = $this->currentObject;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {

        $this->propertyPathStack->rewind();
        $propertyPath = ($this->propertyPathStack->count() ? $this->propertyPathStack->current() . '.' : '') . $metadata->name;
        $this->propertyPathStack->push($propertyPath);

        try {
            $name = $this->namingStrategy->translateName($metadata);

            if (null === $data) {
                $this->propertyPathStack->pop();
                return;
            }

            if (!is_array($data)) {
                throw new RuntimeException(sprintf('Invalid data "%s"(%s), expected "%s".', $data, $metadata->type['name'], $metadata->reflection->class));
            }

            if (!array_key_exists($name, $data)) {
                $this->propertyPathStack->pop();
                return;
            }

            if (!$metadata->type) {
                throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->reflection->class, $metadata->name));
            }
            $v = $data[$name] !== null ? $this->navigator->accept($data[$name], $metadata->type, $context) : null;

            $metadata->setValue($this->currentObject, $v);
        } catch (RuntimeException $e) {
            $this->propertyPathStack->rewind();
            $this->exceptions[$this->propertyPathStack->current()] = $e;
        }
        $this->propertyPathStack->pop();
    }

    /**
     * {@inheritDoc}
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        $obj = $this->currentObject;
        $this->revertCurrentObject();
        return $obj;
    }

    public function revertCurrentObject()
    {
        return $this->currentObject = $this->objectStack->pop();
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getCurrentObject()
    {
        return $this->currentObject;
    }

    public function setCurrentObject($object)
    {
        $this->objectStack->push($this->currentObject);
        $this->currentObject = $object;
    }
}