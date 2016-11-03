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
    public $cli;
    public $files;

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
     * Enables the given Homebrew services.
     *
     * @param
     */
    public function enableService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo systemctl enable '.$service);
        }
    }

    /**
     * Enables the given Homebrew services.
     *
     * @param
     */
    public function disableService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo systemctl disable '.$service);
        }
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    public function linkedPhp() :string
    {
        if (!$this->files->isLink($this->getConfig('php-bin'))) {
            throw new DomainException('Unable to determine linked PHP.');
        }

        $resolvedPath = $this->files->readLink($this->getConfig('php-bin'));

        if (strpos($resolvedPath, $this->getConfig('php-latest')) !== false) {
            return $this->getConfig('php-latest');
        } elseif (strpos($resolvedPath, $this->getConfig('php-56')) !== false) {
            return $this->getConfig('php-56');
        } elseif (strpos($resolvedPath, $this->getConfig('php-55')) !== false) {
            return $this->getConfig('php-55');
        } else {
            throw new DomainException('Unable to determine linked PHP.');
        }
    }

    public function getConfig(string $value):string
    {
        $config = [
            // PHP binary path
            'php-bin'           => '/usr/bin/php',

            // Latest PHP
            'php-latest'        => 'php7.0',
            'fpm-service'       => 'php7.0-fpm',
            'fpm-config'        => '/etc/php/7.0/fpm/pool.d/www.conf',

            // Caddy/Systemd
            'systemd-caddy'     => '/lib/systemd/system/caddy.service',
            'systemd-caddy-fpm' => '/var/run/php/php7.0-fpm.sock',

            // PHP 5.6
            'php-56'            => 'php5.6',
            'fpm56-service'     => 'php5.6-fpm',
            'fpm56-config'      => '/etc/php/5.6/php-fpm.conf',

            // PHP 5.5
            'php-55'            => 'php5.5',
            'fpm55-service'     => 'php5.5-fpm',
            'fpm55-config'      => '/etc/php/5.5/php-fpm.conf',
            'network-manager'   => 'network-manager',
        ];

        return $config[$value];
    }
}
