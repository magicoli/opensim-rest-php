<?php

use Robo\Tasks;
use Symfony\Component\Finder\Finder;

class RoboFile extends \Robo\Tasks {

	/**
	 * Bumps the version based on the specified level (major, minor, patch, dev, beta, rc).
	 *
	 * @param string $level The level to increment (major, minor, patch, dev, beta, rc). Default: patch
	 */
	public function bumpVersion( $level = 'patch' ) {
		$versionFile = '.version';

		$currentVersion = file_exists( $versionFile ) ? file_get_contents( $versionFile ) : '0.0.0';
		$nextVersion    = $this->incrementVersion( $currentVersion, $level );
		file_put_contents( $versionFile, $nextVersion );

		$phpFiles = $this->getPhpFiles();

		$pattern = '\d+\.\d+\.\d+(-[A-Za-z]+(-[a-zA-Z0-9\.-]+)?)?';

		$this->replaceInFiles( $phpFiles, '/@version\s+' . $pattern . '/', "@version $nextVersion" );
		$this->replaceInFile( 'README.md', '/Version ' . $pattern . '/', "Version $nextVersion" );
		$this->replaceInFile( 'README.md', '/Version\/' . $pattern . '\//', "Version/$nextVersion/" );

		$this->say( "Version bumped to: $nextVersion" );

		$this->taskExec( 'php' )
		->arg( '-d' )
		->arg( 'phar.readonly=off' )
		->arg( './src/bin/build-phar.php' )
		->run();
	}

	/**
	 * Increments the version based on the specified level (major, minor, patch, dev, beta, rc).
	 *
	 * @param string $version The current version.
	 * @param string $level The level to increment (major, minor, patch, dev, beta, rc).
	 * @return string The incremented version.
	 */
	private function incrementVersion( $version, $level ) {
		$parts       = explode( '.', $version );
		$major       = (int) $parts[0];
		$minor       = (int) $parts[1];
		$patch_parts = explode( '-', $parts[2] );
		$patch       = $patch_parts[0];
		$cur_suffix  = isset( $patch_parts[1] ) ? $patch_parts[1] : null;
		$suffix_num  = isset( $patch_parts[2] ) ? (int) $patch_parts[2] : 1;
		$suffix      = '';

		switch ( $level ) {
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
				if ( ! in_array( $cur_suffix, array( 'dev', 'beta', 'rc' ) ) ) {
					error_log( '$cur_suffix ' . $cur_suffix );
					$patch++;
				}
				break;
			case 'dev':
				$suffix = "-$level";
				if ( $cur_suffix === $level ) {
					$suffix = "-$level-" . ( $suffix_num + 1 );
				} else {
					$patch++;
				}
				break;
			case 'beta':
				$suffix = "-$level";
				if ( $cur_suffix === $level ) {
					$suffix = "-$level-" . ( $suffix_num + 1 );
				} elseif ( $cur_suffix !== 'dev' ) {
					$patch++;
				}
				break;
			case 'rc':
				$suffix = "-$level";
				if ( $cur_suffix === $level ) {
					$suffix = "-$level-" . ( $suffix_num + 1 );
				} elseif ( ! in_array( $cur_suffix, array( 'dev', 'beta' ) ) ) {
					$patch++;
				}
				break;
			default:
				break;
		}

		return "$major.$minor.$patch$suffix";
	}

	/**
	 * Replaces the given pattern with the replacement string in the specified files.
	 *
	 * @param array  $files The files to perform the replacement on.
	 * @param string $pattern The pattern to search for.
	 * @param string $replacement The replacement string.
	 */
	private function replaceInFiles( $files, $pattern, $replacement ) {
		foreach ( $files as $file ) {
			$contents = file_get_contents( $file );
			$contents = preg_replace( $pattern, $replacement, $contents );
			file_put_contents( $file, $contents );
		}
	}

	/**
	 * Replaces the given pattern with the replacement string in the specified file.
	 *
	 * @param string $file The file to perform the replacement on.
	 * @param string $pattern The pattern to search for.
	 * @param string $replacement The replacement string.
	 */
	private function replaceInFile( $file, $pattern, $replacement ) {
		$contents = file_get_contents( $file );
		$contents = preg_replace( $pattern, $replacement, $contents );
		file_put_contents( $file, $contents );
	}

	/**
	 * Returns an array of PHP file paths in the current directory and its subdirectories.
	 *
	 * @return array The PHP file paths.
	 */
	private function getPhpFiles() {
		$finder = new Finder();
		$finder
		->files()
		->in( './' )
		->name( '*.php' )
		->exclude( array( 'vendor', 'node_modules' ) )
		->ignoreVCS( true )
		->ignoreDotFiles( true );

		$phpFiles = array();
		foreach ( $finder as $file ) {
			$phpFiles[] = $file->getRealPath();
		}

		return $phpFiles;
	}
}
