<?php

namespace Emhar\ApiInfrastructureBundle\Nelmio\Parser;

use Doctrine\Common\Inflector\Inflector;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\JmsMetadataParser;
use Nelmio\ApiDocBundle\Parser\ValidationParser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Time;

/**
 * {@inheritDoc}
 */
class TableizedNameValidationParser extends ValidationParser
{
    /**
     * @var JmsMetadataParser
     */
    protected $jmsParser;

    /**
     * {@inheritdoc}
     */
    public function parse(array $input)
    {
        //Remove param excluded by jms groups.
        $params = parent::parse($input);
        if ($this->jmsParser->supports($input)) {
            if (!isset($input['groups'])) {
                $input['groups'] = array();
            }
            $jmsParams = $this->jmsParser->parse($input);
            foreach ($params as $key => $param) {
                if (!array_key_exists($key, $jmsParams)) {
                    unset($params[$key]);
                }
            }
        }
        return $params;
    }

    public function setJmsParser(JmsMetadataParser $parser)
    {
        $this->jmsParser = $parser;
    }

    /**
     * {@inheritDoc}
     */
    protected function doParse($className, array $visited)
    {
        $propertyInformations = parent::doParse($className, $visited);
        foreach ($propertyInformations as $propertyName => $propertyInformation) {
            if (Inflector::tableize($propertyName) != $propertyName) {
                $propertyInformations[Inflector::tableize($propertyName)] = $propertyInformation;
                unset($propertyInformations[$propertyName]);
            }
        }
        return $propertyInformations;
    }

    /**
     * {@inheritDoc}
     */
    protected function parseConstraint(Constraint $constraint, $vparams, $className, &$visited = array())
    {
        $vparams = parent::parseConstraint($constraint, $vparams, $className, $visited);

        switch (get_class($constraint)) {
            case Time::class:
                $vparams['format'] = array();
                $vparams['format'][] = '{Time HH:MM}';
                $vparams['actualType'] = DataTypes::TIME;
                break;
        }
        return $vparams;
    }
}
