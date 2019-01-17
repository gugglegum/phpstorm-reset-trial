<?php

declare(strict_types=1);

namespace App\Steps;

use App\Exceptions\UserAbortException;
use App\Utils\Console;
use App\Utils\Exec;
use App\Utils\File;

class Step02 extends StepAbstract
{
    const REGISTRY_KEY = 'HKEY_CURRENT_USER\SOFTWARE\JavaSoft\Prefs\jetbrains\phpstorm';
    const JAVA_USER_PREFS = '~/.java/.userPrefs/jetbrains/phpstorm';

    /**
     * @var bool
     */
    private $isRegistryKeyDeleted = false;

    /**
     * Whether "reg export" command to backup currently existing registry key was successful?
     *
     * @var bool
     */
    private $isRegistryKeyExported = false;

    /**
     * @var bool
     */
    private $isJavaUserPrefsDeleted = false;

    /**
     * Performs actions of the step
     *
     * @throws UserAbortException
     * @throws \Exception
     */
    public function forward()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (!Console::confirm("Remove Registry key \"" . self::REGISTRY_KEY . "\". Continue?")) {
                throw new UserAbortException();
            }
            try {
                Exec::exec('reg export ' . escapeshellarg(self::REGISTRY_KEY) . ' ' . escapeshellarg($this->stepConfig->getBackupDir() . '/phpstorm.reg'));
                $this->isRegistryKeyExported = true;
            } catch (\Exception $e) {
                /* do nothing */
            }
            echo "Deleting Registry key ... ";
            try {
                Exec::exec('reg delete ' . escapeshellarg(self::REGISTRY_KEY) . ' /f');
                echo "OK\n";
            } catch (\Exception $e) {
                echo "FAILED (probably already deleted)\n";
            }
            $this->isRegistryKeyDeleted = true;
        } else {
            if (is_dir(self::expandTildeToHomeDir(self::JAVA_USER_PREFS))) {
                if (!Console::confirm('Remove folder ' . self::JAVA_USER_PREFS . '?')) {
                    throw new UserAbortException();
                }

                echo "Making backup ... ";
                try {
                    File::copyDir(self::expandTildeToHomeDir(self::JAVA_USER_PREFS), $this->stepConfig->getBackupDir() . '/phpstorm', true);
                    echo "OK\n";
                } catch (\RuntimeException $e) {
                    echo "FAILED\n";
                    throw $e;
                }

                echo "Removing ... ";
                try {
                    File::deleteDir(self::expandTildeToHomeDir(self::JAVA_USER_PREFS));
                    echo "OK\n";
                    $this->isJavaUserPrefsDeleted = true;
                } catch (\RuntimeException $e) {
                    echo "FAILED\n";
                    throw $e;
                }
            } else {
                echo 'Java user preferences directory ' . self::JAVA_USER_PREFS . " doesn't exists\n";
            }
        }
    }

    /**
     * Reverts actions of the step previously performed by forward() method
     *
     * @throws \Exception
     */
    public function backward()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if ($this->isRegistryKeyDeleted && $this->isRegistryKeyExported) {
                if (!Console::confirm('Restore removed Registry key "' . self::REGISTRY_KEY . '"?', true)) {
                    throw new UserAbortException();
                }
                echo "Restoring Registry key ... ";
                try {
                    Exec::exec('reg import ' . escapeshellarg($this->stepConfig->getBackupDir() . '/phpstorm.reg'));
                    echo "OK\n";
                } catch (\Exception $e) {
                    echo "FAILED\n";
                    throw $e;
                }
                $this->isRegistryKeyDeleted = false;
            }
        } else {
            if ($this->isJavaUserPrefsDeleted) {
                if (!Console::confirm('Restore ' . self::JAVA_USER_PREFS . '?')) {
                    throw new UserAbortException();
                }

                echo "Restoring from backup ... ";
                try {
                    File::copyDir($this->stepConfig->getBackupDir() . '/phpstorm', self::expandTildeToHomeDir(self::JAVA_USER_PREFS), true);
                    echo "OK\n";
                } catch (\RuntimeException $e) {
                    echo "FAILED\n";
                    throw $e;
                }

                echo "Cleaning backup ... ";
                try {
                    File::deleteDir($this->stepConfig->getBackupDir() . '/phpstorm');
                    echo "OK\n";
                    $this->isJavaUserPrefsDeleted = false;
                } catch (\RuntimeException $e) {
                    echo "FAILED\n";
                    throw $e;
                }
            }
        }
    }

    public function needBackward(): bool
    {
        return $this->isRegistryKeyDeleted;
    }

    /**
     * [UNIX only] Expands dir like ~/.java to /home/username/.java
     *
     * @param string $homeRelativePath
     * @return string
     */
    private static function expandTildeToHomeDir(string $homeRelativePath): string
    {
        return preg_replace('/^~\//', getenv('HOME') . '/', $homeRelativePath);
    }
}
