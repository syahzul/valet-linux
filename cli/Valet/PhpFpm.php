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
        if (!$this->linux->installed($this->linux->getConfig('php-latest')) &&
            !$this->linux->installed($this->linux->getConfig('php-56')) &&
            !$this->linux->installed($this->linux->getConfig('php-55'))) {
            $this->linux->ensureInstalled($this->linux->getConfig('php-latest'));
        }

        $this->files->ensureDirExists('/var/log', user());

        $this->updateConfiguration();

        $this->linux->enableServie($this->linux->getConfig('fpm-service'));

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

        $this->linux->restartService($this->linux->getConfig('fpm-service'));
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public function stop()
    {
        $this->linux->stopService([
            $this->linux->getConfig('fpm55-service'),
            $this->linux->getConfig('fpm56-service'),
            $this->linux->getConfig('fpm-service'),
        ]);
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath()
    {
        if ($this->linux->linkedPhp() === $this->linux->getConfig('php-latest')) {
            return $this->linux->getConfig('fpm-config');
        } elseif ($this->linux->linkedPhp() === $this->linux->getConfig('php-56')) {
            return $this->linux->getConfig('fpm56-config');
        } elseif ($this->linux->linkedPhp() === $this->linux->getConfig('php-55')) {
            return $this->linux->getConfig('fpm55-config');
        } else {
            throw new DomainException('Unable to find php-fpm config.');
        }
    }

    public function getFpmService()
    {
        if ($this->linux->linkedPhp() === $this->linux->getConfig('php-latest')) {
            return $this->linux->getConfig('fpm-service');
        } elseif ($this->linux->linkedPhp() === $this->linux->getConfig('php-56')) {
            return $this->linux->getConfig('fpm56-service');
        } elseif ($this->linux->linkedPhp() === $this->linux->getConfig('php-55')) {
            return $this->linux->getConfig('fpm55-service');
        } else {
            throw new DomainException('Unable to find php fpm service.');
        }
    }
}
