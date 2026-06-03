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

    public function getFailedJobUsers(): array
    {
        if ($this->failedJobUserGroup) {
            if ($groupId = Db::idByUid(Table::USERGROUPS, $this->failedJobUserGroup)) {
                return User::find()->groupId($groupId)->all();
            }
        }

        return [];
    }

    public function getFailedJobEmails(): array
    {
        $emails = [];

        foreach ($this->failedJobEmails as $row) {
            $email = is_array($row) ? ($row['email'] ?? '') : $row;
            $email = trim((string)App::parseEnv((string)$email));

            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    public function getFailedJobEmailRows(): array
    {
        $rows = [];

        foreach ($this->failedJobEmails as $row) {
            $email = is_array($row) ? ($row['email'] ?? '') : $row;
            $email = trim((string)$email);

            if ($email !== '') {
                $rows[] = ['email' => $email];
            }
        }

        return $rows;
    }

    public function validateFailedJobEmails(string $attribute, mixed $params = null, mixed $validator = null): void
    {
        foreach ($this->getFailedJobEmails() as $email) {
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
        $rules[] = [['restartMaxTries', 'restartInterval'], 'number', 'integerOnly' => true];
        $rules[] = [['failedJobEmails'], 'validateFailedJobEmails'];
        
        return $rules;
    }
}
