<?php

namespace AnnotateCms\Packages;


class Asset implements IAsset
{

	/** @var Package */
	private $package;

	private $fileName;


	public function __construct(Package $package, $fileName)
	{
		$this->package = $package;
		$this->fileName = $fileName;
	}


	public function getRelativePath($basePath)
	{
		return str_replace('@', $basePath . $this->package->getRelativePath(), $this->fileName);
	}

}
