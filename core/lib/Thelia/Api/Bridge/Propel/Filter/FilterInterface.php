<?php

namespace Thelia\Api\Bridge\Propel\Filter;

use \ApiPlatform\Api\FilterInterface as BaseFilter;
use Propel\Runtime\ActiveQuery\ModelCriteria;

interface FilterInterface extends BaseFilter
{
    public function apply(ModelCriteria $query, ?string $resourceClass, string $operationName = null, array $context = []);
}
