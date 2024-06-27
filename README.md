# Silverstripe Fluent Autotranslate
Module for auto translation using Google Cloud Translation for Silverstripe with Fluent.

## Requirements
- [Silverstripe](https://github.com/silverstripe/silverstripe-framework) ^4 || ^5
- [Fluent](https://github.com/tractorcow-farm/silverstripe-fluent) >=4
- PHP >= 7.1
- ext-json

## Installation
`composer require level51/silverstripe-fluent-autotranslate`

## Setup
Define the API key for the Google Cloud Translation API like this:

```yaml
Level51\Autotranslate\AutotranslateFieldExtension:
    google_cloud_translation_api_key: 'YOUR_API_KEY'
```

See https://cloud.google.com/translate/docs/setup for setup instructions.

## Maintainer
- Level51 <hallo@lvl51.de>

## Disable extension for specific field types
```yaml
Level51\Autotranslate\AutotranslateFieldExtension:
  class_blacklist:
    - SilverStripe\Forms\HTMLEditor\HTMLEditorField
```
