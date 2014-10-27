<?php

namespace AnnotateCmsTests\Packages;

use AnnotateCms\Packages\Asset;
use AnnotateCms\Packages\Package;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';


class AssetsTest extends TestCase
{

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

}


\run(new AssetsTest);
