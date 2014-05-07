<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 17.1.14
 * Time: 21:23
 */

namespace AnnotateCms\Packages\Loaders;


use AnnotateCms\Packages\Package;
use AnnotateCms\Packages\ThemeAsset;
use AnnotateCms\Themes\Theme;
use Kdyby\Events\Subscriber;
use Nette\Bridges\ApplicationLatte\Template;

class AssetsLoader implements Subscriber
{

    const classname = __CLASS__;

    private $styles = [];

    private $scripts = [];

    /** @var Package[] */
    private $packages = [];


    public function getPackages()
    {
        return $this->packages;
    }


    public function addPackage(Package $package)
    {
        $this->packages[] = $package;
    }


    public function getStyles()
    {
        return $this->styles;
    }


    public function getScripts()
    {
        return $this->scripts;
    }


    public function getSubscribedEvents()
    {
        return [
            "AnnotateCms\\Templating\\TemplateFactory::onSetupTemplate",
            "AnnotateCms\\Themes\\Loaders\\ThemesLoader::onActivateTheme",
        ];
    }


    public function onActivateTheme(Theme $theme)
    {
        $styles = $theme->getStyles();
        $stylesAssets = [];
        foreach ($styles as $style) {
            $stylesAssets[] = new ThemeAsset($theme, $style);
        }
        $this->addStyles($stylesAssets);

        $scripts = $theme->getScripts();
        $scriptsAssets = [];
        foreach ($scripts as $script) {
            $scriptsAssets[] = new ThemeAsset($theme, $script);
        }
        $this->addScripts($scriptsAssets);
    }


    public function addStyles($styles)
    {
        $this->styles = array_merge($this->styles, $styles);
    }


    public function addScripts($scripts)
    {
        $this->scripts = array_merge($this->scripts, $scripts);
    }


    public function onSetupTemplate(Template $template)
    {
        $template->styles = $this->styles;
        $template->scripts = $this->scripts;
    }

} 