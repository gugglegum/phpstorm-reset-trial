<?php /** @noinspection HtmlUnknownTag */

namespace App;

use App\Exceptions\UserAbortException;
use App\Steps\StepInterface;
use App\Utils\Console;

class PhpstormResetTrial
{
    /**
     * @var string
     */
    private $phpStormDir;

    public static function main()
    {
        $app = new self();
        $app->run();
    }

    public function run()
    {
        echo "PhpStorm Reset Trial\n====================\n\n";
        echo "This utility will reset trail period of your PhpStorm installation with saving its settings.\n\n";

        $this->parseCommandLineArguments();

        $stepsStack = [];
        try {
            $this->checkIsValidPhpStormDir();
            $settingsConfigDir = $this->getIdeaConfigDir();

            echo "Config directory should be in \"{$settingsConfigDir}\"\n";

            $settingsDir = dirname($settingsConfigDir);
            $backupDir = $settingsDir . '/backup';

            echo "Backup directory will be in \"{$backupDir}\"\n\n";

            if (!Console::confirm("Want to continue?")) {
                throw new UserAbortException();
            }

            $stepConfig = new Steps\StepConfig([
                'settingsDir' => $settingsDir,
                'settingsConfigDir' => $settingsConfigDir,
                'backupDir' => $backupDir,
                'backupConfigDir' => $backupDir . '/config',
            ]);

            for ($stepNumber = 1; $stepNumber <= 3; $stepNumber++) {
                $stepClassName = 'App\Steps\Step' . str_pad((string) $stepNumber, 2, 0, STR_PAD_LEFT);
                /** @var \App\Steps\StepInterface $step */
                $step = new $stepClassName($stepConfig);
                array_push($stepsStack, $step);
                $step->forward();
            }
        } catch (\Exception $e) {
            self::printException($e);
            if (self::needStepsBackward($stepsStack)) {
                if (Console::confirm('We have made some changes that we may try to revert. Revert changes?')) {
                    try {
                        while ($step = array_pop($stepsStack)) {
                            $step->backward();
                        }
                    } catch (\Exception $e) {
                        self::printException($e);
                    }
                }
            }
            exit(-1);
        }

        echo "\nAll is done. Now you can start PhpStorm and continue to use it yet another 30 days! :)\n";
    }

    private function parseCommandLineArguments()
    {
        if ($_SERVER['argc'] < 2) {
            echo "Usage:\n\tphp ", basename(__FILE__), " <PhpStorm-Installation-Dir>\n\n";
            exit(-1);
        }

        $this->phpStormDir = $_SERVER['argv'][1];
    }

    /**
     * @throws \Exception
     */
    private function checkIsValidPhpStormDir()
    {
        if (!file_exists($this->phpStormDir . '/bin/idea.properties')) {
            throw new \Exception("Invalid PhpStorm installation directory passed (\"{$this->phpStormDir}\")\n");
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getIdeaConfigDir(): string
    {
        $propertiesPathFile = $this->phpStormDir . '/bin/idea.properties';
        $configPathOptionName = 'idea.config.path';
        $f = new \SplFileObject($propertiesPathFile);
        while (($s = $f->fgets()) !== false) {
            $s = rtrim($s);
            if ($s === '' || $s{0} === '#') {
                continue;
            }
            list($key, $value) = array_map('trim', explode('=', $s));
            if ($key === 'idea.config.path') {
                return $value;
            }
        }
        throw new \Exception("Failed to get \"{$configPathOptionName}\" from \"{$propertiesPathFile}\"");
    }

    /**
     * @param \Exception $e
     */
    public static function printException(\Exception $e)
    {
        echo (!$e instanceof UserAbortException ? 'Error: ' : ''), $e->getMessage(), "\n";
    }

    /**
     * Checks whether we need to restore changes we have made
     *
     * @param StepInterface[] $stepsStack
     * @return bool
     */
    private static function needStepsBackward(array $stepsStack): bool
    {
        foreach($stepsStack as $step) {
            if ($step->needBackward()) {
                return true;
            }
        }
        return false;
    }
}
