<?php


namespace AnnotateCms\Packages;


use AnnotateCms\Themes\Theme;

class ThemeAsset implements IAsset
{

	/** @var  Theme */
	private $theme;
	private $fileName;


	function __construct(Theme $theme, $fileName)
	{
		$this->theme = $theme;
		$this->fileName = $fileName;
	}


	public function getRelativePath($basePath)
	{
		return str_replace("@", $basePath . "/" . $this->theme->getRelativePath(), $this->fileName);
	}


} 