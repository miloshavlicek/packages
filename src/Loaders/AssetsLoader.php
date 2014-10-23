<?php

namespace AnnotateCms\Packages\Loaders;


use AnnotateCms\Packages\Package;
use Kdyby\Events\Subscriber;
use Nette\Bridges\ApplicationLatte\Template;


class AssetsLoader implements Subscriber
{

	const CLASSNAME = __CLASS__;

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
			'AnnotateCms\\Templating\\TemplateFactory::onSetupTemplate',
		];
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
