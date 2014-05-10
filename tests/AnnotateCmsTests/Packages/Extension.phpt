<?php

namespace AnnotateCmsTests\Packages;

use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Loaders\PackageLoader;
use Nette;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';

class ExtensionTest extends TestCase
{

    public function setUp()
    {

    }


    private function createContainer()
    {
        $config = new Nette\Configurator();
        $config->setTempDirectory(TEMP_DIR);
        $config->addConfig(__DIR__ . '/data/config.neon');
        return $config->createContainer();
    }


    public function testFunctional()
    {
        $container = $this->createContainer();
        Assert::true($container->getService('packages.packageLoader') instanceof PackageLoader);
        Assert::true($container->getService('packages.assetsLoader') instanceof AssetsLoader);
    }

}

\run(new ExtensionTest);