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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Thelia\Api\Bridge\Propel\Extension\FilterExtension;
use Thelia\Core\Service\ConfigCacheService;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Api\Bridge\Propel\Filter\BooleanFilter;


return function (ContainerConfigurator $configurator): void {
    $serviceConfigurator = $configurator->services();

    $serviceConfigurator->defaults()
        ->autowire(false)
        ->autoconfigure(false)
        ->bind('$kernelCacheDir', '%kernel.cache_dir%')
        ->bind('$kernelDebug', '%kernel.debug%')
        ->bind('$kernelEnvironment', '%kernel.environment%')
        ->bind('$sessionSavePath', '%session.save_path%')
        ->bind('$theliaParserLoops', '%Thelia.parser.loops%')
        ->bind('$formDefinition', '%Thelia.parser.forms%')
        ->bind('$propelCollectionExtensions', tagged_iterator('thelia.api.propel.query_extension.collection'))
        ->bind('$propelItemExtensions', tagged_iterator('thelia.api.propel.query_extension.item'))
        ->bind('$itemHydrators', tagged_iterator('thelia.api.propel.hydrator.item'))
        ->bind('$collectionHydrators', tagged_iterator('thelia.api.propel.hydrator.collection'));

    $serviceConfigurator->load('Thelia\\', THELIA_LIB)
        ->exclude(
            [
                THELIA_LIB . '/Command/Skeleton/Module/I18n/*.php',
                THELIA_LIB . '/Config/**/*.php',
            ]
        )->autowire()
        ->autoconfigure();

    $serviceConfigurator->get(FilterExtension::class)->arg('$locator', service('api_platform.filter_locator'));

    if (ConfigQuery::isSmtpEnable()) {
        $dsn = 'smtp://';

        if (ConfigQuery::getSmtpUsername()) {
            $dsn .= ConfigQuery::getSmtpUsername() . ':' . ConfigQuery::getSmtpPassword();
        }

        $dsn .= ConfigQuery::getSmtpHost() . ':' . ConfigQuery::getSmtpPort();
        $configurator->extension('framework', [
            'mailer' => [
                'dsn' => $dsn,
            ],
        ]);
    }

    if (\defined('THELIA_INSTALL_MODE') === false) {
        $modules = ModuleQuery::getActivated();
        /** @var Module $module */
        foreach ($modules as $module) {
            try {
                \call_user_func([$module->getFullNamespace(), 'configureContainer'], $configurator);
                \call_user_func([$module->getFullNamespace(), 'configureServices'], $serviceConfigurator);
            } catch (\Exception $e) {
                if ($_ENV['APP_DEBUG']) {
                    throw $e;
                }
                Tlog::getInstance()->addError(
                    sprintf('Failed to load module %s: %s', $module->getCode(), $e->getMessage()),
                    $e
                );
            }
        }
    }

    $serviceConfigurator->get(ConfigCacheService::class)
        ->public();

//    $services = $configurator->services();
//    $services->set(PropelCollectionProvider::class)
//        ->args([tagged_iterator('thelia.api.propel.query_extension.collection')]);
};
