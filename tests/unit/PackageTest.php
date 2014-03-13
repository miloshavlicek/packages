<?php
use Codeception\Util\Stub;

class PackageTest extends \Codeception\TestCase\Test
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


    public function testPackageReturnsItsName()
    {
        $package = new \AnnotateCms\Packages\Package('Test', '3.0', [], [], 'aDir', 'rDir');
        $this->assertSame('Test', $package->getName());
    }

    public function testPackageReturnsItsNameAndVersion()
    {
        $package = new \AnnotateCms\Packages\Package('Test', '3.0', [], [], 'aDir', 'rDir');
        $this->assertSame('Test 3.0', (string) $package);
    }

}