<?php

namespace Somar\Job;

use SilverStripe\ORM\FieldType\DBDatetime;
use Somar\Task\SendUpdateRemindersTask;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Each page can have an update reminder period set.
 * This job checks for any pages that have their last update time older
 * than the reminder period, and emails site admins requesting an update.
 */
class SendUpdateRemindersJob extends AbstractQueuedJob
{
    public function __construct($params = null)
    {
        // Special properties required by queuedjobs framework
        // to ensure that jobs execute smoothly and can be paused/stopped/restarted.
        $this->totalSteps = 1;
        $this->currentStep = 0;
    }

    public function getTitle()
    {
        return 'Send Update Reminders';
    }

    public function process()
    {
        $task = new SendUpdateRemindersTask();
        $task->sendUpdateReminders();
        $this->messages = $task->getMessages();
        $this->addMessage('Done.');
        $this->isComplete = true;
        $this->requeue();
    }

    private function requeue()
    {
        $nextJob = new self();
        $startAfter = DBDatetime::now()->modify('+ 1 week')->Format(DBDatetime::ISO_DATETIME);

        singleton(QueuedJobService::class)->queueJob($nextJob, $startAfter);
    }
}
