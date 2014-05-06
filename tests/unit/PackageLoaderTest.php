<?php

class PackageLoaderTest extends \Codeception\TestCase\Test
{
    /**
     * @var \CodeGuy
     */
    protected $codeGuy;

    /**
     * @var \AnnotateCms\Packages\Loaders\PackageLoader
     */
    private $packageLoader;

    protected function _before()
    {
        $assetsLoader = $this->getMock('AnnotateCms\\Packages\\Loaders\\AssetsLoader');
        $this->packageLoader = new \AnnotateCms\Packages\Loaders\PackageLoader(DATA_DIR . '/packages', $assetsLoader);
    }

    public function testItListensGoodEvents()
    {
        $this->assertEquals([
            'AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme'
        ], $this->packageLoader->getSubscribedEvents());
    }

    public function testTwitterBootstrapPackageCanBeFound()
    {
        $this->assertInstanceOf('AnnotateCms\\Packages\\Package', $this->packageLoader->getPackage('TwitterBootstrap'));
    }

    /**
     * @expectedException AnnotateCms\Packages\Exceptions\PackageNotFoundException
     * @expectedExceptionMessage Package 'Test' does not exist
     */
    public function testTestPackageCannotBeFound()
    {
        $this->packageLoader->getPackage('Test');
    }

    public function testLoadIsFunctional()
    {
        $this->packageLoader->load();
        $this->assertArrayHasKey('TwitterBootstrap', $this->packageLoader->getPackages());
        $this->assertArrayHasKey('jQuery', $this->packageLoader->getPackages());
    }


    public function testItLoadsDependenciesOnActivatedTheme()
    {

        $this->markAsSkippedIfThemesExtensionMissing();

        $assetsLoader = $this->getMock('AnnotateCms\\Packages\\Loaders\\AssetsLoader');

        $assetsLoader->expects($this->exactly(2))
            ->method('addScripts');

        $assetsLoader->expects($this->once()) // jQuery has not any styles
            ->method('addStyles');

        $this->packageLoader = new \AnnotateCms\Packages\Loaders\PackageLoader(DATA_DIR . '/packages', $assetsLoader);

        $def = [
            'name' => 'TestTheme',
            'version' => 1.0,
            'author' => 'John Doe',
            'scripts' => [
                '@script.js'
            ],
            'styles' => [
                '@style.css'
            ],
            'dependencies' => [
                'TwitterBootstrap' => []
            ],
        ];
        $aDir = '/home/michal/www/cms/fakepath/themes/';
        $theme = new \AnnotateCms\Themes\Theme($def, $aDir);
        $this->packageLoader->onActivateTheme($theme);
        $this->assertTrue($theme->isChecked());
    }

    /**
     * @expectedException \AnnotateCms\Packages\Exceptions\PackageNotFoundException
     * @expectedExceptionMessage Theme cannot be loaded. Package 'Test' does not exist.
     */
    public function testItThrowsExceptionOnUnknownPackage()
    {
        $this->markAsSkippedIfThemesExtensionMissing();

        $def = [
            'name' => 'TestTheme',
            'version' => 1.0,
            'author' => 'John Doe',
            'scripts' => [
                '@script.js'
            ],
            'styles' => [
                '@style.css'
            ],
            'dependencies' => [
                'Test' => []
            ],
        ];
        $aDir = '/home/michal/www/cms/fakepath/themes/';
        $theme = new \AnnotateCms\Themes\Theme($def, $aDir);
        $this->packageLoader->onActivateTheme($theme);
    }

    /**
     * @expectedException \AnnotateCms\Packages\Exceptions\BadPackageVersionException
     * @expectedExceptionMessage Theme cannot be loaded. Theme requires 'TwitterBootstrap' version '4.0'
     */
    public function testItThrowsExceptionOnWrongVersion()
    {
        $this->markAsSkippedIfThemesExtensionMissing();

        $def = [
            'name' => 'TestTheme',
            'version' => 1.0,
            'author' => 'John Doe',
            'scripts' => [
                '@script.js'
            ],
            'styles' => [
                '@style.css'
            ],
            'dependencies' => [
                'TwitterBootstrap' => [
                    'version' => '4.0'
                ]
            ],
        ];
        $aDir = '/home/michal/www/cms/fakepath/themes/';
        $theme = new \AnnotateCms\Themes\Theme($def, $aDir);
        $this->packageLoader->onActivateTheme($theme);

    }

