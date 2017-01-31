<?php

namespace Emhar\ApiInfrastructureBundle\Serializer;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Handler\DateHandler as BaseDateHandler;
use JMS\Serializer\JsonDeserializationVisitor as JMSJsonDeserializationVisitor;
use Symfony\Component\Translation\TranslatorInterface;

class DateHandler extends BaseDateHandler
{
    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    protected $pDefaultFormat;

    /**
     * @param string $defaultFormat
     * @param string $defaultTimezone
     * @param bool $xmlCData
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator, $defaultFormat = \DateTime::ISO8601, $defaultTimezone = 'UTC', $xmlCData = true)
    {
        parent::__construct($defaultFormat, $defaultTimezone, $xmlCData);
        $this->translator = $translator;
        $this->pDefaultFormat = $defaultFormat;
    }

    public function deserializeDateTimeFromJson(JMSJsonDeserializationVisitor $visitor, $data, array $type)
    {
        try {
            $result = parent::deserializeDateTimeFromJson($visitor, $data, $type);
        } catch (RuntimeException $e) {
            $format = $type['params'][0] ?? $this->pDefaultFormat;
            throw new RuntimeException($this->translator->trans('Invalid_date_time_%date%_expected_%format%', array(
                '%date%' => $data,
                '%format%' => $format
            ), 'validators'));
        }

        return $result;
    }
}