<?php /** @noinspection HtmlUnknownTag */

namespace App;

use App\Exceptions\UserAbortException;
use App\Steps\StepInterface;
use App\Utils\Console;

class PhpstormResetTrial
{
    const VERSION = '1.0.1';
    const DATE = '2018-10-19';

    /**
     * @var string
     */
    private $configDir;

    public static function main()
    {
        $app = new self();
        $app->run();
    }

    public function run()
    {
        echo "PhpStorm Reset Trial ver. " . self::VERSION . ' ' . self::DATE . "\n\n";
        echo "This utility will reset trial period of your PhpStorm installation with saving its settings.\n\n";

        $this->parseCommandLineArguments();

        $stepsStack = [];
        try {
            $this->checkIsValidConfigDir();

            echo "Config directory in \"{$this->configDir}\"\n";

            $settingsDir = dirname($this->configDir);
            $backupDir = $settingsDir . '/backup';

            echo "Backup directory in \"{$backupDir}\"\n\n";

            if (!Console::confirm("Want to continue?")) {
                throw new UserAbortException();
            }

            $stepConfig = new Steps\StepConfig([
                'settingsDir' => $settingsDir,
                'settingsConfigDir' => $this->configDir,
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
                echo "...............\nWe are exiting now, but we have made some changes that we may try to revert. ";
                if (Console::confirm('Revert changes?')) {
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

    /**
     * @throws \Exception
     */
    private function checkIsValidConfigDir()
    {
        if (!file_exists($this->configDir . '/options/options.xml')) {
            throw new \Exception("Directory {$this->configDir} is not looks like valid PhpStorm config directory\n");
        }
    }

    private function parseCommandLineArguments()
    {
        if ($_SERVER['argc'] < 2) {
            echo "Usage:\n\tphp ", basename(__FILE__), " <PhpStorm-Config-Dir>\n\n";
            exit(-1);
        }

        $this->configDir = rtrim($_SERVER['argv'][1], '/\\');
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
