# Hyvä Themes - Base Layout Reset Module

[![Hyvä Themes](https://hyva.io/media/wysiwyg/logo-compact.png)](https://hyva.io/)

## hyva-themes/magento2-base-layout-reset

Generates a version of the base layout XML of core Magento modules and bundled extensions without block declarations.

This generated base layout is used instead of the original base layout for Hyvä based themes.

Its purpose is to avoid loading the original base layout, only to reset it with the reset theme. This improves performance on page requests that hit a cold layout cache.

## Installation

```sh
composer require hyva-themes/magento2-base-layout-reset
bin/magento module:enable Hyva_BaseLayoutReset
bin/magento setup:upgrade
```

If your custom theme inherits from `Hyva/default` or `Hyva/default-csp`, no additional steps are required.

## Usage

In Hyvä default-theme versions up to 1.3.18, a parent theme was used to override and remove block layout declarations from module folders.

Starting with version 1.4.0, Hyvä Themes use this module instead, which dynamically generates base layout declarations in `var/hyva-layout-resets/`.

This approach improves:

* Performance, on page requests with a cold layout cache.
* Maintainability, by removing the need for a reset theme.

The base layout resets are generated on the fly.  
It is also possible to trigger generation by running the command 

```sh
bin/magento hyva:base-layout-resets:generate
```

## Configuring the generation folder

You can define a custom absolute path for the generated layout files in `app/etc/env.php` using the `hyva_layout_resets_generation_directory` key.

**Example**
```php
return [
    'hyva_layout_resets_generation_directory' => '/var/www/html/generated/code/hyva-layout-resets/',
    ...
```

## What determines if a theme is a Hyvä Theme

A theme is considered a Hyvä-based theme if its inheritance chain contains a theme with a name starting with `Hyva/`.  
This was always true when using the `Hyva/reset` theme. However, when using the generated base layouts instead, this detection no longer works.

To ensure proper detection, base Hyvä themes must be added to the constructor argument `$hyvaBaseThemes` of `Hyva\BaseLayoutReset\Service\HyvaThemes` via `di.xml`.

In most projects, this is best done within a custom module, but it can also be defined in `app/etc/di.xml`.

## Custom Hyvä base themes

If you maintain a custom Hyvä base theme that directly extends `Hyva/reset`, you can migrate it to use the generated base layout reset.

**NOTE**: *This migration is optional - existing themes can continue using the reset theme without issues.*  

**Migration Steps**

* Add `<update handle="default_hyva"/>` to `Magento_Theme/layout/default.xml`.
* Copy `Magento_Theme/templates/root.phtml` from the reset theme into the Hyvä theme.
* Remove `<parent>Hyva/reset</parent>` from `theme.xml`.
* Update the `theme` table in the database, setting the `parent_id` of your theme to `NULL`.
* Add your theme code to the `hyvaBaseThemes` array in the constructor argument for `Hyva\BaseLayoutReset\Service\HyvaThemes` within `di.xml`.
* Clear the cache.

This module provides commands to help with these steps.

### List Hyvä Theme reset information

To list all Hyvä themes and see their migration status, run:

```sh
bin/magento hyva:base-layout-resets:info
```

### Migrating a custom Hyvä base theme to the generated layout reset

To automatically migrate a Hyvä base theme to the generated layout reset, run: 

```sh
bin/magento hyva:base-layout-resets:migrate Vendor/theme
```

Replace `Vendor/theme` with your theme code.  


**NOTE**: This command will update `app/etc/di.xml`, adding your theme to the `hyvaBaseThemes` argument of
`Hyva\BaseLayoutReset\Service\HyvaThemes`.
If you prefer not to modify `app/etc/di.xml`, you can move this declaration into a `di.xml` file inside your custom module.

*Be aware that many projects do not keep `app/etc/di.xml` in version control, and any changes may be lost. We recommend adding the declaration to a custom module `di.xml` instead.*


## License

This package is licensed under the **Open Software License (OSL 3.0)**.

* **Copyright:** Copyright © 2020-present Hyvä Themes. All rights reserved.
* **License Text:** The full text of the OSL 3.0 license can be found in the `LICENSE.txt` file within this package, and is also available online at [http://opensource.org/licenses/osl-3.0.php](http://opensource.org/licenses/osl-3.0.php).

[ico-compatibility]: https://img.shields.io/badge/magento-%202.4-brightgreen.svg?logo=magento&longCache=true&style=flat-square
