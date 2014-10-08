Quickstart
==========

This extension provides support for loading css/js packages dynamically. You define a package and require this package in your presenter or component.
Packages extension also manage dependencies of packages so you can load eg. jQueryUI package in your component and jQuery will be automatically loaded before jQueryUI.

Installation
------------

Require this extension by [Composer](http://getcomposer.org)

```sh
$ composer require annotatecms/packages:@dev
```

If you have correct configuration for [annotatecms/extensions-installer](https://github.com/annotatecms/extensions-installer) extension will be registered
automatically. If not, just register extension into configuration:

```yml
extensions:
    packages: AnnotateCms\Packages\DI\PackagesExtension
```

Configuration
-------------

Default directory to find packages is set to `%appDir%/addons/packages`. So this directory must exist. To change directory for packages just update configuration:

```yml
packages:
    directory: %appDir%/packages
```

Template setup
--------------

This @layout.latte structure is recommended to be able to load scripts in components dynamically:

```smarty
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    {capture $html}
</head>
<body>
    {include #content}
    {* place any <body> content here *}
    {/capture}

    {foreach $assetsLoader->getStyles() as $style}
        <link rel="stylesheet" href="{$style->getRelativePath($basePath)}"/>
    {/foreach}
    {$html|noescape}

    {foreach $assetsLoader->getScripts() as $script}
        <script src="{$script->getRelativePath($basePath)}"></script>
    {/foreach}
</body>
</html>

```

The `capture` macro will take all content. Thanks to this all scripts and css will be loaded dynamically even if you require them from component.

Pass assetsLoader to the template
---------------------------------

Edit your presenter:

```php

use AnnotateCms\Packages\Loaders\AssetsLoader;
use AnnotateCms\Packages\Loaders\PackageLoader;

class Presenter extends Nette\Application\UI\Presenter {

    /** @var AssetsLoader @inject */
    public $assetsLoader;

    /** @var PackageLoader @inject */
    public $assetsLoader;

    protected function startup()
    {
        parent::startup();
        $this->packageLoader->loadPackage('jQueryUI');
    }

    protected function beforeRender()
    {
        $this->template->assetsLoader = $this->assetsLoader;
    }



}
```

When you check your page in browser jQueryUI assets will be loaded after jQuery assets.

Next steps
----------

- [define package](define_package.md)
- [require package](require_package.md) [TODO]
