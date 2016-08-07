<?php

namespace Valet;

use DomainException;
use Symfony\Component\Process\Process;

class PhpFpm
{
    public $linux;
    public $cli;
    public $files;

    /**
     * Create a new PHP FPM class instance.
     *
     * @param Linux       $linux
     * @param CommandLine $cli
     * @param Filesystem  $files
     *
     * @return void
     */
    public function __construct(Linux $linux, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->linux = $linux;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    public function install()
    {
        if (!$this->linux->installed(get_config('php-latest')) &&
            !$this->linux->installed(get_config('php-56')) &&
            !$this->linux->installed(get_config('php-55'))) {
            $this->linux->ensureInstalled(get_config('php-latest'));
        }

        $this->files->ensureDirExists('/var/log', user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public function updateConfiguration()
    {
        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^listen.owner = .+$/m', 'listen.owner = '.user(), $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public function restart()
    {
        $this->stop();

        $this->linux->restartService(get_config('fpm-service'));
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public function stop()
    {
        $this->linux->stopService([
            get_config('fpm55-service'),
            get_config('fpm56-service'),
            get_config('fpm-service'),
        ]);
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath()
    {
        if ($this->linux->linkedPhp() === get_config('php-latest')) {
            return get_config('fpm-config');
        } elseif ($this->linux->linkedPhp() === get_config('php-56')) {
            return get_config('fpm56-config');
        } elseif ($this->linux->linkedPhp() === get_config('php-55')) {
            return get_config('fpm55-config');
        } else {
            throw new DomainException('Unable to find php-fpm config.');
        }
    }

    public function getFpmService()
    {
        if ($this->linux->linkedPhp() === get_config('php-latest')) {
            return get_config('fpm-service');
        } elseif ($this->linux->linkedPhp() === get_config('php-56')) {
            return get_config('fpm56-service');
        } elseif ($this->linux->linkedPhp() === get_config('php-55')) {
            return get_config('fpm55-service');
        } else {
            throw new DomainException('Unable to find php fpm service.');
        }
    }
}
