<?php

namespace Thelia\Api\Bridge\Propel\Hydrator;

interface HydratorCollectionInterface
{
    /**
     * @param object $propelModel
     * @param object $apiResource
     * @param string $resourceClass
     * @return mixed
     * This method will be called for each item of the collection
     */
    public function hydrateItemOfCollection(object $propelModel, object $apiResource, string $resourceClass);
}
