<?php

declare(strict_types=1);

namespace App\Steps;

class StepConfig
{
    /**
     * @var string
     */
    private $settingsDir;

    /**
     * @var string
     */

    private $settingsConfigDir;

    /**
     * @var string
     */
    private $backupDir;

    /**
     * @var string
     */
    private $backupConfigDir;

    /**
     * A relative path and file name where PhpStorm stores XML tree of <component name="PropertiesComponent">. Before
     * PhpStorm 2018.3.3 it was in options/options.xml, since 2018.3.3 it is in options/other.xml
     *
     * @var string
     */
    private $propertiesComponentFile;

    /**
     * StepsConfig constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if ($missingKeys = array_diff(array_keys(get_object_vars($this)), array_keys($data))) {
            throw new \LogicException('Missing following key(s) in data array passed to the constructor of '
                . __CLASS__ . ': ' . implode(',', $missingKeys));
        }

        if ($unexpectedKeys = array_diff(array_keys($data), array_keys(get_object_vars($this)))) {
            throw new \LogicException('Unexpected following key(s) in data array passed to the constructor of '
                . __CLASS__ . ': ' . implode(',', $unexpectedKeys));
        }

        foreach (get_object_vars($this) as $key => $value) {
            if (array_key_exists($key, $data)) {
                $this->{$key} = $data[$key];
            }
        }
    }

    /**
     * @return string
     */
    public function getSettingsDir(): string
    {
        return $this->settingsDir;
    }

    /**
     * @return string
     */
    public function getSettingsConfigDir(): string
    {
        return $this->settingsConfigDir;
    }

    /**
     * @return string
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * @return string
     */
    public function getBackupConfigDir(): string
    {
        return $this->backupConfigDir;
    }

    public function getPropertiesComponentFile(): string
    {
        return $this->propertiesComponentFile;
    }
}
