# Hyvä Themes - Magento2 Order Cancellation WebAPI

[![Hyvä Themes](https://hyva.io/media/wysiwyg/logo-compact.png)](https://hyva.io/)

## hyva-themes/magento2-order-cancellation-webapi

This module provides a REST API for the Magento_OrderCancellation module released in Magento 2.4.7.  
Out of the box the module only comes with a GraphQL API (magento/module-order-cancellation-graph-ql), but for merchants not using a headless storefront, a REST API is more appropriate.

![Supported Magento Versions][ico-compatibility]

Compatible with Magento 2.4.7 and higher.  
The module can be installed with older versions of Magento, too, be used by themes that also work with versions of Magento that don't have the OrderCancellation modules.  
In that case, the API will refuse to work.
 
## Installation

```
composer require hyva-themes/magento2-order-cancellation-webapi
bin/magento setup:upgrade
```

## License

Copyright © 2020-present Hyvä Themes.

Each source file included in this distribution is licensed under OSL 3.0.

http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
Please see LICENSE.txt for the full text of the OSL 3.0 license.

## Changelog
Please see [The Changelog](CHANGELOG.md).

[ico-compatibility]: https://img.shields.io/badge/magento-%202.4-brightgreen.svg?logo=magento&longCache=true&style=flat-square
