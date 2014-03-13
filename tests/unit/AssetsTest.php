<?php

class AssetsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \CodeGuy
     */
    protected $codeGuy;

    protected function _before()
    {
    }

    protected function _after()
    {
    }


    public function testAssetReturnsCorrectPath()
    {
        $package = new \AnnotateCms\Packages\Package(
            'Package',
            '2.0',
            [
                'default' => []
            ],
            [],
            '/adir/to/package',
            '/package');
        $asset = new \AnnotateCms\Packages\Asset($package, '@css/file.css');
        $this->assertSame('/basepath/package/css/file.css', $asset->getRelativePath('/basepath'));
    }

    public function testThemeAssetReturnsCorrectPath()
    {
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
        $aDir = 'fakepath/themes/TestTheme/';

        $theme = new \AnnotateCms\Themes\Theme($def, $aDir);
        $asset = new \AnnotateCms\Packages\ThemeAsset($theme, '@js/file.js');
        $this->assertSame('/cms/fakepath/themes/TestTheme/js/file.js', $asset->getRelativePath('/cms'));
    }

}