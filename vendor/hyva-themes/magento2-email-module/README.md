# hyva-themes/magento2-email-module

Luma style Emails for Hyvä Themes because currently Hyvä does not style emails.

## What does it do?

This module reactivates the luma CSS functionality for emails in hyva-based themes.
The `email.less` file should go in your active Hyvä theme, into the directory `web/css/`.

Note this is different from the luma-checkout or the theme-fallback modules, that actually use a Luma theme instead of Hyvä. 
 
## Installation
  
1. Install via composer
   Note: both repositories need to be configured until the package and its dependency are available through packagist.
   ```
   composer config repositories.hyva-themes/magento2-email-module git git@gitlab.hyva.io:hyva-themes/magento2-email-module
   composer require hyva-themes/magento2-email-module
   ```
2. Enable module
   ```
   bin/magento setup:upgrade
   ```

## License

This project is licensed under the **Open Software License (OSL 3.0)**.

* **Copyright:** This product includes software developed by Magento, Inc. (https://github.com/magento/magento2) and distributed under the Open Source License (OSL) v3.0. Other source files Copyright © 2020-present Hyvä Themes (https://www.hyva.io). All rights reserved.
* **License Text:** The full text of the OSL 3.0 license can be found in the `LICENSE.txt` file within this package, and is also available online at [http://opensource.org/licenses/osl-3.0.php](http://opensource.org/licenses/osl-3.0.php).

