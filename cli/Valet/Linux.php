<?php

namespace Valet;

use Valet\Contracts\LinuxContract;

class Linux implements LinuxContract
{
    public $cli, $files;
    protected $distribution;

    /**
     * Create a new Linux instance.
     *
     * @param CommandLine $cli
     * @param Filesystem  $files
     *
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->distribution = $this->getDistributionInstance();
    }

    private function getDistributionInstance() : LinuxContract
    {
        $match = [];
        preg_match('/.*-(\w*)/i', strtolower(php_uname('r')), $match);
        switch ($match[1]) {
            case 'manjaro':
            case 'arch':
                return new Arch($this->cli, $this->files);
            default:
                return new Ubuntu($this->cli, $this->files);
        }
    }

    /**
     * Determine if a compatible PHP version is installed.
     *
     * @return bool
     */
    public function hasInstalledPhp() :bool
    {
        return $this->installed(get_config('php-latest'))
        || $this->installed(get_config('php-56'))
        || $this->installed(get_config('php-55'));
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param string $package
     *
     * @return bool
     */
    public function installed(string $package) :bool
    {
        return $this->distribution->installed($package);
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param string $package
     *
     * @return void
     */
    public function ensureInstalled(string $package)
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param string $package
     *
     * @return void
     */
    public function installOrFail(string $package)
    {
        $this->distribution->installOrFail($package);
    }

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    public function restartService($services)
    {
        $this->distribution->restartService($services);
    }

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    public function stopService($services)
    {
        $this->distribution->stopService($services);
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    public function linkedPhp() :string
    {
        return $this->distribution->linkedPhp();
    }
}
