<?php
/**
 * Created by PhpStorm.
 * User: gordo
 * Date: 31/05/16
 * Time: 12:48 PM
 */

namespace Valet;


use DomainException;
use Valet\Contracts\LinuxContract;

class Arch implements LinuxContract
{
    public $cli;
    public $files;
    /**
     * Arch constructor.
     * @param CommandLine $cli
     * @param Filesystem  $files
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    function installed(string $package) : bool
    {
        return explode(' ', $this->cli->run('pacman -Qe ' . $package))[0] == $package;
    }

    function installOrFail(string $package)
    {
        output('<info>[' . $package . '] is not installed, installing it now...</info> 🍻');

        $this->cli->run('packman -S ' . $package, function ($errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Unable to install [' . $package . '].');
        });
    }

    function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo systemctl restart ' . $service);
        }
    }

    function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo systemctl stop ' . $service);
        }
    }

    function linkedPhp() :string
    {
       return "php";
    }
}