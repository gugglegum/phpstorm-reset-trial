<?php

declare(strict_types=1);

namespace App\Steps;

use App\Exceptions\UserAbortException;
use App\Utils\Console;
use App\Utils\Exec;

class Step02 extends StepAbstract
{
    const REGISTRY_KEY = 'HKEY_CURRENT_USER\SOFTWARE\JavaSoft\Prefs\jetbrains\phpstorm';

    /**
     * @var bool
     */
    private $isRegistryKeyDeleted = false;

    /**
     * Performs actions of the step
     *
     * @return void
     * @throws UserAbortException
     * @throws \Exception
     */
    public function forward(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (!Console::confirm("Remove Registry key \"" . self::REGISTRY_KEY . "\". Continue?")) {
                throw new UserAbortException();
            }
            Exec::exec('reg export ' . escapeshellarg(self::REGISTRY_KEY) . ' ' . escapeshellarg($this->stepConfig->getBackupDir() . '/phpstorm.reg'));
            echo "Deleting Registry ket ... ";
            try {
                Exec::exec('reg delete ' . escapeshellarg(self::REGISTRY_KEY) . ' /f');
                echo "OK\n";
            } catch (\Exception $e) {
                echo "FAILED\n";
                throw $e;
            }
            $this->isRegistryKeyDeleted = true;
        }
    }

    /**
     * Reverts actions of the step previously performed by forward() method
     *
     * @return void
     * @throws \Exception
     */
    public function backward(): void
    {
        if ($this->isRegistryKeyDeleted) {
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
    }

    public function needBackward(): bool
    {
        return $this->isRegistryKeyDeleted;
    }
}
