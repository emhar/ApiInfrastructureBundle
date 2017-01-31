<?php

namespace Emhar\ApiInfrastructureBundle\Nelmio\Parser;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\Driver\DoctrineTypeDriver;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactoryInterface;
use Nelmio\ApiDocBundle\Parser\JmsMetadataParser;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;

/**
 * {@inheritDoc}
 */
class InheritanceAwareJmsMetadataParser extends JmsMetadataParser
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \Metadata\MetadataFactoryInterface
     */
    private $pfactory;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $pnamingStrategy;

    /**
     * Constructor, requires JMS Metadata factory
     * @param MetadataFactoryInterface $factory
     * @param PropertyNamingStrategyInterface $namingStrategy
     * @param DocCommentExtractor $commentExtractor
     */
    public function __construct(MetadataFactoryInterface $factory, PropertyNamingStrategyInterface $namingStrategy,
                                DocCommentExtractor $commentExtractor
    )
    {
        parent::__construct($factory, $namingStrategy, $commentExtractor);
        $this->pfactory = $factory;
        $this->pnamingStrategy = $namingStrategy;
    }

    public function setDoctrineRegistry(ManagerRegistry $doctrineRegistry)
    {
        $this->registry = $doctrineRegistry;
    }

    /**
     * {@inheritedDoc}
     * JMS meta data don't hold information about target class if class has inheritance mapping
     * @see DoctrineTypeDriver::setPropertyType() read the comment inside method
     *
     * Two hacks are added on this method :
     *  - set super type (which hold discriminator map) on field with an inheritance mapping
     *  - guess children from sub types and add on it a special description (only for [<list of discriminator>])
     */
    protected function doParse($className, $visited = array(), array $groups = array())
    {
        $meta = $this->pfactory->getMetadataForClass($className);

        if (null === $meta) {
            throw new \InvalidArgumentException(sprintf("No metadata found for class %s", $className));
        }

        $exclusionStrategies = array();
        if ($groups) {
            $exclusionStrategies[] = new GroupsExclusionStrategy($groups);
        }

        $params = array();

        $reflection = new \ReflectionClass($className);
        $defaultProperties = array_map(function ($default) {
            if (is_array($default) && count($default) === 0) {
                return null;
            }

            return $default;
        }, $reflection->getDefaultProperties());

        // iterate over property metadata
        foreach ($meta->propertyMetadata as $item) {
            //////////////////////////////////////////////////////
            //                  START OVERRIDE                  //
            //////////////////////////////////////////////////////
            if (is_null($item->type) && $doctrineMeta = $this->tryLoadingDoctrineMetadata($className)) {
                $this->setPropertyType($doctrineMeta, $item);
            }
            //////////////////////////////////////////////////////
            //                    END OVERRIDE                  //
            //////////////////////////////////////////////////////
            if (!is_null($item->type)) {
                $name = $this->pnamingStrategy->translateName($item);

                $dataType = $this->processDataType($item);

                // apply exclusion strategies
                foreach ($exclusionStrategies as $strategy) {
                    if (true === $strategy->shouldSkipProperty($item, SerializationContext::create())) {
                        continue 2;
                    }
                }

                if (!$dataType['inline']) {
                    $params[$name] = array(
                        'dataType' => $dataType['normalized'],
                        'actualType' => $dataType['actualType'],
                        'subType' => $dataType['class'],
                        'required' => false,
                        'default' => isset($defaultProperties[$item->name]) ? $defaultProperties[$item->name] : null,
                        //TODO: can't think of a good way to specify this one, JMS doesn't have a setting for this
                        'description' => $this->getDescription($item),
                        'readonly' => $item->readOnly,
                        'sinceVersion' => $item->sinceVersion,
                        'untilVersion' => $item->untilVersion,
                    );

                    if (!is_null($dataType['class']) && false === $dataType['primitive']) {
                        $params[$name]['class'] = $dataType['class'];
                    }
                }

                // we can use type property also for custom handlers, then we don't have here real class name
                if (!class_exists($dataType['class'])) {
                    continue;
                }

                // if class already parsed, continue, to avoid infinite recursion
                if (in_array($dataType['class'], $visited)) {
                    continue;
                }

                // check for nested classes with JMS metadata
                if ($dataType['class'] && false === $dataType['primitive'] && null !== $this->pfactory->getMetadataForClass($dataType['class'])) {
                    $visited[] = $dataType['class'];
                    $children = $this->doParse($dataType['class'], $visited, $groups);

                    if ($dataType['inline']) {
                        $params = array_merge($params, $children);
                    } else {
                        $params[$name]['children'] = $children;
                    }
                }
                //////////////////////////////////////////////////////
                //                  START OVERRIDE                  //
                //////////////////////////////////////////////////////
                $metadata = $this->pfactory->getMetadataForClass($dataType['class']);
                /* @var $metadata ClassMetadata */
                $descriptionPrefix = 'Only for "' . $metadata->discriminatorFieldName . '" in ';
                foreach ($metadata->discriminatorMap as $discriminatorValue => $class) {
                    $children = $this->doParse($class, $visited, $groups);
                    //merge description
                    foreach ($children as $subName => $parameter) {
                        $initialDescription = null;
                        if ($dataType['inline'] && isset($params[$subName])) {
                            $initialDescription = $params[$subName]['description'];
                        } elseif (isset($params[$name]['children'], $params[$name]['children'][$subName])) {
                            $initialDescription = $params[$name]['children'][$subName]['description'];
                        }
                        if ($initialDescription) {
                            $discriminatorValues = explode(
                                ', ',
                                str_replace($descriptionPrefix, '', $initialDescription)
                            );
                            $discriminatorValues[] = '"' . $discriminatorValue . '"';
                            if (count($discriminatorValues) != count($metadata->discriminatorMap)) {
                                $children[$subName]['description'] = $descriptionPrefix
                                    . implode(', ', $discriminatorValues);
                            } else {
                                $children[$subName]['description'] = '';
                            }
                        } else {
                            $children[$subName]['description'] = $descriptionPrefix . '"' . $discriminatorValue . '"';
                        }
                    }
                    if ($dataType['inline']) {
                        $params = array_merge($params, $children);
                    } else {
                        $params[$name]['children'] = array_merge($params[$name]['children'] ?? array(), $children);
                    }
                }
                //////////////////////////////////////////////////////
                //                    END OVERRIDE                  //
                //////////////////////////////////////////////////////
            }
        }
        return $params;
    }

    /**
     * @param string $className
     *
     * @return null|DoctrineClassMetadata
     */
    protected function tryLoadingDoctrineMetadata($className)
    {
        if (!$manager = $this->registry->getManagerForClass($className)) {
            return null;
        }

        if ($manager->getMetadataFactory()->isTransient($className)) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadata $propertyMetadata
     */
    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata)
    {
        $propertyName = $propertyMetadata->name;
        if ($doctrineMetadata->hasAssociation($propertyName)) {
            $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);

            if (null === $targetMetadata = $this->tryLoadingDoctrineMetadata($targetEntity)) {
                return;
            }

            if (!$doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = "ArrayCollection<{$targetEntity}>";
            }
            $propertyMetadata->setType($targetEntity);
        }
    }
}
