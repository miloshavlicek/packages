<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 11.1.14
 * Time: 19:38
 */

namespace AnnotateCms\Packages\Loaders;

use AnnotateCms\Packages\Exceptions\BadPackageVersionException;
use AnnotateCms\Packages\Exceptions\PackageNotFoundException;
use AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException;
use AnnotateCms\Packages\Package;
use Nette\ArrayHash;
use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

if (!defined("PACKAGES_DIR")) {
    define("PACKAGES_DIR", APP_DIR . "packages" . DS);
}

class PackageLoader
{

    const classname = __CLASS__;

    private $packages = array();

    function __construct()
    {
        $this->load();
    }

    public function load()
    {

        $adapter = new NeonAdapter();

        foreach (Finder::findFiles("*.package.neon")->from(PACKAGES_DIR) as $path => $file) {
            /** @var $file \SplFileInfo */
            $neon = ArrayHash::from($adapter->load($path));
            $this->mergeVariants($neon);
            $aDir = dirname($path);
            $rDir = str_replace(PACKAGES_DIR, "/", $aDir);
            $dependencies = isset($neon->dependecies) ? $neon->dependencies : null;
            $this->packages[$neon->name] = new Package($neon->name, $neon->version, $neon->variants, $dependencies, $aDir, $rDir);
        }

    }

    private function mergeVariants(&$neon)
    {
        foreach ($neon->variants as $name => $variant) {
            if (isset($variant->_extends)) {
                if (!isset($neon->variants[$variant->_extends])) {
                    throw new \RuntimeException("Cannot extend package variant '$name'. Undefined package variant '$variant->_extends'");
                }
                if (!isset($variant["styles"])) {
                    $variant["styles"] = array();
                }
                if (!isset($variant["scripts"])) {
                    $variant["scripts"] = array();
                }
                if (!isset($neon->variants[$variant->_extends]["styles"])) {
                    $neon->variants[$variant->_extends]["styles"] = array();
                }
                if (!isset($neon->variants[$variant->_extends]["scripts"])) {
                    $neon->variants[$variant->_extends]["scripts"] = array();
                }
                $variant["styles"] = array_merge((array)$neon->variants[$variant->_extends]["styles"], (array)$variant["styles"]);
                $variant["scripts"] = array_merge((array)$neon->variants[$variant->_extends]["scripts"], (array)$variant["scripts"]);
                unset($variant["_extends"]);
            }
        }
    }

    public function loadPackage($name, $version = null, $packageVariant = "default")
    {
        /** @var Package $package */
        $package = $this->getPackage($name, $version, $packageVariant);
        if ($package->isLoaded()) {
            return;
        }
        if ($package->getDependencies()) {
            foreach ($package->getDependencies() as $dep_name => $info) {
                $dep_version = isset($info->version) ? $info->version : null;
                $variant = isset($info->variant) ? $info->variant : "default";
                $this->loadPackage($dep_name, $dep_version, $variant);
            }
        }
        $package->setLoaded();
    }

    /**
     * @param        $name
     * @param null $version
     * @param string $variant
     *
     * @throws BadPackageVersionException
     * @throws \AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException
     * @throws \AnnotateCms\Packages\Exceptions\PackageNotFoundException
     * @return Package
     */
    public function getPackage($name, $version = null, $variant = "default")
    {
        if (!isset($this->packages[$name])) {
            throw new PackageNotFoundException("Package '$name' does not exist");
        }

        if (!$this->packages[$name]->hasVariant($variant)) {
            throw new PackageVariantNotFoundException("Package '$name' does not have variant '$variant'");
        }

        /* @var Package */
        $package = $this->packages[$name];

        if ($version && version_compare($package->getVersion(), $version) < 0) {
            throw new BadPackageVersionException("Package '$name' is version {$package->getVersion()},
            but version $version required.");
        }

        return $package;
    }

    private function getFile($file, Package $package)
    {
        $path = $package->getRDir();
        return $this->formatPath($file, $path);
    }

    private function formatPath($path, $base)
    {
        if (Strings::startsWith($path, "http") || Strings::startsWith($path, "//")) {
            return $path;
        }
        if (Strings::startsWith($path, "@")) {
            return str_replace("@", $base . "/", "%basePath%" . $path);
        }

        return $path;
    }


} 