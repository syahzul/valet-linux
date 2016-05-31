<?php

namespace Valet;

use Exception;
use DomainException;
use Valet\Contracts\LinuxContract;

abstract class Linux implements LinuxContract
{
    var $cli, $files;

    /**
     * Create a new Ubuntu instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $package
     * @return bool
     */
    abstract function installed(string $package) :bool;

    /**
     * Determine if a compatible PHP version is installed.
     *
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->installed(get_config('php-latest'))
            || $this->installed(get_config('php-56'))
            || $this->installed(get_config('php-55'));
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $package
     * @return void
     */
    function ensureInstalled(string $package)
    {
        if (! $this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $package
     * @return void
     */
    abstract function installOrFail(string $package);

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    abstract function restartService($services);

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    abstract function stopService($services);

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    abstract function linkedPhp() :string;
}
