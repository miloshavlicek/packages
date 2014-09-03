<?php

namespace AnnotateCmsTests\Packages;

use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Package;
use AnnotateCms\Packages\ThemeAsset;
use AnnotateCms\Themes\Theme;
use Latte\Engine;
use Latte\Template;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';



class AssetsLoaderTest extends TestCase
{

	/** @var  AssetsLoader */
	private $assetsLoader;


	public function setUp()
	{
		$this->assetsLoader = new AssetsLoader();
	}


	public function testItImplementsSubscriber()
	{
		Assert::true($this->assetsLoader instanceof \Kdyby\Events\Subscriber);
	}


	public function testAddPackageAppendsPackages()
	{
		$package = new Package('TestPackage', 0.1, NULL, NULL, NULL, NULL);
		Assert::equal(0, count($this->assetsLoader->getPackages()));
		$this->assetsLoader->addPackage($package);
		Assert::equal(1, count($this->assetsLoader->getPackages()));
		Assert::true(in_array($package, $this->assetsLoader->getPackages()));
	}


	public function testAddStylesMergeAddedWithExistingArray()
	{
		Assert::equal([], $this->assetsLoader->getStyles());
		$styles = [
			'style.css',
		];
		$this->assetsLoader->addStyles($styles);
		Assert::equal($styles, $this->assetsLoader->getStyles());
		$anotherStyles = [
			'another.css'
		];
		$this->assetsLoader->addStyles($anotherStyles);
		Assert::equal(array_merge($styles, $anotherStyles), $this->assetsLoader->getStyles());
	}


	public function testAddScriptsMergeAddedWithExistingArray()
	{
		Assert::equal([], $this->assetsLoader->getScripts());
		$scripts = [
			'script.js',
		];
		$this->assetsLoader->addScripts($scripts);
		Assert::equal($scripts, $this->assetsLoader->getScripts());
		$anotherScripts = [
			'another.js'
		];
		$this->assetsLoader->addScripts($anotherScripts);
		Assert::equal(array_merge($scripts, $anotherScripts), $this->assetsLoader->getScripts());
	}


	public function testItListensGoodEvents()
	{
		Assert::equal(
			[
				'AnnotateCms\\Templating\\TemplateFactory::onSetupTemplate',
				'AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme',
			],
			$this->assetsLoader->getSubscribedEvents()
		);
	}


	public function testItAddsThemesFiles()
	{
		if (!class_exists('AnnotateCms\\Themes\\Theme')) {
			Tester\Environment::skip('Test skipped because themes extension is not installed');

			return;
		}
		$def = [
			'name'         => 'TestTheme',
			'version'      => 1.0,
			'author'       => 'John Doe',
			'scripts'      => [
				'@script.js'
			],
			'styles'       => [
				'@style.css'
			],
			'dependencies' => [],
		];
		$aDir = '/home/michal/www/cms/fakepath/themes/';
		$theme = new Theme($def, $aDir);
		$this->assetsLoader->onActivateTheme($theme);
		$styles = $this->assetsLoader->getStyles();
		$scripts = $this->assetsLoader->getScripts();

		$cssAsset = new ThemeAsset($theme, '@style.css');
		$jsAsset = new ThemeAsset($theme, '@script.js');

		Assert::equal([$cssAsset], $styles);
		Assert::equal([$jsAsset], $scripts);
	}


	public function testItAddsTemplateVariablesOnSetupTemplate()
	{
		$filters = [];
		$template = new Template([], $filters, new Engine, 'template');
		$this->assetsLoader->onSetupTemplate($template);
		Assert::equal([], $template->styles);
		Assert::equal([], $template->scripts);
	}

}



\run(new AssetsLoaderTest);
