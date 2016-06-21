<?php
/**
 * Created by PhpStorm.
 * User: gordo
 * Date: 31/05/16
 * Time: 11:55 AM.
 */
namespace Valet;

use DomainException;
use Valet\Contracts\LinuxContract;

class Ubuntu implements LinuxContract
{
    public $cli, $files;

    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
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
        return in_array($package,
            explode(PHP_EOL, $this->cli->run('dpkg -l | grep '.$package.' | sed \'s_  _\t_g\' | cut -f 2')));
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
        output('<info>['.$package.'] is not installed, installing it now...</info> 🍻');

        $this->cli->run('apt-get install '.$package, function ($errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Unable to install ['.$package.'].');
        });
    }

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    public function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo service '.$service.' restart');
        }
    }

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    public function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo service '.$service.' stop');
        }
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    public function linkedPhp() :string
    {
        if (!$this->files->isLink(get_config('php-bin'))) {
            throw new DomainException('Unable to determine linked PHP.');
        }

        $resolvedPath = $this->files->readLink(get_config('php-bin'));

        if (strpos($resolvedPath, get_config('php-latest')) !== false) {
            return get_config('php-latest');
        } elseif (strpos($resolvedPath, get_config('php-56')) !== false) {
            return get_config('php-56');
        } elseif (strpos($resolvedPath, get_config('php-55')) !== false) {
            return get_config('php-55');
        } else {
            throw new DomainException('Unable to determine linked PHP.');
        }
    }
}
