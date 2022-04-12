<?php

namespace Somar\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\DataExtension;

/**
 * If a page has not been edited in e.g. 6 months, send a reminder email
 * to CMS admin to check if any updates are needed.
 */
class UpdateReminderExtension extends DataExtension
{
    private static $db = [
        'UpdateReminderPeriod' => "Enum('None, One month, Three months, Six months, One year',
        'Six months')", // If this line is updated, also update SendUpdateRemindersTask.php
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.UpdateReminder',
            OptionsetField::create(
                'UpdateReminderPeriod',
                'How long since the last update before a reminder email is sent?',
                singleton('Page')->dbObject('UpdateReminderPeriod')->enumValues()
            )
        );
    }
}
