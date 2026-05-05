# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

[Unreleased]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.7...master
## [Unreleased]

## [1.1.7] - 2025-11-10
[1.1.7]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.6...1.1.7

### Added

- Nothing added

### Changed

- Changed license to OSL 3.0

### Removed


## [1.1.6] - 2024-05-03
[1.1.6]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.5...1.1.6

### Added

- Nothing added

### Changed

- **Use resourceConnection->getTableName to determine core_config_data table name**

  Previously only the db type table name hashing was being applied, not configured table name prefixes.

  For more details, please refer to [issue #16](https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/issues/16).

### Removed

- Nothing removed


## [1.1.5] - 2024-04-29
[1.1.5]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.4...1.1.5

### Added

- Nothing added

### Changed

- **Use getTableName to determine core_config_data table name**

  Previously, the setup data patch would fail due to an unexistant table on instances with a table name prefix.

  For more details, please refer to [issue #16](https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/issues/16).

### Removed

- Nothing removed

## [1.1.4] - 2024-04-18
[1.1.4]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.3...1.1.4

### Added

- **Automatically inject customer/ajax/login into existing luma fallback configurations**

   This release expands on the new default config introduced in 1.1.3 by adding a DataPatch class that
   automatically injects the customer/ajax/login path into existing Luma checkout configurations in the
   database. This means existing stores with customized configurations only need to update the luma-fallback-checkout
   module and run `bin/magento setup:upgrade` to apply the fix.

### Changed

- Nothing changed

### Removed

- Nothing removed

## [1.1.3] - 2024-04-12
[1.1.3]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.2...1.1.3

### Added
- **Add customer/ajax/login to default luma fallback configuration**

  Since Hyvä 1.3.6 form-key validation was added to the customer/ajax/login route that is used if guest login
  during checkout is enabled.  
  Since the form-key is missing in Luma, the check is skipped if the current theme is not Hyvä based.  
  For this to correctly work with the Luma checkout them fallback configuration, the fallback theme also needs
  to be configured for the ajax login route.  

  Release 1.1.3 add the route to the default configuration.

  For more information please refer to [merge request #14](https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/merge_requests/14).

### Changed
- **Add leading / to checkout/index fallback route for hyva_checkout compatiblity**
  
  This change is required so the Luma Checkout fallback configuration does not match hyva_checkout routes.  
  With this change it is possible to use Hyvä Checkout and Luma Checkout side by side on separate store views or websites.  

  For more information please refer to [merge request #12](https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/merge_requests/12).

### Removed
- Nothing

## [1.1.2] - 2022-03-09
### Added
- Add paypal payflow callback urls to default fallback configuration.

  Big thanks to Lucas van Staden for the contribution!

  More information can be found in the [merge request #11](https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/merge_requests/11)

### Changed
- Nothing

### Removed
- Nothing

[1.1.2]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.1...1.1.2


## [1.1.1] - 2021-06-17
### Added
- Nothing

### Changed
- Enable theme fallback in default system configuration after installation

### Removed
- Nothing

### [1.1.0] - 2021-06-04
### Added
- Nothing

### Changed
- Extracted the fallback logic to hyva-themes/magento2-theme-fallback.

  All code is backward compatible with one exception: the config XML paths changed.
  Configuration settings stored in the core_config_data table are migrated automatically, but settings 
  stored in `app/etc/config.php` will need to be migrated manually.
  Please read the **Upgrading** section in the readme for more details.

### Removed
- All the fallback logic. This module now only is responsible for providing the fallback configuration.

### [1.0.3] - 2021-04-10
### Added
- This Changelog

### Changed
- pluginOrder increased to 9999 to prevent conflicts with other modules

### Removed
- none

# Ealier releases
#### [1.0.2] - 2021-02-26
#### [1.0.1] - 2021-01-15
#### [1.0.0] - 2020-12-08

[1.1.2]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.1...1.1.2
[1.1.1]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.1.0...1.1.1
[1.1.0]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.0.3...1.1.0
[1.0.3]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.0.2...1.0.3
[1.0.2]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.0.1...1.0.2
[1.0.1]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/compare/1.0.0...1.0.1
[1.0.0]: https://gitlab.hyva.io/hyva-themes/magento2-luma-checkout/-/tags/1.0.0
