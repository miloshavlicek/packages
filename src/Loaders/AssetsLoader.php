<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 17.1.14
 * Time: 21:23
 */

namespace AnnotateCms\Packages\Loaders;


use AnnotateCms\Packages\Package;
use Kdyby\Events\Subscriber;
use Nette\Templating\ITemplate;

class AssetsLoader implements Subscriber
{

    const classname = __CLASS__;

    private $styles = array();

    private $scripts = array();

    /** @var Package[] */
    private $packages = array();

    public function addStyles($styles)
    {
        $this->styles = array_merge($this->styles, $styles);
    }

    public function addScripts($scripts)
    {
        $this->scripts = array_merge($this->scripts, $scripts);
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
        return array(
            "AnnotateCms\\Framework\\Templating\\TemplateFactory::onSetupTemplate"
        );
    }

    public function onSetupTemplate(ITemplate $template)
    {
        $template->styles = $this->styles;
        $template->scripts = $this->scripts;
    }

} 