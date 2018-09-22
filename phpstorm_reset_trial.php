<?php

echo "PhpStorm Reset Trial\n====================\n\n";

echo "This utility will reset trail period of your PhpStorm installation with saving its settings.\n\n";

if (!confirm('Want to continue?')) {
    echo "Aborting...\n";
    return;
}

$settingsDir = 'C:/ProgramData/JetBrains/PhpStorm.test';
$backupDir = $settingsDir . '/backup';
$settingsConfigDir = $settingsDir . '/config';
$backupConfigDir = $backupDir . '/config';

//
// Making backup of config folder
//

if (file_exists($backupDir) && !isEmptyDir($backupDir)) {
    if (!confirm('Backup folder already exists and it\'s not empty, need to clean it before continue. OK?', true)) {
        exit();
    }
    if (is_dir($backupDir)) {
        echo 'Cleaning backup folder ... ';
        try {
            deleteDir($backupDir, true);
        } catch (\RuntimeException $e) {
            echo "FAILED\n";
            printException($e);
            exit(-1);
        }
        echo "OK\n";
    } else {
        echo "Backup already exists but it's not a directory.\n";
        exit(-1);
    }
}

if (!file_exists($backupDir)) {
    echo 'Making backup folder ... ';
    if (!mkdir($backupDir, 0755)) {
        echo "FAILED\n";
        exit(-1);
    }
    echo "OK\n";
}


do {
    echo "We are going to move config folder to backup. PhpStorm must be closed. ";
} while (!confirm('Are you ready?'));

echo 'Moving config folder to backup ... ';
try {
    moveDir($settingsConfigDir, $backupDir);
} catch (\RuntimeException $e) {
    echo "FAILED\n";
    printException($e);
    exit(-1);
}
echo "OK\n";

//
// Cleaning Registry (Windows only)
//

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $registryFolder = "HKEY_CURRENT_USER\SOFTWARE\JavaSoft\Prefs\jetbrains\phpstorm";

    if (!confirm("We are going to remove registry folder \"{$registryFolder}\". Continue?")) {
        echo "Aborting...\n";
        return;
    }

    passthru("reg delete " . escapeshellarg($registryFolder), $retCode);
    if ($retCode !== 0) {
        echo "Failed to remove registry folder - aborting...";
        exit(-1);
    }

}

do {
    if (confirm('Now start PhpStorm, select evaluation end exit. Did it?')) {
        if (is_dir($settingsConfigDir)) {
            break;
        } else {
            echo "No, it seems you didn't.\n";
        }
    }
} while (true);

//
// Merging old options/options.xml with new one
//

echo 'Merging old options/options.xml with new one ... ';
try {
    mergeOptionsXml($backupConfigDir . '/options/options.xml', $settingsConfigDir . '/options/options.xml');
} catch (\RuntimeException $e) {
    echo "FAILED\n";
    printException($e);
    exit(-1);
}
echo "OK\n";

//
// Copying all other config files
//

echo 'Copying all other config files back from backup ... ';
try {
    copyDir($backupConfigDir, $settingsDir, false, function(\SplFileInfo $entry) use ($backupConfigDir) {
        return $entry->getRealPath() !== realpath($backupConfigDir . '/options/options.xml')
            && strtolower($entry->getExtension()) !== 'bak';
    });
} catch (\RuntimeException $e) {
    echo "FAILED\n";
    printException($e);
    exit(-1);
}
echo "OK\n";

echo "\nAll is done. Now you can start PhpStorm and continue to use yet another 30 days! :)\n";

// -------- Functions ----------

function confirm(string $question, bool $default = false): bool
{
    do {
        $input = ask("{$question} (y/n)", ($default ? 'yes' : 'no'));
        switch (strtolower($input)) {
            case '':
                return $default;
            case 'y':
            case 'yes':
                return true;
            case 'n':
            case 'no':
                return false;
            default:
                echo "Unexpected input \"{$input}\"\n";
        }
    } while(true);
    return false; // This return will never be reached, but PhpStorm warns about missing return statement otherwise
}

function ask(string $question, string $default = null): string
{
    return readline("{$question} " . ($default !== null ? "[$default] " : ''));
}

function askWithHistory(string $question, string $default = null): string
{
    $input = ask($question, $default);
    readline_add_history($input);
    return $input;
}

