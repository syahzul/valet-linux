<?php
/**
 * Created by PhpStorm.
 * User: gordo
 * Date: 31/05/16
 * Time: 11:50 AM
 */

namespace Valet\Contracts;


interface LinuxContract
{
    function installed(string $package) : bool;
    function installOrFail(string $package);
    function restartService($services);
    function stopService($services);
    function linkedPhp() :string;
}