<?php

declare(strict_types=1);

namespace App\Utils;

final class File
{
    /**
     * Deletes directory recursive
     *
     * @param string $deletingDir
     * @param bool $contentsOnly
     */
    public static function deleteDir(string $deletingDir, $contentsOnly = false)
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
    }

    /**
     * Copies directory recursive
     *
     * @param string $sourceDir
     * @param string $destinationDir
     * @param bool $contentsOnly
     * @param callable|null $filter
     */
    public static function copyDir(string $sourceDir, string $destinationDir, $contentsOnly = false, callable $filter = null)
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

            $destination = $destinationDir . DIRECTORY_SEPARATOR . self::getRelativePath($sourceEntry->getPathname(), $contentsOnly ? $sourceDir : dirname($sourceDir));
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

    /**
     * Moves directory (using rename() function)
     *
     * @param string $sourceDir
     * @param string $destinationDir
     * @param bool $contentsOnly
     */
    public static function moveDir(string $sourceDir, string $destinationDir, $contentsOnly = false)
    {
        if (!is_dir($sourceDir)) {
            throw new \RuntimeException("Source \"{$sourceDir}\" must be existing directory");
        }
        if (!is_dir($destinationDir)) {
            throw new \RuntimeException("Destination \"{$destinationDir}\" must be existing directory");
        }

        if ($contentsOnly) {
            foreach (new \FilesystemIterator($sourceDir) as $sourceEntry) {
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

    /**
     * Subtracts base path from some inner path and returns relative path
     *
     * @param string $path
     * @param string $basePath
     * @return string
     */
    public static function getRelativePath(string $path, string $basePath): string
    {
        $path = realpath($path);
        $basePath = realpath($basePath);
        if (strpos($path, $basePath) !== 0) {
            throw new \LogicException("Can't calculate relative path since path doesn't contains base path");
        }
        return substr($path, strlen($basePath));
    }

    /**
     * Checks whether directory is empty or not
     *
     * @param string $path
     * @return bool
     */
    public static function isEmptyDir(string $path): bool
    {
        return !(new \FilesystemIterator($path))->valid();
    }
}
