<?php

namespace AnnotateCmsTests\Packages;

use AnnotateCms\Packages\Package;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';


class PackageTest extends TestCase
{

	public function setUp()
	{

	}


	public function testPackageReturnsItsName()
	{
		$package = new Package('Test', '3.0', [], [], 'aDir', 'rDir');
		Assert::same('Test', $package->getName());
	}


	public function testPackageReturnsItsNameAndVersion()
	{
		$package = new Package('Test', '3.0', [], [], 'aDir', 'rDir');
		Assert::same('Test 3.0', (string)$package);
	}

}


\run(new PackageTest);
