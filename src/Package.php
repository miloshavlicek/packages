<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 11.1.14
 * Time: 19:36
 */

namespace AnnotateCms\Packages;


class Package
{

    private $loaded = false;
    private $checked = false;
    private $name;
    private $aDir;
    private $rDir;
    private $version;
    private $variants;
    private $dependencies = [];


    public function __construct($name, $version, $variants, $dependencies, $aDir, $rDir)
    {
        $this->name = $name;
        $this->version = $version;
        $this->variants = $variants;
        $this->dependencies = $dependencies;
        $this->aDir = $aDir;
        $this->rDir = $rDir;
    }


    public function isLoaded()
    {
        return $this->loaded;
    }


    public function isChecked()
    {
        return $this->checked;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getVersion()
    {
        return $this->version;
    }


    public function getVariants()
    {
        return $this->variants;
    }


    public function getDependencies()
    {
        return $this->dependencies;
    }


    public function getRelativePath()
    {
        return str_replace("\\", "/", $this->rDir) . "/";
    }


    public function setLoaded()
    {
        $this->loaded = true;
    }


    public function setChecked()
    {
        $this->checked = true;
    }


    public function hasVariant($name)
    {
        if (isset($this->variants[$name]) && $this->variants[$name] != null) {
            return true;
        }

        return false;
    }


    public function __toString()
    {
        return "{$this->name} {$this->version}";
    }


} 