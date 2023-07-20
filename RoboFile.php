<?php

use Robo\Tasks;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;

class RoboFile extends \Robo\Tasks
{
    /**
     * Bumps the version based on the specified level (major, minor, or patch).
     *
     * @param string $level The level to increment (major, minor, or patch). Default: patch
     */
    public function bumpVersion($level = 'patch')
    {
        $versionFile = '.version';

        $currentVersion = file_exists($versionFile) ? file_get_contents($versionFile) : '0.0.0';
        $nextVersion = $this->incrementVersion($currentVersion, $level);
        file_put_contents($versionFile, $nextVersion);

        $phpFiles = $this->getPhpFiles();

        $this->replaceInFiles($phpFiles, '/@version\s+\d+\.\d+\.\d+/', "@version $nextVersion");

        $this->replaceInFile('README.md', '/Version \d+\.\d+\.\d+/', "Version $nextVersion");

        $this->replaceInFile('README.md', '/Version\/\d+\.\d+\.\d+\//', "Version/$nextVersion/");

        $this->say("Version bumped to: $nextVersion");

        $this->taskExec('php')
            ->arg('-d')
            ->arg('phar.readonly=off')
            ->arg('./src/bin/build-phar.php')
            ->run();
    }

    /**
     * Increments the version based on the specified level (major, minor, or patch).
     *
     * @param string $version The current version.
     * @param string $level The level to increment (major, minor, or patch).
     * @return string The incremented version.
     */
    private function incrementVersion($version, $level)
    {
        $parts = explode('.', $version);
        $major = (int)$parts[0];
        $minor = (int)$parts[1];
        $patch = (int)$parts[2];

        switch ($level) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }

        return "$major.$minor.$patch";
    }

    /**
     * Replaces the given pattern with the replacement string in the specified files.
     *
     * @param array $files The files to perform the replacement on.
     * @param string $pattern The pattern to search for.
     * @param string $replacement The replacement string.
     */
    private function replaceInFiles($files, $pattern, $replacement)
    {
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $contents = preg_replace($pattern, $replacement, $contents);
            file_put_contents($file, $contents);
        }
    }

    /**
     * Replaces the given pattern with the replacement string in the specified file.
     *
     * @param string $file The file to perform the replacement on.
     * @param string $pattern The pattern to search for.
     * @param string $replacement The replacement string.
     */
    private function replaceInFile($file, $pattern, $replacement)
    {
        $contents = file_get_contents($file);
        $contents = preg_replace($pattern, $replacement, $contents);
        file_put_contents($file, $contents);
    }

    /**
     * Returns an array of PHP file paths in the current directory and its subdirectories.
     *
     * @return array The PHP file paths.
     */
    private function getPhpFiles()
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in('./')
            ->name('*.php')
            ->exclude('vendor')
            ->exclude('node_modules')
            ->ignoreVCS(true)
            ->ignoreDotFiles(true);

        $phpFiles = [];
        foreach ($finder as $file) {
            $phpFiles[] = $file->getRealPath();
        }

        return $phpFiles;
    }
}
