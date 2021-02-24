<?php

namespace Level51\Autotranslate;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

class AutotranslateFieldExtension extends Extension
{
    use Configurable;

    /**
     * @var DataObject|null The affected DataObject record
     */
    private ?DataObject $record = null;

    /** @var string|null The field value in the source locale */
    private ?string $sourceValue = null;

    /** @var bool Whether the source locale value was already fetched or not (to cache requests) */
    private bool $sourceValueFetched = false;

    /**
     * @var array|string[] List of FormField class names to exclude
     */
    private static array $class_blacklist = [
        SiteTreeURLSegmentField::class
    ];

    /**
     * @var string The endpoint for the google translate api
     */
    private static string $google_translate_api = 'https://translation.googleapis.com/language/translate/v2';

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
     * @return string The target locale
     */
    private function getAutotranslateTargetLocale($shortCodeOnly = true): string
    {
        $locale = Locale::getCurrentLocale()->Locale;

        if ($shortCodeOnly) {
            $bits = explode('_', $locale);
            return array_shift($bits);
        }

        return $locale;
    }

    /**
     * @param bool $shortCodeOnly
     * @return string The source locale
     */
    private function getAutotranslateSourceLocale($shortCodeOnly = true): string
    {
        $locale = Locale::getDefault(Locale::getCurrentLocale()->Domain())->Locale;

        if ($shortCodeOnly) {
            $bits = explode('_', $locale);
            return array_shift($bits);
        }

        return $locale;
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
        $form = $this->owner->getForm();
        $record = null;

        if (get_class($form->getController()) === CMSPageEditController::class) {
            if ($idField = $form->Fields()->fieldByName('ID')) {
                $record = SiteTree::get()->byID($idField->Value());
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
                        $state->setLocale($this->getAutotranslateSourceLocale(false));
                        $sourceRecord = DataObject::get(get_class($record))->byID($record->ID);
                        $sourceValue = $sourceRecord->{$context->owner->Name};
                    }
                );

            $this->sourceValue = $sourceValue;
        }

        return $this->sourceValue;
    }

    /**
     * Get the payload passed to the vue component.
     *
     * @return string|null
     */
    public function getAutotranslateFieldPayload(): ?string
    {
        if ($this->isAutotranslateActionAvailable()) {
            return json_encode(
                [
                    'id'                 => $this->owner->ID(),
                    'getVars'            => [
                        'key' => self::config()->get('google_cloud_translation_api_key')
                    ],
                    'targetLocale'       => $this->getAutotranslateTargetLocale(),
                    'sourceLocale'       => $this->getAutotranslateSourceLocale(),
                    'sourceValue'        => $this->getAutotranslateSourceValue(),
                    'googleTranslateApi' => self::config()->get('google_translate_api')
                ]
            );
        }

        return null;
    }
}
