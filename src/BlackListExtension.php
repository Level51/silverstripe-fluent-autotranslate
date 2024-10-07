<?php

namespace Level51\Autotranslate;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataExtension;

class BlackListExtension extends DataExtension
{
    private static $db = [
        'BlackListValues' => 'Text'
    ];

    public function updateCMSFields($fields)
    {
        $fields->addFieldsToTab('Root.Translation', [
            TextareaField::create('BlackListValues', 'BlackList Values')
                ->setDescription('Enter one value per line, the translatior will ignore these values and keep them as they are.')
        ]);
    }

    public function BlackListValues(): string
    {
        if(!$this->owner->BlackListValues) return '';

        $values = str_replace("\r", "", $this->owner->BlackListValues);
        $values = explode("\n", $values);
        return implode(", ", $values);
    }

}
