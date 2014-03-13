<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 11.1.14
 * Time: 19:39
 */

namespace AnnotateCms\Packages\DI;


use AnnotateCms\Framework\DI\CompilerExtension;
use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Loaders\PackageLoader;
use Kdyby\Events\DI\EventsExtension;

class PackagesExtension extends CompilerExtension
{

    function getServices()
    {
        return [
            "packageLoader" => [
                "class" => PackageLoader::classname,
                "tags" => [EventsExtension::SUBSCRIBER_TAG],
            ],
            "assetsLoader" => [
                "class" => AssetsLoader::classname,
                "tags" => [EventsExtension::SUBSCRIBER_TAG],
            ],
        ];
    }


    function getFactories()
    {
        // TODO: Implement getFactories() method.
    }


    function  getDefaults()
    {
        // TODO: Implement getDefaults() method.
    }
}