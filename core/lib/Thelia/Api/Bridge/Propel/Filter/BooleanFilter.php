<?php

namespace Thelia\Api\Bridge\Propel\Filter;


use Propel\Runtime\ActiveQuery\ModelCriteria;

class BooleanFilter extends AbstractFilter
{

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
                'type' => 'bool',
                'required' => false,
            ];
        }

        return $description;
    }


    private function normalizeValue($value): ?bool
    {
        if (\in_array($value, [true, 'true', '1'], true)) {
            return true;
        }

        if (\in_array($value, [false, 'false', '0'], true)) {
            return false;
        }
        return null;
    }

    protected function filterProperty(string $property, string $value, ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = [])
    {
        $filterMethod = 'filterBy' . ucfirst($property);
        if (!method_exists($query,$filterMethod)) {
            return;
        }
        $query->$filterMethod($this->normalizeValue($value));
    }
}

