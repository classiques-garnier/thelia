<?php

namespace Thelia\Api\Bridge\Propel\Filter;


use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class SearchFilter extends AbstractFilter
{
    public const STRATEGY_EXACT = 'exact';
    public const STRATEGY_PARTIAL = 'partial';
    public const STRATEGY_END = 'end';
    public const STRATEGY_START = 'start';

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        foreach ($properties as $property => $unused) {
            $propertyName = $this->normalizePropertyName($property);
            $description[$propertyName] = [
                'property' => $propertyName,
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }


    /**
     * @param string $property
     * @param string $value
     * @param ModelCriteria $query
     * @param string|null $resourceClass
     * @param string|null $operationName
     * @param array $context
     * @return void
     * This filter is case insenstive at the moment.
     */
    protected function filterProperty(string $property, $value, ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = [])
    {
        if (!\array_key_exists($property, $this->properties)) {
            return;
        }
        $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

        $filterMethod = 'filterBy' . ucfirst($property);
        if (!method_exists($query, $filterMethod)) {
            return;
        }
        switch ($strategy) {
            case (self::STRATEGY_PARTIAL):
                $query->$filterMethod('%' . $value . '%', Criteria::LIKE);
                break;
            case (self::STRATEGY_EXACT):
                $query->$filterMethod($value);
                break;
            case (self::STRATEGY_END):
                $query->$filterMethod($value . '%', CRITERIA::LIKE);
                break;
            case (self::STRATEGY_START):
                $query->$filterMethod('%' . $value, CRITERIA::LIKE);
                break;
        }
    }
}

