<?php

namespace Somar\Task;

use Page;
use SilverStripe\Control\Email\Email;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Group;
use SilverStripe\View\ArrayData;

class SendUpdateRemindersTask extends BuildTask
{
    protected $title = 'Send Update Reminders';

    protected $description = 'Each page can have an update reminder period set.<br>This task
        checks for any pages that have their last update time older than the reminder
        period, and emails the page owners requesting an update.';

    // What the enums in Page.php actually map to (in days)
    private static $update_periods = [
        'One month' => 30,
        'Three months' => 90,
        'Six months' => 180,
        'One year' => 365,
    ];

    private $groupedItems = [];

    // cache property for getMembersToNotify()
    private $notifyMembers = [];

    // output log
    private $messages = [];

    public function run($request)
    {
        $this->sendUpdateReminders();
        $this->printMessages();
    }

    public function sendUpdateReminders()
    {
        $emailAddresses = join(', ', $this->getEmailAddressesToNotify());
        $this->addMessage("Notification emails will be sent to: {$emailAddresses}");
        $this->addMessage('');

        $this->checkPages();

        if ($this->staleItemsCount()) {
            $this->sendNotificationEmails();
        }
    }

    public function checkPages()
    {
        $this->groupedItems['Pages'] = ArrayList::create();

        foreach (self::$update_periods as $updatePeriodTitle => $updatePeriodDays) {
            $window = DBDatetime::now()->modify("- {$updatePeriodDays} days");

            $this->addMessage("Pages exceeding {$updatePeriodTitle} reminder period date of {$window->Nice()}");
            $this->addMessage('');

            $stalePages = Page::get()->filter([
                'UpdateReminderPeriod' => $updatePeriodTitle,
                'LastEdited:LessThan' => $window,
            ]);

            $this->groupedItems['Pages']->push(ArrayData::create([
                'Title' => $updatePeriodTitle,
                'Window' => $window,
                'Items' => $stalePages,
            ]));

            foreach ($stalePages as $page) {
                $this->addMessage("{$page->Title} (version #{$page->Version})");
                $this->addMessage($page->AbsoluteLink());
                $this->addMessage("Last edited: {$page->LastEdited}");
                $this->addMessage("Stale by: {$page->dbObject('LastEdited')->Ago()}");
                $this->addMessage('');
            }

            $this->addMessage('<hr />');
        }
    }

    public function getMessages()
    {
        return $this->messages;
    }

    private function staleItemsCount()
    {
        $countItems = function ($sum, $group) {
            return $sum + array_reduce(
                $group->toArray(),
                fn ($carry, $item) => $carry + $item->Items->Count()
            );
        };

        return array_reduce($this->groupedItems, $countItems);
    }

    private function sendNotificationEmails()
    {
        foreach ($this->getMembersToNotify() as $member) {
            $email = Email::create()
                ->setTo($member->Email)
                ->setSubject('Update Reminders for ' . date('Y-m-d', time()))
                ->setHTMLTemplate('Somar\\Email\\UpdateReminderEmail')
                ->setData([
                    'MemberName' => $member->getName(),
                    'LastRun' => DBDatetime::now(),
                    'ItemsTypes' => ArrayList::create(
                        array_map(
                            fn ($name) => ArrayData::create(['Name' => $name, 'GroupedItems' => $this->groupedItems[$name]]),
                            array_keys($this->groupedItems)
                        )
                    ),
                ], )
            ;
            $email->send();
        }
    }

    private function getEmailAddressesToNotify()
    {
        return array_map(function ($member) {
            return $member->Email;
        }, $this->getMembersToNotify());
    }

    private function getMembersToNotify()
    {
        // cache
        if (!empty($this->notifyMembers)) {
            return $this->notifyMembers;
        }

        $group = Group::get()->filter([
            'Code' => 'administrators',
        ])->first();

        $this->notifyMembers = array_filter($group->Members()->toArray(), function ($member) {
            return Email::is_valid_address($member->Email);
        });

        return $this->notifyMembers;
    }

    /**
     * @param string $message
     * @param string $severity
     */
    private function addMessage($message, $severity = 'INFO')
    {
        $severity = strtoupper($severity);
        $this->messages[] = '[' . DBDatetime::now()->Rfc2822() . "][{$severity}] {$message}";
    }

    private function printMessages()
    {
        foreach ($this->messages as $message) {
            DB::alteration_message($message);
        }
    }
}
