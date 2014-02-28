<?php
/**
 * Created by PhpStorm.
 * User: michal
 * Date: 16.2.14
 * Time: 18:23
 */
namespace AnnotateCms\Packages;

interface IAsset
{
	public function getRelativePath($basePath);
}