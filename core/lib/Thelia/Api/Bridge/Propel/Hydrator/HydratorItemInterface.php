<?php

namespace Thelia\Api\Bridge\Propel\Hydrator;

interface HydratorItemInterface
{
    /**
     * @param object $propelModel
     * @param object $apiResource
     * @param string $resourceClass
     * @return mixed
     * This function transform a propelModel into a proper data transfer object
     */
    public function hydrateItem(object $propelModel, object $apiResource, string $resourceClass);

}
