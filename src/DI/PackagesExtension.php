<?php

namespace AnnotateCms\Packages\DI;


use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Loaders\PackageLoader;
use Kdyby\Events\DI\EventsExtension;
use Nette\DI\CompilerExtension;


class PackagesExtension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->getDefaults());
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('packageLoader'))
				->setClass(PackageLoader::CLASSNAME, [
					'packagesDir' => $config['directory'],
					'rootDir' => $builder->expand('%appDir%') . '/../'
				])
				->addTag(EventsExtension::SUBSCRIBER_TAG);

		$builder->addDefinition($this->prefix('assetsLoader'))
				->setClass(AssetsLoader::CLASSNAME)
				->addTag(EventsExtension::SUBSCRIBER_TAG);
	}


	private function getDefaults()
	{
		return [
			'directory' => '%appDir%/addons/packages/',
		];
	}

}
