<?php

namespace AnnotateCmsTests\Packages;

use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Loaders\PackageLoader;
use AnnotateCms\Themes\Theme;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';


class PackageLoaderTest extends TestCase
{


	public function testItListensGoodEvents()
	{
		Assert::equal(
			[
				'AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme'
			],
			$this->createPackageLoader()->getSubscribedEvents()
		);
	}


	private function createPackageLoader($assetLoaderMock = NULL)
	{
		if ($assetLoaderMock) {
			return new PackageLoader([ROOT_DIR . '/Packages/data/packages'], ROOT_DIR, $assetLoaderMock);
		} else {
			return new PackageLoader([ROOT_DIR . '/Packages/data/packages'], ROOT_DIR, $this->createAssetLoaderMock());
		}
	}


	/**
	 * @return \Mockista\MockInterface|AssetsLoader
	 */
	private function createAssetLoaderMock()
	{
		return $this->mockista->create('AnnotateCms\Packages\Loaders\AssetsLoader');
	}


	public function testTwitterBootstrapPackageCanBeFound()
	{
		Assert::type('AnnotateCms\Packages\Package', $this->createPackageLoader()->getPackage('TwitterBootstrap'));
	}


	public function testTestPackageCannotBeFound()
	{
		Assert::exception(
			function () {
				$this->createPackageLoader()->getPackage('Test');
			},
			'AnnotateCms\Packages\Exceptions\PackageNotFoundException',
			'Package "Test" does not exist'
		);

	}


	public function testLoadIsFunctional()
	{
		$packageLoader = $this->createPackageLoader();
		$packageLoader->load();
		Assert::true(array_key_exists('TwitterBootstrap', $packageLoader->getPackages()));
		Assert::true(array_key_exists('jquery', $packageLoader->getPackages()));
	}


	public function testItLoadsDependenciesOnActivatedTheme()
	{
		$this->markAsSkippedIfThemesExtensionMissing();

		$assetsLoader = $this->createAssetLoaderMock();
		$assetsLoader->expects('addScripts')->twice();
		$assetsLoader->expects('addStyles')->once();

		$packageLoader = $this->createPackageLoader($assetsLoader);

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
		$theme = new Theme($def, $aDir, '/fakepath/themes/');
		$packageLoader->onActivateTheme($theme);
		Assert::true($theme->isChecked());
	}


	private function markAsSkippedIfThemesExtensionMissing()
	{
		if (!class_exists('AnnotateCms\\Themes\\Theme')) {
			Tester\Environment::skip('Test skipped because themes extension is not installed');

			return;
		}
	}


	public function testItThrowsExceptionOnWrongVersion()
	{
		$this->markAsSkippedIfThemesExtensionMissing();

		Assert::exception(
			function () {
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
				$theme = new Theme($def, $aDir, '/fakepath/themes/');
				$this->createPackageLoader()->onActivateTheme($theme);
			},
			'AnnotateCms\Packages\Exceptions\BadPackageVersionException',
			'Theme cannot be loaded. Theme requires "TwitterBootstrap" version "4.0"'
		);
	}


	public function testGetPackageThrowsExceptionOnUnknownPackage()
	{
		Assert::exception(
			function () {
				$this->createPackageLoader()->getPackage('Test', '2.0');
			},
			'AnnotateCms\Packages\Exceptions\PackageNotFoundException'
		);
	}


	public function testGetPackageThrowsExceptionOnUnknownVariant()
	{
		Assert::exception(
			function () {
				$this->createPackageLoader()->getPackage('jquery', NULL, 'someVariant');
			},
			'AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException'
		);
	}


	public function testGetPackageThrownExceptionOnBadVersion()
	{
		Assert::exception(
			function () {
				$this->createPackageLoader()->getPackage('jquery', 20.56, 'default');
			},
			'AnnotateCms\Packages\Exceptions\BadPackageVersionException'
		);
	}


	public function testLoadPackageLoadsPackageAssets()
	{
		$assetsLoader = $this->createAssetLoaderMock();
		$assetsLoader->expects('addScripts')->once();
		$assetsLoader->expects('addStyles')->exactly(0);
		$packageLoader = $this->createPackageLoader($assetsLoader);
		$packageLoader->loadPackage('jquery');
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
		$theme = new Theme($def, $aDir, '/fakepath/themes/');
		$theme->setChecked();
		$assetsLoader = $this->createAssetLoaderMock();
		$assetsLoader->expects('addScripts')->exactly(0);
		$assetsLoader->expects('addStyles')->exactly(0);
		$this->createPackageLoader($assetsLoader)->onActivateTheme($theme);
		$assetsLoader->assertExpectations();
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
		$theme = new Theme($def, $aDir, '/fakepath/themes/');
		$assetsLoader = $this->createAssetLoaderMock();
		$assetsLoader->expects('addScripts')->exactly(0);
		$assetsLoader->expects('addStyles')->exactly(0);
		$this->createPackageLoader($assetsLoader)->onActivateTheme($theme);
		$assetsLoader->assertExpectations();
	}


	public function testItSkipsLoadedPackage()
	{
		$assetsLoader = $this->createAssetLoaderMock();
		$packageLoader = $this->createPackageLoader($assetsLoader);


		$assetsLoader->expects('addScripts')->exactly(1);
		$assetsLoader->expects('addStyles')->exactly(0);

		$packageLoader->loadPackage('jquery');
		$packageLoader->loadPackage('jquery');
		$assetsLoader->assertExpectations();
	}


	public function testBowerPackageLoadedCorrectly()
	{
		$assetsLoader = $this->createAssetLoaderMock();
		$packageLoader = $this->createPackageLoader($assetsLoader);

		$assetsLoader->expects('addScripts')->exactly(2); // bower-package + jquery
		$assetsLoader->expects('addStyles')->exactly(1);

		$packageLoader->loadPackage('bower-package');

		$package = $packageLoader->getPackage('bower-package');

		Assert::equal(['@/css/styles.css'], $package->getVariants()['default']['styles']);
		Assert::equal(['@/js/script.js'], $package->getVariants()['default']['scripts']);
	}

}


\run(new PackageLoaderTest);
