<?php

declare(strict_types=1);

namespace App\Steps;

use App\Exceptions\UserAbortException;
use App\Utils\Console;
use App\Utils\File;

class Step01 extends StepAbstract
{
    /**
     * @var bool
     */
    private $isConfigMoved = false;

    /**
     * Performs actions of the step
     *
     * @return void
     * @throws UserAbortException
     * @throws \Exception
     */
    public function forward(): void
    {
        if (is_dir($this->stepConfig->getSettingsConfigDir())) {
            if (file_exists($this->stepConfig->getBackupDir())) {
                if (!File::isEmptyDir($this->stepConfig->getBackupDir())) {
                    if (!Console::confirm('Backup folder already exists and it\'s not empty, need to clean it before continue. OK?', true)) {
                        throw new UserAbortException();
                    }
                    if (is_dir($this->stepConfig->getBackupDir())) {
                        echo 'Cleaning backup folder ... ';
                        try {
                            File::deleteDir($this->stepConfig->getBackupDir(), true);
                            echo "OK\n";
                        } catch (\RuntimeException $e) {
                            echo "FAILED\n";
                            throw $e;
                        }
                    } else {
                        throw new \Exception("Backup already exists but it's not a directory.");
                    }
                }
            } else {
                echo 'Making backup folder ... ';
                if (!mkdir($this->stepConfig->getBackupDir(), 0755)) {
                    echo "FAILED\n";
                    throw new \Exception("Can't create directory \"{$this->stepConfig->getBackupDir()}\"");
                }
                echo "OK\n";
            }

            do {
                echo "Move config folder to backup. PhpStorm must be closed. ";
            } while (!Console::confirm('Are you ready?'));

            echo 'Moving config folder to backup ... ';
            try {
                File::moveDir($this->stepConfig->getSettingsConfigDir(), $this->stepConfig->getBackupDir());
                $this->isConfigMoved = true;
                echo "OK\n";
            } catch (\RuntimeException $e) {
                echo "FAILED\n";
                throw $e;
            }
        } else {
            echo "No PhpStorm's config directory exists. ";
            if (file_exists($this->stepConfig->getBackupDir()) && !File::isEmptyDir($this->stepConfig->getBackupDir())) {
                echo "But we have some config in backup directory.\nLooks like you've already backed up config and just aborted this script recently.\n";
            } else {
                echo "Will not be able to restore settings.\n";
            }
            if (!Console::confirm('Would you like to continue?')) {
                throw new UserAbortException();
            }
        }
    }

    /**
     * Reverts actions of the step previously performed by forward() method
     *
     * @return void
     * @throws UserAbortException
     */
    public function backward(): void
    {
        if ($this->isConfigMoved) {
            if (!Console::confirm('Restore config directory from backup?', true)) {
                throw new UserAbortException();
            }
            echo 'Moving config folder back from backup ... ';
            try {
                File::moveDir($this->stepConfig->getBackupConfigDir(), $this->stepConfig->getSettingsDir());
                $this->isConfigMoved = false;
                echo "OK\n";
            } catch (\RuntimeException $e) {
                echo "FAILED\n";
                throw $e;
            }
        }
    }

    public function needBackward(): bool
    {
        return $this->isConfigMoved;
    }

}
