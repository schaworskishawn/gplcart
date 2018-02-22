[![Crowdin](https://d322cqt584bo4o.cloudfront.net/gplcart/localized.svg)](https://crowdin.com/project/gplcart)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gplcart/gplcart/badges/quality-score.png?b=dev)](https://scrutinizer-ci.com/g/gplcart/gplcart/?branch=dev)
[![Build Status](https://travis-ci.org/gplcart/gplcart.svg?branch=dev)](https://travis-ci.org/gplcart/gplcart)

## WARNING. Dev branch in not for production. Please wait until the release of 1.X ##

## About ##
GPLCart is an open source e-commerce platform based on the classical LAMP stack (Linux+ Apache+Mysql+PHP). It's free, simple and extensible solution that allows you to build online shops fast and easy. GplCart is not a fork of an existing software. It's completely unique, made "with blood, sweat and tears" from the scratch.

## Requirements ##

- PHP 5.4+, Mysql 5+, Apache 1+

Also you'll need the following extension enabled:

- PDO
- FileInfo
- SPL
- JSON
- GD
- Mb string
- Mod Rewrite

## Installation ##

**Old school:**

1. Download and extract to your hosting directory all files inside "gplcart" directory
2. Go to `http://yourdomain.com` and follow the instructions

**Composer:**

Clone to `test` directory

    composer create-project gplcart/gplcart test --stability dev --no-interaction

then you can perform full installation:

1. `cd test`
2. `php gplcart install`

In one line: `composer create-project gplcart/gplcart test --stability dev --no-interaction && cd test && php gplcart install`

## Some key features ##

- Simple MVC pattern
- PHP 7 compatibility
- PSR-0, PSR-4 standard compliance
- Dependency injection
- Modules are damn simple, theme = module. [See how you can generate your module](https://github.com/gplcart/skeleton)
- Hooks
- Command line support ([extensible](https://github.com/gplcart/cli))
- Ability to rewrite almost any core method from a module (no monkey patching, "VQ mods")
- Supports any template engine, including [TWIG](https://github.com/gplcart/twig)
- Supports versioned dependencies for modules and 3-d party libraries

- Really simple UI
- Multistore `anotherstore.com, anotherstore.domain.com`
- International, [easy translatable](https://github.com/gplcart/extractor)
- Product comparison
- Wishlists even for anonymous
- Address books
- No stupid cart pages, just one checkout page
- True one page checkout with graceful degradation when JS is disabled
- Product classes
- Bundled products
- Product fields (images, colors, text)
- Product combinations (XL + red, XL + green etc) with the easiest management you've ever seen
- Super flexible price rules both for catalog and checkout (including coupons)
- Roles and access control
- Autogenerated URL aliases
- Autogenerated SKU
- JS/CSS aggregation and compression
- Installation profiles

...and much more!

## Extensions ##

[There are](https://github.com/topics/gplcart-module) a number of official modules already written to extend core functionality.
