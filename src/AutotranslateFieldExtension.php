<?php

namespace Level51\Autotranslate;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\i18n\Data\Intl\IntlLocales;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

class AutotranslateFieldExtension extends Extension
{
    use Configurable;

    private static string $translation_provider = 'google'; // OPTIONS: ['google', 'openai']

    /** @var string */
    public const TRANSLATION_PROVIDER_GOOGLE = 'google';

    /** @var string */
    public const TRANSLATION_PROVIDER_OPENAI = 'openai';

    /**
     * @var DataObject|null The affected DataObject record
     */
    private $record = null;

    /** @var string|null The field value in the source locale */
    private $sourceValue = null;

    /** @var bool Whether the source locale value was already fetched or not (to cache requests) */
    private $sourceValueFetched = false;

    /**
     * @var array|string[] List of FormField class names to exclude
     */
    private static $class_blacklist = [
        SiteTreeURLSegmentField::class
    ];

    /**
     * @var string The endpoint for the google translate api
     */
    private static $google_translate_api = 'https://translation.googleapis.com/language/translate/v2';

    /**
     * Check if the autotranslate function should be available for the owner field.
     *
     * @return bool
     */
    private function isAutotranslateAvailableForField(): bool
    {
        if (in_array(get_class($this->owner), self::config()->get('class_blacklist'))) {
            return false;
        }

        if (!($record = $this->getAutotranslateRecord())) {
            return false;
        }

        if (!$record->hasExtension(FluentExtension::class)) {
            return false;
        }

        return $this->isAutotranslateLocalizedField($this->owner->Name);
    }

    /**
     * Check if the given field is available for translations.
     *
     * @param string $fieldName
     * @return bool
     */
    private function isAutotranslateLocalizedField($fieldName): bool
    {
        $record = $this->getAutotranslateRecord();
        $translatedFields = [];

        foreach ($record->getLocalisedTables() as $table => $translatedFieldsPerTable) {
            $translatedFields = array_merge($translatedFields, $translatedFieldsPerTable);
        }

        return in_array($fieldName, $translatedFields);
    }

    /**
     * Hook into the field holder render function to inject requirements and set custom template.
     *
     * @param FormField $context
     * @param           $properties
     */
    public function onBeforeRenderHolder($context, $properties)
    {
        if ($this->isAutotranslateAvailableForField()) {
            Requirements::javascript('level51/silverstripe-fluent-autotranslate: client/dist/autotranslateField.js');
            Requirements::css('level51/silverstripe-fluent-autotranslate: client/dist/autotranslateField.css');
            $context->setFieldHolderTemplate('Level51\Autotranslate\FormField_holder');
        }
    }

    /**
     * Check if the translate action should be available.
     *
     * Is only the case if not on the default locale.
     *
     * @return bool
     */
    public function isAutotranslateActionAvailable(): bool
    {
        if (!Locale::getLocales()->exists()) {
            return false;
        }

        if ($this->isAutotranslateAvailableForField()) {
            $locale = Locale::getCurrentLocale();

            if ($locale->getIsDefault()) {
                return false;
            }

            return !!$this->getAutotranslateSourceValue();
        }

        return false;
    }

    /**
     * Get a ID for the autotranslate "field" (vue component).
     *
     * @return string
     */
    public function getAutotranslateFieldID(): string
    {
        return $this->owner->ID() . '_autotranslate';
    }

    /**
     * @param bool $shortCodeOnly
     * @return string|Locale The target locale
     */
    private function getAutotranslateTargetLocale($shortCodeOnly = false)
    {
        $locale = Locale::getCurrentLocale();

        if ($shortCodeOnly) {
            $bits = explode('_', $locale->Locale);
            return array_shift($bits);
        }

        return $locale;
    }

    /**
     * @param bool $shortCodeOnly
     * @return string|Locale The source locale
     */
    private function getAutotranslateSourceLocale($shortCodeOnly = false)
    {
        $locale = Locale::getDefault(Locale::getCurrentLocale()->Domain());

        if ($shortCodeOnly) {
            $bits = explode('_', $locale->Locale);
            return array_shift($bits);
        }

        return $locale;
    }

    /**
     * Get the current cms locale as short language code (e.g. "en").
     * @return string
     */
    private function getCMSLocaleForAutotranslateField(): string
    {
        $bits = explode('_', i18n::get_locale());

        return array_shift($bits);
    }

    /**
     * Get the affected DataObject record.
     *
     * @return DataObject|null
     */
    private function getAutotranslateRecord(): ?DataObject
    {
        if ($this->record) {
            return $this->record;
        }

        if ($record = $this->fetchAutoTranslateRecord()) {
            $this->record = $record;

            return $this->record;
        }

        return null;
    }

