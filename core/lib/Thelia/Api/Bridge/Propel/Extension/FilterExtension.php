<?php

namespace Thelia\Api\Bridge\Propel\Extension;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Psr\Container\ContainerInterface;
use Thelia\Api\Bridge\Propel\Filter\FilterInterface;

class FilterExtension implements QueryCollectionExtensionInterface
{
    /**
     * @param FilterInterface[] $apiFilters
     */
    public function __construct(private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private ContainerInterface $locator)
    {

    }

    public function applyToCollection(ModelCriteria $query, string $resourceClass, string $operationName = null, array $context = []): void
    {
        $this->apply($query, $resourceClass, $operationName, $context);

    }

    public function apply(ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = []): void
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        /** @var ResourceMetadata|ResourceMetadataCollection */
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName);
        $resourceFilters = $operation->getFilters();

        if (empty($resourceFilters)) {
            return;
        }

        foreach ($resourceFilters as $filterId) {
            $filter = $this->getFilter($filterId);
            if ($filter instanceof FilterInterface) {
                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($query, $resourceClass, $operationName, $context);
            }
        }
    }

    private function getFilter(string $filterId): ?\ApiPlatform\Api\FilterInterface
    {
        if ($this->locator->has($filterId)) {
            return $this->locator->get($filterId);
        }
        return null;
    }
}
