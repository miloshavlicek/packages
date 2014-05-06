<?php

class AssetsLoaderTest extends \Codeception\TestCase\Test
{

    /** @var  \AnnotateCms\Packages\Loaders\IAssetsLoader */
    private $assetsLoader;

    /**
     * @var \CodeGuy
     */
    protected $codeGuy;

    protected function _before()
    {
        $this->assetsLoader = new \AnnotateCms\Packages\Loaders\AssetsLoader();
    }

    public function testItImplementsSubscriber()
    {
        $this->assertTrue($this->assetsLoader instanceof \Kdyby\Events\Subscriber);
    }

    public function testAddPackageAppendsPackages()
    {
        $package = new \AnnotateCms\Packages\Package('TestPackage', 0.1, null, null, null, null);
        $this->assertEquals(0, count($this->assetsLoader->getPackages()));
        $this->assetsLoader->addPackage($package);
        $this->assertEquals(1, count($this->assetsLoader->getPackages()));
        $this->assertTrue(in_array($package, $this->assetsLoader->getPackages()));
    }

    public function testAddStylesMergeAddedWithExistingArray()
    {
        $this->assertEquals([], $this->assetsLoader->getStyles());
        $styles = [
            'style.css',
        ];
        $this->assetsLoader->addStyles($styles);
        $this->assertEquals($styles, $this->assetsLoader->getStyles());
        $anotherStyles = [
            'another.css'
        ];
        $this->assetsLoader->addStyles($anotherStyles);
        $this->assertEquals(array_merge($styles, $anotherStyles), $this->assetsLoader->getStyles());
    }

    public function testAddScriptsMergeAddedWithExistingArray()
    {
        $this->assertEquals([], $this->assetsLoader->getScripts());
        $scripts = [
            'script.js',
        ];
        $this->assetsLoader->addScripts($scripts);
        $this->assertEquals($scripts, $this->assetsLoader->getScripts());
        $anotherScripts = [
            'another.js'
        ];
        $this->assetsLoader->addScripts($anotherScripts);
        $this->assertEquals(array_merge($scripts, $anotherScripts), $this->assetsLoader->getScripts());
    }

    public function testItListensGoodEvents()
    {
        $this->assertEquals([
            'AnnotateCms\\Framework\\Templating\\TemplateFactory::onSetupTemplate',
            'AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme',
        ], $this->assetsLoader->getSubscribedEvents());
    }

    public function testItAddsThemesFiles()
    {
        if (!class_exists('AnnotateCms\\Themes\\Theme')) {
            $this->markTestSkipped('Test skipped because themes extension is not installed');
            return;
        }
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
        $this->assetsLoader->onActivateTheme($theme);
        $styles = $this->assetsLoader->getStyles();
        $scripts = $this->assetsLoader->getScripts();

        $cssAsset = new \AnnotateCms\Packages\ThemeAsset($theme, '@style.css');
        $jsAsset = new \AnnotateCms\Packages\ThemeAsset($theme, '@script.js');

        $this->assertEquals([$cssAsset], $styles);
        $this->assertEquals([$jsAsset], $scripts);
    }

    public function testItAddsTemplateVariablesOnSetupTemplate()
    {
        $template = new \Nette\Bridges\ApplicationLatte\Template(new \Latte\Engine());
        $this->assetsLoader->onSetupTemplate($template);
        $this->assertEquals([], $template->styles);
        $this->assertEquals([], $template->scripts);
    }


}