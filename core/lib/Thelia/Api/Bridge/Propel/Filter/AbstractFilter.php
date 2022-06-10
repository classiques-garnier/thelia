<?php

namespace Thelia\Api\Bridge\Propel\Filter;


use Propel\Runtime\ActiveQuery\ModelCriteria;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

abstract class AbstractFilter implements FilterInterface
{

    public function __construct(protected NameConverterInterface $nameConverter, protected RequestStack $requestStack, protected ?array $properties = null)
    {
    }

    abstract protected function filterProperty(string $property, $value, ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = []);


    /**
     * {@inheritdoc}
     */
    public function apply(ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = [])
    {
        if (!isset($context['filters']) || !\is_array($context['filters'])) {
            return;
        }
        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $query, $resourceClass, $operationName, $context);
        }
    }


    protected function normalizePropertyName($property)
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map([$this->nameConverter, 'normalize'], explode('.', (string)$property)));
    }

    protected function denormalizePropertyName($property)
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map([$this->nameConverter, 'denormalize'], explode('.', (string)$property)));
    }


    protected function getProperties(): ?array
    {
        return $this->properties;
    }
}
