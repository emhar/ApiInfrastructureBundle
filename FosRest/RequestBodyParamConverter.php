<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Emhar\ApiInfrastructureBundle\FosRest;

use Emhar\ApiInfrastructureBundle\Serializer\ApiDeserializationException;
use FOS\RestBundle\Request\RequestBodyParamConverter as BaseRequestBodyParamConverter;
use FOS\RestBundle\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Tyler Stroud <tyler@tylerstroud.com>
 */
class RequestBodyParamConverter extends BaseRequestBodyParamConverter
{
    private $pValidator;

    /**
     * The name of the argument on which the ConstraintViolationList will be set.
     *
     * @var null|string
     */
    private $pValidationErrorsArgument;

    /**
     * @param Serializer $serializer
     * @param array|null $groups An array of groups to be used in the serialization context
     * @param string|null $version A version string to be used in the serialization context
     * @param ValidatorInterface $validator
     * @param string|null $validationErrorsArgument
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        Serializer $serializer,
        $groups = null,
        $version = null,
        ValidatorInterface $validator = null,
        $validationErrorsArgument = null
    )
    {
        parent::__construct($serializer, $groups, $version, $validator, $validationErrorsArgument);

        $this->pValidator = $validator;
        $this->pValidationErrorsArgument = $validationErrorsArgument;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            parent::apply($request, $configuration);
        } catch (ApiDeserializationException $e) {
            $request->attributes->set($configuration->getName(), $e->getIncompleteObject());
            $request->attributes->set(
                $this->pValidationErrorsArgument,
                $this->mergeConstraintViolationAndJmsException(
                    (array)$configuration->getOptions(),
                    $e->getIncompleteObject(),
                    $e->getJmsDeserializationExceptions()
                )
            );

        }
        return true;
    }

    /**
     * @param array $options
     * @param $incompleteObject
     * @param array $exceptions
     * @return ConstraintViolationListInterface
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function mergeConstraintViolationAndJmsException(array $options, $incompleteObject, array $exceptions): ConstraintViolationListInterface
    {
        $validatorOptions = $this->getValidatorOptions($options);
        $errors = $this->pValidator->validate($incompleteObject, null, $validatorOptions['groups']);
        foreach ($exceptions as $propertyPath => $exception) {
            /* @var $exception \Exception */
            foreach ($errors as $offset => $error) {
                /* @var $error ConstraintViolationInterface */
                if ($error->getPropertyPath() == $propertyPath) {
                    $errors->remove($offset);
                }
            }
            $errors->add(new ConstraintViolation($exception->getMessage(), $exception->getMessage(), array(), $incompleteObject, $propertyPath, null));
        }
        return $errors;
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    private function getValidatorOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'groups' => null,
            'traverse' => false,
            'deep' => false,
        ]);

        return $resolver->resolve($options['validator'] ?? []);
    }
}