    /**
     * Try to fetch the affected DataObject record.
     *
     * As we are in the field context, we need to try to fetch the record
     * depending on the holder form.
     *
     * @return DataObject|null
     */
    private function fetchAutoTranslateRecord(): ?DataObject
    {
        if (!$this->owner || !$this->owner->hasMethod('getForm') || !$this->owner->getForm()) {
            return null;
        }

        $form = $this->owner->getForm();
        $record = null;

        if (get_class($form->getController()) === CMSPageEditController::class) {
            if ($idField = $form->Fields()->fieldByName('ID')) {
                $record = SiteTree::get()->byID($idField->getValue());
            }
        } elseif ($form->getController() instanceof GridFieldDetailForm_ItemRequest) {
            $record = $form->getRecord();
        }

        return $record;
    }

    /**
     * Get the relevant value in the source locale.
     *
     * @return string|null
     */
    private function getAutotranslateSourceValue(): ?string
    {
        if (!$this->sourceValueFetched) {
            $record = $this->getAutotranslateRecord();
            $context = $this;
            $sourceValue = null;

            FluentState::singleton()
                ->withState(
                    function ($state) use ($context, $record, &$sourceValue) {
                        $state->setLocale($this->getAutotranslateSourceLocale()->Locale);
                        $sourceRecord = DataObject::get(get_class($record))->byID($record->ID);
                        $sourceValue = $sourceRecord->{$context->owner->Name};
                    }
                );

            $this->sourceValue = $sourceValue;
        }

        return $this->sourceValue;
    }

    public function hasAutotranslateSourceValue()
    {
        $locale = Locale::getCurrentLocale();
        if ($locale->getIsDefault()) {
            return true; // always return true for default locale
        }
        return $this->getAutotranslateSourceValue() !== null;
    }

    /**
     * Get the active translation provider.
     *
     * @return string
     */
    private function getTranslationProvider(): string
    {
        switch (self::config()->get('translation_provider')) {
            case 'openai':
                return self::TRANSLATION_PROVIDER_OPENAI;
            case 'google':
            default:
                return self::TRANSLATION_PROVIDER_GOOGLE;
        }
    }

    /**
     * Get the translation provider dependent config.
     *
     * @return array|null
     */
    private function getConfigForTranslationProvider(): ?array
    {
        switch (self::config()->get('translation_provider')) {
            case self::TRANSLATION_PROVIDER_GOOGLE:
                return [
                    'apiEndpoint' => self::config()->get('google_translate_api'),
                    'getVars'     => [
                        'key' => self::config()->get('google_cloud_translation_api_key')
                    ],
                ];
            case self::TRANSLATION_PROVIDER_OPENAI:
                return [
                    'getVars'     => [
                        'key' => self::config()->get('openai_translation_api_key')
                    ],
                ];
        }

        return null;
    }

    private function getAutotranslateTermsBlacklist()
    {
        switch (self::config()->get('translation_provider')) {
            case self::TRANSLATION_PROVIDER_GOOGLE:
                return '';
            case self::TRANSLATION_PROVIDER_OPENAI:
                if(SiteConfig::has_extension(BlackListExtension::class)) {
                    return SiteConfig::current_site_config()->BlackListValues();
                }
        }
    }

    /**
     * @return string The field title / label.
     */
    private function getAutotranslateFieldTitle(): string
    {
        $title = $this->owner->Title();

        return method_exists($title, 'forTemplate') ? $title->forTemplate() : $title;
    }

    /**
     * Get the payload passed to the vue component.
     *
     * @return string|null
     */
    public function getAutotranslateFieldPayload(): ?string
    {
        if ($this->isAutotranslateActionAvailable()) {
            $targetLocale = $this->getAutotranslateTargetLocale();
            $sourceLocale = $this->getAutotranslateSourceLocale();

            return json_encode(
                [
                    'id'             => $this->owner->ID(),
                    'fieldTitle'     => $this->getAutotranslateFieldTitle(),
                    'cmsLocale'      => $this->getCMSLocaleForAutotranslateField(),
                    'targetLocale'   => [
                        'code'   => $this->getAutotranslateTargetLocale(true),
                        'locale' => $targetLocale->Locale,
                        'title'  => IntlLocales::singleton()->languageName($targetLocale->Locale)
                    ],
                    'sourceLocale'   => [
                        'code'   => $this->getAutotranslateSourceLocale(true),
                        'locale' => $sourceLocale->Locale,
                        'title'  => IntlLocales::singleton()->languageName($sourceLocale->Locale)
                    ],
                    'sourceValue'    => $this->getAutotranslateSourceValue(),
                    'provider'       => $this->getTranslationProvider(),
                    'providerConfig' => $this->getConfigForTranslationProvider(),
                    'termsBlacklist' => $this->getAutotranslateTermsBlacklist(),
                ]
            );
        }

        return null;
    }
}
