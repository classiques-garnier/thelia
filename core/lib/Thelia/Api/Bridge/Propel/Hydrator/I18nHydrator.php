<?php

namespace Thelia\Api\Bridge\Propel\Hydrator;

use Thelia\Api\Resource\I18n;
use Thelia\Api\Resource\TranslatableResourceInterface;
use Thelia\Model\LangQuery;

class I18nHydrator implements HydratorItemInterface, HydratorCollectionInterface
{
    public function hydrateItem(object $propelModel, object $apiResource, string $resourceClass)
    {
        if (!(is_subclass_of($resourceClass, TranslatableResourceInterface::class))) {
            return;
        }
        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach ($langs as $lang) {
            /** @var I18n $i18nResource */
            $i18nResource = new ($resourceClass::getI18nResourceClass());

            $i18nResource
                ->setLocale($lang->getLocale());

            $this->setI18nFieldValue($i18nResource, $lang, 'title', $propelModel);
            $this->setI18nFieldValue($i18nResource, $lang, 'chapo', $propelModel);

            $apiResource->addI18n($i18nResource);
        }
    }

    public function hydrateItemOfCollection(object $propelModel, object $apiResource, string $resourceClass)
    {
        return $this->hydrateItem($propelModel, $apiResource, $resourceClass);
    }

    private
    function setI18nFieldValue(I18n $i18nResource, $lang, $field, $propelModel): void
    {
        $virtualColumn = 'lang_' . $lang->getLocale() . '_' . $field;
        $setter = 'set' . ucfirst($field);

        $value = '';

        if (
            $propelModel->hasVirtualColumn($virtualColumn)
            &&
            !empty($propelModel->getVirtualColumn($virtualColumn))
        ) {
            $value = $propelModel->getVirtualColumn($virtualColumn);
        }

        $i18nResource->$setter($value);
    }
}
