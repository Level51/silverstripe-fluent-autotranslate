<?php

namespace Level51\Autotranslate;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\TextareaField;

class BlackListExtension extends Extension
{
    private static $db = [
        'BlackListValues' => 'Text'
    ];

    public function updateCMSFields($fields)
    {
        $fields->addFieldsToTab('Root.' . _t(self::class . '.Translation', 'Translation'), [
            TextareaField::create('BlackListValues', _t(self::class . '.BlackListValues', 'Blacklist Values'))
                ->setDescription(_t(self::class . '.BlackListValuesDescription', 'Enter one value per line, the translatior will ignore these values and keep them as they are.'))
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
