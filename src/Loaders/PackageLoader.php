<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 11.1.14
 * Time: 19:38
 */

namespace AnnotateCms\Packages\Loaders;

use AnnotateCms\Framework\Diagnostics\CmsPanel;
use AnnotateCms\Packages\Asset;
use AnnotateCms\Packages\Exceptions\BadPackageVersionException;
use AnnotateCms\Packages\Exceptions\PackageNotFoundException;
use AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException;
use AnnotateCms\Packages\Package;
use AnnotateCms\Themes\Theme;
use Kdyby\Events\Subscriber;
use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\Diagnostics\Dumper;
use Nette\Utils\Finder;

if (!defined("PACKAGES_DIR")) {
    define("PACKAGES_DIR", APP_DIR . DS . "addons" . DS . "packages" . DS);
}

class PackageLoader implements Subscriber
{

    const classname = __CLASS__;

    /** @var Package[] */
    private $packages = array();
    /**
     * @var AssetsLoader
     */
    private $assetsLoader;

    private $loadedPackages = array();

    function __construct(AssetsLoader $assetsLoader)
    {
        $this->load();
        $this->assetsLoader = $assetsLoader;
    }

    public function load()
    {

        $adapter = new NeonAdapter();

        foreach (Finder::findFiles("*.package.neon")->from(PACKAGES_DIR) as $path => $file) {
            /** @var $file \SplFileInfo */
            $neon = $adapter->load($path);
            $this->mergeVariants($neon);
            $aDir = dirname($path);
            $rDir = str_replace(ROOT_DIR, "/", $aDir);
            $dependencies = isset($neon["dependencies"]) ? $neon["dependencies"] : null;
            $this->packages[$neon["name"]] = new Package($neon["name"], $neon["version"], $neon["variants"], $dependencies, $aDir, $rDir);
        }

    }

    private function mergeVariants(&$neon)
    {
        foreach ($neon["variants"] as $name => $variant) {
            if (isset($variant["_extends"])) {
                $extendsName = $variant["_extends"];
                if (!isset($neon["variants"][$extendsName])) {
                    throw new \RuntimeException("Cannot extend package variant '$name'. Undefined package variant '$extendsName'");
                }
                $extends = $neon["variants"][$extendsName];
                if (!isset($variant["styles"])) {
                    $variant["styles"] = array();
                }
                if (!isset($variant["scripts"])) {
                    $variant["scripts"] = array();
                }
                if (!isset($extends["styles"])) {
                    $extends["styles"] = array();
                }
                if (!isset($extends["scripts"])) {
                    $extends["scripts"] = array();
                }

                foreach ($extends["styles"] as $extendsStyle) {
                    array_unshift($variant["styles"], $extendsStyle);
                }

                foreach ($extends["scripts"] as $extendsScript) {
                    array_unshift($variant["scripts"], $extendsScript);
                }

                $neon["variants"][$name]["styles"] = $variant["styles"];
                $neon["variants"][$name]["scripts"] = $variant["scripts"];
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
            if (!$package->isChecked()) {
                foreach ($package->getDependencies() as $dep_name => $info) {
                    $dep_version = isset($info->version) ? $info->version : null;
                    $variant = isset($info->variant) ? $info->variant : "default";
                    $this->loadPackage($dep_name, $dep_version, $variant);
                }
                $package->setChecked();
            }
        } else {
            $package->setChecked();
        }
        $this->loadPackageAssets($packageVariant, $package);

        $this->loadedPackages[] = array(
            "name" => $name,
            "version" => $version,
            "variant" => $packageVariant,
            "dependencies" => $package->getDependencies()
        );

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

    public function getSubscribedEvents()
    {
        return array(
            "AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme"
        );
    }

    public function onActivateTheme(Theme $theme)
    {
        if ($theme->isChecked()) {
            return;
        }

        if (!$theme->hasDependencies()) {
            return;
        }

        foreach ($theme->getDependencies() as $name => $info) {
            $version = isset($info["version"]) ? $info["version"] : null;
            $variant = isset($info["variant"]) ? $info["variant"] : "default";

            try {
                $this->loadPackage($name, $version, $variant);
            } catch (PackageNotFoundException $e) {
                throw new PackageNotFoundException("Theme cannot be loaded. Package '$name' does not exist.", 0, $e);
            } catch (BadPackageVersionException $e) {
                throw new BadPackageVersionException("Theme cannot be loaded. Theme requires '$name' version $version", 0, $e);
            }
        }
        $theme->setChecked();

        $this->addDebugSection();
    }

    /**
     * @param $packageVariant
     * @param $package
     */
    private function loadPackageAssets($packageVariant, Package $package)
    {
        $variants = $package->getVariants();
        $requiredVariant = $variants[$packageVariant];

        if (isset($requiredVariant["scripts"])) {
            $scripts = array();
            foreach ($requiredVariant["scripts"] as $script) {
                $scripts[] = new Asset($package, $script);
            }
            $this->assetsLoader->addScripts($scripts);
        }

        if (isset($requiredVariant["styles"])) {
            $styles = array();
            foreach ($requiredVariant["styles"] as $style) {
                $styles[] = new Asset($package, $style);
            }
            $this->assetsLoader->addStyles($styles);
        }
    }

    private function addDebugSection()
    {
        $packages = $this->loadedPackages;
        CmsPanel::$sections[] = function () use ($packages) {
            $html = "<h2>Loaded Packages:</h2>";
            $html .= "<div><table>";
            $html .= "<thead><tr><th>Name</th><th>Version</th><th>Variant</th><th>Deps</th></tr></thead>";
            foreach ($packages as $package) {
                $html .= "<tr><td>" . $package["name"] . "</td><td>" . $package["version"] . "</td><td>" . $package["variant"] . "</td><td>" . Dumper::toHtml($package["dependencies"], array(Dumper::COLLAPSE => true)) . "</td></tr>";
            }
            $html .= "</table></div>";
            return $html;
        };
    }

} 