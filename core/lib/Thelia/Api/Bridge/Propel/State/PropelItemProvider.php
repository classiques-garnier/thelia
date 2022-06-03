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
use Thelia\Api\Bridge\Propel\Extension\QueryItemExtensionInterface;
use Thelia\Api\Bridge\Propel\Hydrator\ApiResourceHydrator;
use Thelia\Api\Bridge\Propel\Hydrator\HydratorItemInterface;
use Thelia\Api\Resource\PropelResourceInterface;

class PropelItemProvider implements ProviderInterface
{
    /**
     * @param QueryItemExtensionInterface[] $propelItemExtensions
     * @param HydratorItemInterface[] $itemHydrators
     */
    public function __construct
    (
        private iterable $propelItemExtensions = [],
        private iterable $itemHydrators = [],
    )
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

        foreach ($this->propelItemExtensions as $extension) {
            $extension->applyToItem($query, $resourceClass, $uriVariables, $operation->getName(), $context);
        }
        foreach ($uriVariables as $key => $value) {
            $filterMethod = 'filterBy' . ucfirst($key);
            $query->$filterMethod($value);
        }

        $propelModel = $query->findOne();
        $apiResource = ApiResourceHydrator::transformModelToResource($propelModel, $resourceClass);
        foreach ($this->itemHydrators as $itemHydrator) {
            $itemHydrator->hydrateItem($propelModel, $apiResource, $resourceClass);
        }

        return $apiResource;
    }
}
