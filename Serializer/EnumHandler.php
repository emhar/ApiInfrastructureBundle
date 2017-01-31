<?php

namespace Emhar\ApiInfrastructureBundle\Serializer;

use Fervo\EnumBundle\JMSSerializer\EnumHandler as BaseEnumHandler;
use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\VisitorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EnumHandler extends BaseEnumHandler
{
    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param VisitorInterface $visitor
     * @param $data
     * @param array $type
     * @param Context $context
     * @return mixed|null
     * @throws \InvalidArgumentException
     * @throws \JMS\Serializer\Exception\RuntimeException
     */
    public function serializeEnumToJson(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        try {
            return parent::serializeEnumToJson($visitor, $data, $type, $context);
        } catch (RuntimeException $e) {
            $enumClass = $type['name'];
            $values = $enumClass::toArray();
            throw new RuntimeException($this->translator->trans('Invalid_enum_value_%value%_expected_%expected_values%', array(
                '%value%' => $data,
                '%expected_values%' => implode(', ', $values)
            ), 'validators'));
        }
    }
}