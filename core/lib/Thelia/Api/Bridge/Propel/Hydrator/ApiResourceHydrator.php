<?php

namespace Thelia\Api\Bridge\Propel\Hydrator;

class ApiResourceHydrator
{

    public static function transformModelToResource(object $propelModel, string $resourceClass): object
    {
        $apiResource = new $resourceClass;
        foreach (get_class_methods($apiResource) as $methodName) {
            if (!str_starts_with($methodName, 'set')) {
                continue;
            }
            $propelGetter = 'get' . ucfirst(substr($methodName, 3));

            if (!method_exists($propelModel, $propelGetter)) {
                continue;
            }
            $apiResource->$methodName($propelModel->$propelGetter());
        }
        return $apiResource;
    }

}
