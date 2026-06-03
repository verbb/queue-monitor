<?php
namespace verbb\queuemonitor\models;

use craft\base\Model;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Db;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool $restartFailedJobs = false;
    public int $restartMaxTries = 3;
    public int $restartInterval = 10;
    public bool $sendFailedJobEmail = false;
    public ?string $failedJobUserGroup = null;
    public array $failedJobEmails = [];
    public bool $monitorStalledQueue = false;
    public int $stalledQueueThreshold = 30;
    public int $stalledQueueCheckInterval = 30;
    public bool $sendStalledQueueEmail = false;
    public ?string $stalledQueueUserGroup = null;
    public array $stalledQueueEmails = [];
    public ?string $stalledQueueWebhook = null;


    // Public Methods
    // =========================================================================

    public function getRestartFailedJobs(): bool
    {
        return App::parseBooleanEnv($this->restartFailedJobs);
    }

    public function getSendFailedJobEmail(): bool
    {
        return App::parseBooleanEnv($this->sendFailedJobEmail);
    }

    public function getMonitorStalledQueue(): bool
    {
        return App::parseBooleanEnv($this->monitorStalledQueue);
    }

    public function getStalledQueueThreshold(): int
    {
        return (int)App::parseEnv($this->stalledQueueThreshold);
    }

    public function getStalledQueueCheckInterval(): int
    {
        return (int)App::parseEnv($this->stalledQueueCheckInterval);
    }

    public function getSendStalledQueueEmail(): bool
    {
        return App::parseBooleanEnv($this->sendStalledQueueEmail);
    }

    public function getStalledQueueWebhook(): ?string
    {
        $webhook = trim((string)App::parseEnv($this->stalledQueueWebhook));

        return $webhook !== '' ? $webhook : null;
    }

    public function getFailedJobUsers(): array
    {
        return $this->_getUsersByGroupUid($this->failedJobUserGroup);
    }

    public function getStalledQueueUsers(): array
    {
        return $this->_getUsersByGroupUid($this->stalledQueueUserGroup);
    }

    public function getFailedJobEmails(): array
    {
        return $this->_getEmails($this->failedJobEmails);
    }

    public function getFailedJobEmailRows(): array
    {
        return $this->_getEmailRows($this->failedJobEmails);
    }

    public function getStalledQueueEmails(): array
    {
        return $this->_getEmails($this->stalledQueueEmails);
    }

    public function getStalledQueueEmailRows(): array
    {
        return $this->_getEmailRows($this->stalledQueueEmails);
    }

    public function validateFailedJobEmails(string $attribute, mixed $params = null, mixed $validator = null): void
    {
        foreach ($this->_getEmails($this->$attribute) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError($attribute, "$email is not a valid email address.");
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['restartMaxTries', 'restartInterval', 'stalledQueueThreshold', 'stalledQueueCheckInterval'], 'number', 'integerOnly' => true, 'min' => 1];
        $rules[] = [['failedJobEmails', 'stalledQueueEmails'], 'validateFailedJobEmails'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getUsersByGroupUid(?string $groupUid): array
    {
        if ($groupUid) {
            if ($groupId = Db::idByUid(Table::USERGROUPS, $groupUid)) {
                return User::find()->groupId($groupId)->all();
            }
        }

        return [];
    }

    private function _getEmails(array $emailRows): array
    {
        $emails = [];

        foreach ($emailRows as $row) {
            $email = is_array($row) ? ($row['email'] ?? '') : $row;
            $email = trim((string)App::parseEnv((string)$email));

            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    private function _getEmailRows(array $emailRows): array
    {
        $rows = [];

        foreach ($emailRows as $row) {
            $email = is_array($row) ? ($row['email'] ?? '') : $row;
            $email = trim((string)$email);

            if ($email !== '') {
                $rows[] = ['email' => $email];
            }
        }

        return $rows;
    }
}
