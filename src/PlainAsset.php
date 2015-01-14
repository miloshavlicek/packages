<?php

namespace Annotate\Packages;


class PlainAsset implements IAsset
{

	/** @var string */
	private $fileName;



	public function __construct($fileName)
	{
		$this->fileName = $fileName;
	}



	public function getRelativePath($basePath)
	{
		return $this->fileName;
	}

}
