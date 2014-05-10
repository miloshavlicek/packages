<?php

namespace AnnotateCmsTests\Packages;

use AnnotateCms\Packages\Asset;
use AnnotateCms\Packages\Package;
use AnnotateCms\Packages\ThemeAsset;
use AnnotateCms\Themes\Theme;
use Tester\Assert;
use Tester;


require_once __DIR__ . '/../bootstrap.php';

class AssetsTest extends TestCase
{

    public function setUp()
    {

    }


    public function testAssetReturnsCorrectPath()
    {
        $package = new Package(
            'Package',
            '2.0',
            [
                'default' => []
            ],
            [],
            '/adir/to/package',
            '/package'
        );
        $asset = new Asset($package, '@css/file.css');
        Assert::same('/basepath/package/css/file.css', $asset->getRelativePath('/basepath'));
    }


    public function testThemeAssetReturnsCorrectPath()
    {
        if (!class_exists('AnnotateCms\\Themes\\Theme')) {
            Tester\Environment::skip('Test skipped because themes extension is not installed');
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
            'dependencies' => [
                'TwitterBootstrap' => []
            ],
        ];
        $aDir = 'fakepath/themes/TestTheme/';

        $theme = new Theme($def, $aDir);
        $asset = new ThemeAsset($theme, '@js/file.js');
        Assert::same('/cms/fakepath/themes/TestTheme/js/file.js', $asset->getRelativePath('/cms'));
    }


}

\run(new AssetsTest);