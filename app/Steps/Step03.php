<?php

declare(strict_types=1);

namespace App\Steps;

use App\Exceptions\UserAbortException;
use App\Utils\Console;
use App\Utils\File;

class Step03 extends StepAbstract
{
    /**
     * Performs actions of the step
     *
     * @throws UserAbortException
     */
    public function forward()
    {
        if (is_dir($this->stepConfig->getBackupConfigDir())) {
            echo "Now start PhpStorm and do the following things:\n",
            " - Select (*) Do not import anything -> Press [OK]\n",
            " - Press [Skip Remaining and Set Defaults]\n",
            " - Select (*) Evaluate for free -> Press [Evaluate]\n",
            " - Exit PhpStorm\n\n";
            do {
                if (Console::confirm('Did it?')) {
                    if (file_exists($this->stepConfig->getSettingsConfigDir() . DIRECTORY_SEPARATOR . $this->stepConfig->getPropertiesComponentFile())) {
                        break;
                    } else {
                        echo "No, you didn't.\n";
                    }
                } else {
                    if (Console::confirm("Do it, man. Or do you want to exit?")) {
                        throw new UserAbortException();
                    };
                }
            } while (true);

            //
            // Merging old options/other.xml with new one
            //

            echo "Merging old {$this->stepConfig->getPropertiesComponentFile()} with new one ... ";
            try {
                self::mergeOptionsXml(
                    $this->stepConfig->getBackupConfigDir() . DIRECTORY_SEPARATOR . $this->stepConfig->getPropertiesComponentFile(),
                    $this->stepConfig->getSettingsConfigDir() . DIRECTORY_SEPARATOR . $this->stepConfig->getPropertiesComponentFile()
                );
                echo "OK\n";
            } catch (\RuntimeException $e) {
                echo "FAILED\n";
                throw $e;
            }

            //
            // Copying all other config files
            //

            echo 'Copying all other config files back from backup ... ';
            try {
                File::copyDir($this->stepConfig->getBackupConfigDir(), $this->stepConfig->getSettingsDir(), false, function(\SplFileInfo $entry) {
                    return $entry->getRealPath() !== realpath($this->stepConfig->getBackupConfigDir() . DIRECTORY_SEPARATOR . $this->stepConfig->getPropertiesComponentFile())
                        && strtolower($entry->getExtension()) !== 'bak'
                        && realpath($entry->getPath()) !== realpath($this->stepConfig->getBackupConfigDir() . '/eval');
                });
                echo "OK\n";
            } catch (\RuntimeException $e) {
                echo "FAILED\n";
                throw $e;
            }
        } else {
            echo "Since we have not made backup of PhpStorm's config, your settings is not restored.\n";
            echo "Start PhpStorm as first time and setup it from scratch.\n";
        }
    }

    /**
     * Reverts actions of the step previously performed by forward() method
     *
     * @throws UserAbortException
     */
    public function backward()
    {
        if (is_dir($this->stepConfig->getSettingsConfigDir())) {
            if (!Console::confirm('Remove new just created config directory? (old one will be restored from backup then)', true)) {
                throw new UserAbortException();
            }
            echo "Removing new config directory ... ";
            try {
                File::deleteDir($this->stepConfig->getSettingsConfigDir());
                echo "OK\n";
            } catch (\RuntimeException $e) {
                echo "FAILED\n";
                throw $e;
            }
        }
    }

    /**
     * Merges new config/options/other.xml file with old one in backup
     *
     * @param string $oldOptionsFile
     * @param string $newOptionsFile
     */
    private static function mergeOptionsXml(string $oldOptionsFile, string $newOptionsFile)
    {
        $newOptionsFileTmp = $newOptionsFile . '.tmp';

        $fOld = fopen($oldOptionsFile, 'r');
        $fNew = fopen($newOptionsFile, 'r');
        $fMerged = fopen($newOptionsFileTmp, 'w');

        $initialProperties = [];

        while (!feof($fNew)) {

            if (($sNew = fgets($fNew)) === false) {
                throw new \RuntimeException("Failed to read from file \"{$newOptionsFile}\"");
            }

            if (preg_match('/^\s*<property\s+name=\"([^\\"]+)\"/', $sNew, $m)) {
                $initialProperties[] = $m[1];
            }

            if (trim($sNew) === '</component>') {
                while (!feof($fOld)) {
                    if (($sOld = fgets($fOld)) === false) {
                        throw new \RuntimeException("Failed to read from file \"{$oldOptionsFile}\"");
                    }
                    if (preg_match('/^\s*<property\s+name=\"([^\\"]+)\"/', $sOld, $m)) {
                        if (!in_array($m[1], $initialProperties) && !preg_match('/^evlsprt/', $m[1])) {
                            if (fputs($fMerged, $sOld) === false) {
                                throw new \RuntimeException("Failed to write to file \"{$newOptionsFileTmp}\"");
                            }
                        }
                    }
                }
            }

            if (fputs($fMerged, $sNew) === false) {
                throw new \RuntimeException("Failed to write to file \"{$newOptionsFileTmp}\"");
            }
        }

        fclose($fOld);
        fclose($fNew);
        fclose($fMerged);

        rename($newOptionsFile, $newOptionsFile . '.bak');
        rename($newOptionsFileTmp, $newOptionsFile);
    }

    public function needBackward(): bool
    {
        return is_dir($this->stepConfig->getSettingsConfigDir());
    }
}
