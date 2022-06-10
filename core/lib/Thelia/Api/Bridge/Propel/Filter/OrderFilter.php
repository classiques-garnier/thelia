<?php

namespace Thelia\Api\Bridge\Propel\Filter;


use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class OrderFilter extends AbstractFilter
{


    public const DIRECTION_ASC = 'ASC';
    public const DIRECTION_DESC = 'DESC';
    public const DEFAULT_DIRECTION = self::DIRECTION_ASC;

    /**
     * @param NameConverterInterface $nameConverter
     * @param RequestStack $requestStack
     * @param string $orderParameterName
     * @param array|null $properties
     */
    public function __construct(
        protected NameConverterInterface $nameConverter,
        protected RequestStack           $requestStack,
        private string                   $orderParameterName = 'order',
        protected ?array                 $properties = null
    )
    {
        parent::__construct($nameConverter, $requestStack, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();

        foreach ($properties as $property => $propertyOptions) {
            $propertyName = $this->normalizePropertyName($property);
            $description[sprintf('%s[%s]', $this->orderParameterName, $propertyName)] = [
                'property' => $propertyName,
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        strtolower(self::DIRECTION_ASC),
                        strtolower(self::DIRECTION_DESC),
                    ],
                ],
            ];
        }
        return $description;
    }


    protected function filterProperty(string $property, $value, ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = []): void
    {
        if ($property !== $this->orderParameterName) {
            return;
        }
        $field = array_key_first($value);
        $direction = $this->normalizeValue($value[$field]);
        
        $query->orderBy($field, $direction);
    }

    private function normalizeValue($value): ?string
    {
        if (empty($value) && null) {
            // fallback to default direction
            $value = self::DEFAULT_DIRECTION;
        }

        $value = strtoupper($value);
        if (!\in_array($value, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
            return null;
        }
        return $value;
    }
}

