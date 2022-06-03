<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Api\Bridge\Propel\State;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Api\Bridge\Propel\Extension\QueryCollectionExtensionInterface;
use Thelia\Api\Bridge\Propel\Extension\QueryResultCollectionExtensionInterface;
use Thelia\Api\Bridge\Propel\Hydrator\ApiResourceHydrator;
use Thelia\Api\Bridge\Propel\Hydrator\HydratorCollectionInterface;
use Thelia\Api\Resource\PropelResourceInterface;


class PropelCollectionProvider implements ProviderInterface
{
    /**
     * @param QueryCollectionExtensionInterface[] $propelCollectionExtensions
     * @param HydratorCollectionInterface[] $collectionHydrators
     */
    public function __construct(private iterable $propelCollectionExtensions = [], private iterable $collectionHydrators = [])
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $resourceClass = $operation->getClass();

        if (!is_subclass_of($resourceClass, PropelResourceInterface::class)) {
            throw new RuntimeException('Bad provider');
        }

        /** @var ModelCriteria $queryClass */
        $queryClass = $resourceClass::getPropelModelClass() . 'Query';

        /** @var ModelCriteria $query */
        $query = $queryClass::create();

        foreach ($this->propelCollectionExtensions as $extension) {
            $extension->applyToCollection($query, $resourceClass, $operation->getName(), $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operation->getName(), $context)) {
                return $extension->getResult($query, $resourceClass, $operation->getName(), $context);
            }
        }
        $propelModels = iterator_to_array($query->find());
        return array_map(
            function ($propelModel) use ($resourceClass) {
                $apiResource = ApiResourceHydrator::transformModelToResource($propelModel, $resourceClass);
                foreach ($this->collectionHydrators as $collectionHydrator) {
                    $collectionHydrator->hydrateItemOfCollection($propelModel, $apiResource, $resourceClass);
                }
                return $apiResource;
            },
            $propelModels
        );
    }

}
