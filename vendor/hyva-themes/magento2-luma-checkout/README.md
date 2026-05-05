# magento2-luma-checkout

Luma Fallback Checkout for Hyvä Themes

## What does it do?

It allows to use the standard luma checkout or define another theme path for checkout when using the Hyva Theme
 
## Installation
  
1. Install via composer
   Note: both repositories need to be configured until the package and its dependency are available through packagist.
   ```
   composer config repositories.hyva-themes/magento2-luma-checkout git git@gitlab.hyva.io:hyva-themes/magento2-luma-checkout.git
   composer config repositories.hyva-themes/magento2-theme-fallback git git@gitlab.hyva.io:hyva-themes/magento2-theme-fallback.git
   composer require hyva-themes/magento2-luma-checkout
   ```
2. Enable module
   ```
   bin/magento setup:upgrade
   ```
   
## Upgrading

After upgrading from release 1.0.3 or before be sure to run
```
bin/magento setup:upgrade
```
This will ensure configuration settings stored in the database are properly migrated to the `Hyva_ThemeFallback`.

Should the configuration settings be locked in `app/etc/config.php`, you have to migrate all paths in the
section `hyva_luma_checkout` manually to the path `hyva_theme_fallback`.  
The full paths that need to be migrated are:
```
'hyva_luma_checkout/general/enable'          => 'hyva_theme_fallback/general/enable'
'hyva_luma_checkout/general/theme_full_path' => 'hyva_theme_fallback/general/theme_full_path'
```


## Magento backend configuration

1. ```HYVA THEMES->Theme Fallback->General Settings->Enable```
    
    The configutation path is ```hyva_theme_fallback/general/enable```


2. ```HYVA THEMES->Theme Fallback->General Settings->Theme full path```

    The configutation path is ```hyva_theme_fallback/general/theme_full_path```
    
    default `frontend/Magento/luma`

3. ```HYVA THEMES->Theme Fallback->General Settings->The list of URL's parts```
   
   The configuration path is `hyva_theme_fallback/general/list_part_of_url`
   
   The default values are `checkout/index`, `paypal/express/review` and `paypal/express/saveShippingMethod`.
   If they are changed, checking "Use system values" will restore the default settings.


## How does it work?

This module depends on `hyva-themes/magento2-theme-fallback` and only supplies the configuration. 

## License

Copyright © 2020-present Hyvä Themes.

Each source file included in this distribution is licensed under OSL 3.0.

http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
Please see LICENSE.txt for the full text of the OSL 3.0 license.