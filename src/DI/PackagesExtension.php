<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 11.1.14
 * Time: 19:39
 */

namespace AnnotateCms\Packages\DI;


use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Loaders\PackageLoader;
use Kdyby\Events\DI\EventsExtension;
use Nette\DI\CompilerExtension;

class PackagesExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix("packageLoader"))
            ->setClass(PackageLoader::classname)
            ->addTag(EventsExtension::SUBSCRIBER_TAG);

        $builder->addDefinition($this->prefix("assetsLoader"))
            ->setClass(AssetsLoader::classname)
            ->addTag(EventsExtension::SUBSCRIBER_TAG);
    }


}