/**
 * Deletes directory recursive
 *
 * @param string $deletingDir
 * @param bool $contentsOnly
 * @return bool
 */
function deleteDir(string $deletingDir, $contentsOnly = false)
{
    $entries = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($deletingDir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($entries as $entry) {
        /** @var \SplFileInfo $entry */

        if ($entry->isDir()) {
            if (!@rmdir($entry->getPathname())) {
                throw new \RuntimeException("Failed to remove directory \"{$entry->getPathname()}\"");
            }
        } else {
            if (!unlink($entry->getPathname())) {
                throw new \RuntimeException("Failed to remove file \"{$entry->getPathname()}\"");
            }
        }
    }
    if (!$contentsOnly) {
        if (!@rmdir($deletingDir)) {
            throw new \RuntimeException("Failed to remove directory \"{$deletingDir}\"");
        }
    }
    return true;
}

/**
 * Copies directory recursive
 *
 * @param string $sourceDir
 * @param string $destinationDir
 * @param bool $contentsOnly
 * @param callable|null $filter
 */
function copyDir(string $sourceDir, string $destinationDir, $contentsOnly = false, callable $filter = null)
{
    if (!is_dir($sourceDir)) {
        throw new \RuntimeException("Source \"{$sourceDir}\" must be existing directory");
    }
    if (!is_dir($destinationDir)) {
        throw new \RuntimeException("Destination \"{$destinationDir}\" must be existing directory");
    }

    if (!$contentsOnly) {
        $destination = $destinationDir . DIRECTORY_SEPARATOR . basename($sourceDir);
        if (!is_dir($destination) && !@mkdir($destination, 0755)) {
            throw new \RuntimeException("Failed to create directory \"{$destination}\"");
        }
    }

    $sourceEntries = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

    foreach ($sourceEntries as $sourceEntry) {
        /** @var \SplFileInfo $sourceEntry */

        if ($filter && !$filter($sourceEntry)) {
            continue;
        }

        $destination = $destinationDir . DIRECTORY_SEPARATOR . getRelativePath($sourceEntry->getPathname(), $contentsOnly ? $sourceDir : dirname($sourceDir));
        // echo $sourceEntry->getPathname(), ' => ', $destination, "\n";

        if ($sourceEntry->isDir()) {
            if (!is_dir($destination) && !@mkdir($destination, 0755)) {
                throw new \RuntimeException("Failed to create directory \"{$destination}\"");
            }
        } else {
            if (!@copy($sourceEntry->getPathname(), $destination)) {
                throw new \RuntimeException("Failed to copy file \"{$sourceEntry->getPathname()}\" to \"{$destination}\"");
            }
        }
    }
}

function moveDir(string $sourceDir, string $destinationDir, $contentsOnly = false)
{
    if (!is_dir($sourceDir)) {
        throw new \RuntimeException("Source \"{$sourceDir}\" must be existing directory");
    }
    if (!is_dir($destinationDir)) {
        throw new \RuntimeException("Destination \"{$destinationDir}\" must be existing directory");
    }

    if ($contentsOnly) {
        foreach (new FilesystemIterator($sourceDir) as $sourceEntry) {
            /** @var \SplFileInfo $sourceEntry */
            $destination = $destinationDir . DIRECTORY_SEPARATOR . $sourceEntry->getFilename();
            if (!rename($sourceEntry->getPathname(), $destination)) {
                throw new \RuntimeException("Failed to move \"{$sourceEntry->getPathname()}\" to \"{$destination}\"");
            }
        }
    } else {
        $destination = $destinationDir . DIRECTORY_SEPARATOR . basename($sourceDir);
        rename($sourceDir, $destination);
    }
}

function printException(\Exception $e)
{
    echo "Error: ", $e->getMessage(), "\n";
}

function getRelativePath(string $path, string $basePath)
{
    if (strpos($path, $basePath) !== 0) {
        throw new \LogicException("Can't calculate relative path since base path not match");
    }
    return substr($path, strlen($basePath));
}

function isEmptyDir(string $path): bool
{
    return !(new \FilesystemIterator($path))->valid();
}

function mergeOptionsXml(string $oldOptionsFile, string $newOptionsFile)
{
//    if (file_exists($newOptionsFile . '.bak')) {
//        die('It seems you already executed this script earlier');
//    }

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