    /**
     * @expectedException \AnnotateCms\Packages\Exceptions\PackageNotFoundException
     */
    public function testGetPackageThrowsExceptionOnUnknownPackage()
    {
        $this->packageLoader->getPackage('Test', '2.0');
    }

    /**
     * @expectedException \AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException
     */
    public function testGetPackageThrowsExceptionOnUnknownVariant()
    {
        $this->packageLoader->getPackage('jQuery', null, 'someVariant');
    }

    /**
     * @expectedException \AnnotateCms\Packages\Exceptions\BadPackageVersionException
     */
    public function testGetPackageThrownExceptionOnBadVersion()
    {
        $this->packageLoader->getPackage('jQuery', 20.56, 'default');
    }

    public function testLoadPackageLoadsPackageAssets()
    {

        $assetsLoader = $this->getMock('AnnotateCms\\Packages\\Loaders\\AssetsLoader');

        $assetsLoader->expects($this->once())
            ->method('addScripts');

        $assetsLoader->expects($this->exactly(0)) // jQuery has not any styles
            ->method('addStyles');

        $this->packageLoader = new \AnnotateCms\Packages\Loaders\PackageLoader(DATA_DIR . '/packages', $assetsLoader);

        $this->packageLoader->loadPackage('jQuery');
    }

    public function testItSkipsCheckedTheme()
    {
        $this->markAsSkippedIfThemesExtensionMissing();

        $def = [
            'name' => 'TestTheme',
            'version' => 1.0,
            'author' => 'John Doe',
            'scripts' => [
                '@script.js'
            ],
            'styles' => [
                '@style.css'
            ],
            'dependencies' => [
                'TwitterBootstrap' => []
            ],
        ];
        $aDir = '/home/michal/www/cms/fakepath/themes/';
        $theme = new \AnnotateCms\Themes\Theme($def, $aDir);
        $theme->setChecked();

        $assetsLoader = $this->getMock('AnnotateCms\\Packages\\Loaders\\AssetsLoader');

        $assetsLoader->expects($this->exactly(0))
            ->method('addScripts');

        $assetsLoader->expects($this->exactly(0)) // jQuery has not any styles
            ->method('addStyles');

        $this->packageLoader->onActivateTheme($theme);
    }

    public function testItSkipsLoadingIfThemeHasNotAnyDependencies()
    {
        $this->markAsSkippedIfThemesExtensionMissing();

        $def = [
            'name' => 'TestTheme',
            'version' => 1.0,
            'author' => 'John Doe',
            'scripts' => [
                '@script.js'
            ],
            'styles' => [
                '@style.css'
            ],
            'dependencies' => [],
        ];
        $aDir = '/home/michal/www/cms/fakepath/themes/';
        $theme = new \AnnotateCms\Themes\Theme($def, $aDir);

        $assetsLoader = $this->getMock('AnnotateCms\\Packages\\Loaders\\AssetsLoader');

        $assetsLoader->expects($this->exactly(0))
            ->method('addScripts');

        $assetsLoader->expects($this->exactly(0)) // jQuery has not any styles
            ->method('addStyles');

        $this->packageLoader->onActivateTheme($theme);
    }

    public function testItSkipsLoadedPackage()
    {
        $assetsLoader = $this->getMock('AnnotateCms\\Packages\\Loaders\\AssetsLoader');
        $this->packageLoader = new \AnnotateCms\Packages\Loaders\PackageLoader(DATA_DIR . '/packages', $assetsLoader);
        $this->packageLoader->loadPackage('jQuery');

        $assetsLoader->expects($this->exactly(0))
            ->method('addScripts');

        $assetsLoader->expects($this->exactly(0)) // jQuery has not any styles
            ->method('addStyles');

        $this->packageLoader->loadPackage('jQuery');
    }

    private function markAsSkippedIfThemesExtensionMissing()
    {
        if (!class_exists('AnnotateCms\\Themes\\Theme')) {
            $this->markTestSkipped('Test skipped because themes extension is not installed');
            return;
        }
    }
}