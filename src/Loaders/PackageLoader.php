<?php

namespace AnnotateCms\Packages\Loaders;

use AnnotateCms\Diagnostics\CmsPanel;
use AnnotateCms\Packages\Asset;
use AnnotateCms\Packages\Exceptions\BadPackageVersionException;
use AnnotateCms\Packages\Exceptions\PackageNotFoundException;
use AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException;
use AnnotateCms\Packages\Package;
use AnnotateCms\Themes\Theme;
use Kdyby\Events\Subscriber;
use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\Utils\Finder;
use Tracy\Dumper;


class PackageLoader implements Subscriber
{

	const CLASSNAME = __CLASS__;

	/** @var Package[] */
	private $packages = [];

	/**
	 * @var AssetsLoader
	 */
	private $assetsLoader;

	private $loadedPackages = [];

	/** @var string */
	private $packagesDir;


	public function __construct($packagesDir, AssetsLoader $assetsLoader)
	{
		$this->packagesDir = $packagesDir;
		$this->assetsLoader = $assetsLoader;
		$this->load();
	}


	public function load()
	{

		$adapter = new NeonAdapter();

		foreach (Finder::findFiles("*.package.neon")->from($this->packagesDir) as $path => $file) {
			/** @var $file \SplFileInfo */
			$neon = $adapter->load($path);
			$this->mergeVariants($neon);
			$aDir = dirname($path);
			$rDir = str_replace(ROOT_DIR, "/", $aDir);
			$dependencies = isset($neon["dependencies"]) ? $neon["dependencies"] : NULL;
			$this->packages[$neon["name"]] = new Package(
				$neon["name"],
				$neon["version"],
				$neon["variants"],
				$dependencies,
				$aDir,
				$rDir
			);
		}

		$this->addDebugSection();

	}


	private function mergeVariants(&$neon)
	{
		foreach ($neon["variants"] as $name => $variant) {
			if (isset($variant["_extends"])) {
				$extendsName = $variant["_extends"];
				if (!isset($neon["variants"][$extendsName])) {
					throw new \RuntimeException(
						"Cannot extend package variant '$name'. Undefined package variant '$extendsName'"
					);
				}
				$extends = $neon["variants"][$extendsName];
				if (!isset($variant["styles"])) {
					$variant["styles"] = [];
				}
				if (!isset($variant["scripts"])) {
					$variant["scripts"] = [];
				}
				if (!isset($extends["styles"])) {
					$extends["styles"] = [];
				}
				if (!isset($extends["scripts"])) {
					$extends["scripts"] = [];
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


	private function addDebugSection()
	{
		$self = $this;
		CmsPanel::$sections[] = function () use ($self) {
			$packages = $self->loadedPackages;
			$html = "<h2>Loaded Packages:</h2>";
			$html .= "<div><table>";
			$html .= "<thead><tr><th>Name</th><th>Version</th><th>Variant</th><th>Deps</th></tr></thead>";
			foreach ($packages as $package) {
				$html .= "<tr><td>" . $package["name"] . "</td><td>" . $package["version"] . "</td><td>" . $package["variant"] . "</td><td>" . Dumper::toHtml(
						$package["dependencies"],
						[Dumper::COLLAPSE => TRUE]
					) . "</td></tr>";
			}
			$html .= "</table></div>";

			return $html;
		};
	}


	public function getSubscribedEvents()
	{
		return [
			"AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme"
		];
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
			$version = isset($info["version"]) ? $info["version"] : NULL;
			$variant = isset($info["variant"]) ? $info["variant"] : "default";

			try {
				$this->loadPackage($name, $version, $variant);
			} catch (PackageNotFoundException $e) {
				throw new PackageNotFoundException("Theme cannot be loaded. Package '$name' does not exist.", 0, $e);
			} catch (BadPackageVersionException $e) {
				throw new BadPackageVersionException(
					"Theme cannot be loaded. Theme requires '$name' version '$version'", 0, $e
				);
			}
		}
		$theme->setChecked();
	}


	public function loadPackage($name, $version = NULL, $packageVariant = "default")
	{
		/** @var Package $package */
		$package = $this->getPackage($name, $version, $packageVariant);
		if ($package->isLoaded()) {
			return;
		}
		if ($package->getDependencies()) {
			if (!$package->isChecked()) {
				foreach ($package->getDependencies() as $dep_name => $info) {
					$dep_version = isset($info->version) ? $info->version : NULL;
					$variant = isset($info->variant) ? $info->variant : "default";
					$this->loadPackage($dep_name, $dep_version, $variant);
				}
				$package->setChecked();
			}
		} else {
			$package->setChecked();
		}
		$this->loadPackageAssets($packageVariant, $package);

		$this->loadedPackages[] = [
			"name"         => $package->getName(),
			"version"      => $package->getVersion(),
			"variant"      => $packageVariant,
			"dependencies" => $package->getDependencies()
		];

		$package->setLoaded();
	}


	/**
	 * @param        $name
	 * @param null   $version
	 * @param string $variant
	 *
	 * @throws BadPackageVersionException
	 * @throws \AnnotateCms\Packages\Exceptions\PackageVariantNotFoundException
	 * @throws \AnnotateCms\Packages\Exceptions\PackageNotFoundException
	 * @return Package
	 */
	public function getPackage($name, $version = NULL, $variant = "default")
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
			throw new BadPackageVersionException(
				"Package '$name' is version {$package->getVersion()},
            but version $version required."
			);
		}

		return $package;
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
			$scripts = [];
			foreach ($requiredVariant["scripts"] as $script) {
				$scripts[] = new Asset($package, $script);
			}
			$this->assetsLoader->addScripts($scripts);
		}

		if (isset($requiredVariant["styles"])) {
			$styles = [];
			foreach ($requiredVariant["styles"] as $style) {
				$styles[] = new Asset($package, $style);
			}
			$this->assetsLoader->addStyles($styles);
		}
	}


	/**
	 * @return \AnnotateCms\Packages\Package[]
	 */
	public function getPackages()
	{
		return $this->packages;
	}

}
