<?php
namespace verbb\queuemonitor\models;

use craft\base\Model;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['restartMaxTries'], 'number', 'integerOnly' => true];
        
        return $rules;
    }
}
