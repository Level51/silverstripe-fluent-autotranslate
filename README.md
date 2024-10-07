# Silverstripe Fluent Autotranslate
Module for auto translation using Google Cloud Translation for Silverstripe with Fluent.

## Content
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup Google Cloud Translation API](#setup-google-cloud-translation-api)
- [Setup OpenAI API](#setup-openai-api)
- [Maintainer](#maintainer)
- [Disable extension for specific field types](#disable-extension-for-specific-field-types)

## Requirements
- [Silverstripe](https://github.com/silverstripe/silverstripe-framework) ^4 || ^5
- [Fluent](https://github.com/tractorcow-farm/silverstripe-fluent) >=4
- PHP >= 7.1
- ext-json

## Installation
`composer require level51/silverstripe-fluent-autotranslate`

## Setup Google Cloud Translation API
Define the API key for the Google Cloud Translation API like this:

```yaml
Level51\Autotranslate\AutotranslateFieldExtension:
    translation_provider: 'google' # 'google' or 'openai' (default: google)
    google_cloud_translation_api_key: 'YOUR_API_KEY'
```

See https://cloud.google.com/translate/docs/setup for setup instructions.

## Setup OpenAI API
See https://platform.openai.com/docs/quickstart/create-and-export-an-api-key for setup instructions and how to get an API key.

Define the API key for the OpenAI API like this in your `config.yml`:

```yaml
Level51\Autotranslate\AutotranslateFieldExtension:
  translation_provider: 'openai' # 'google' or 'openai' (default: google)
  openai_translation_api_key: 'YOUR_API_KEY'
```

For Open AI you can specify terms to avoid translation by adding them to the `openai_translation_blacklist` in the backend admin settings.
If you want to use this feature you have to add the BlackListExtension to the SiteConfig class.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Level51\Autotranslate\BlackListExtension
```

## Maintainer
- Level51 <hallo@lvl51.de>

## Disable extension for specific field types
```yaml
Level51\Autotranslate\AutotranslateFieldExtension:
  class_blacklist:
    - SilverStripe\Forms\HTMLEditor\HTMLEditorField
```
