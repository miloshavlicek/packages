<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 17.1.14
 * Time: 23:35
 */

namespace AnnotateCms\Packages;


class Asset implements IAsset
{

	/** @var  Package */
	private $package;
	private $fileName;


	function __construct(Package $package, $fileName)
	{
		$this->package = $package;
		$this->fileName = $fileName;
	}


	public function getRelativePath($basePath)
	{
		return str_replace("@", $basePath . $this->package->getRelativePath(), $this->fileName);
	}


} 