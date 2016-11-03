<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\Filesystem;
use Valet\Linux;

class UbuntuTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container());
        if (!(resolve(Linux::class)->getDistributionInstance() instanceof Ubuntu)) {
            $this->markTestSkipped('Tests are skipped, Ubuntu needed.');
        }
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function test_apt_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Ubuntu::class, resolve(self::class));
    }

    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with('dpkg -l | grep '.resolve(Ubuntu::class)->getConfig('php-latest').' | sed \'s_  _\t_g\' | cut -f 2')
            ->andReturn(resolve(Ubuntu::class)->getConfig('php-latest'));
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Ubuntu::class)->installed(resolve(Ubuntu::class)->getConfig('php-latest')));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with('dpkg -l | grep '.resolve(Ubuntu::class)->getConfig('php-latest').' | sed \'s_  _\t_g\' | cut -f 2')
            ->andReturn('php7.0-mcrypt
php7.0');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Ubuntu::class)->installed(resolve(Ubuntu::class)->getConfig('php-latest')));
    }

    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with('dpkg -l | grep '.resolve(Ubuntu::class)->getConfig('php-latest').' | sed \'s_  _\t_g\' | cut -f 2')
            ->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Ubuntu::class)->installed(resolve(Ubuntu::class)->getConfig('php-latest')));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with('dpkg -l | grep '.resolve(Ubuntu::class)->getConfig('php-latest').' | sed \'s_  _\t_g\' | cut -f 2')
            ->andReturn('php7.0-mcrypt');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Ubuntu::class)->installed(resolve(Ubuntu::class)->getConfig('php-latest')));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with('dpkg -l | grep '.resolve(Ubuntu::class)->getConfig('php-latest').' | sed \'s_  _\t_g\' | cut -f 2')
            ->andReturn('php7.0-mcrypt
php7.0-something-else
php7');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Ubuntu::class)->installed(resolve(Ubuntu::class)->getConfig('php-latest')));
    }

    public function test_has_installed_php_indicates_if_php_is_installed_via_apt()
    {
        $apt = Mockery::mock(Ubuntu::class.'[installed]', [new CommandLine(), new Filesystem()]);
        $apt->shouldReceive('installed')->once()->with(resolve(Ubuntu::class)->getConfig('php-latest'))->andReturn(true);
        $apt->shouldReceive('installed')->with(resolve(Ubuntu::class)->getConfig('php-56'))->andReturn(true);
        $apt->shouldReceive('installed')->with(resolve(Ubuntu::class)->getConfig('php-55'))->andReturn(true);
        $this->assertTrue($apt->hasInstalledPhp());

        $apt = Mockery::mock(Ubuntu::class.'[installed]', [new CommandLine(), new Filesystem()]);
        $apt->shouldReceive('installed')->once()->with(resolve(Ubuntu::class)->getConfig('php-latest'))->andReturn(true);
        $apt->shouldReceive('installed')->with(resolve(Ubuntu::class)->getConfig('php-56'))->andReturn(false);
        $apt->shouldReceive('installed')->with(resolve(Ubuntu::class)->getConfig('php-55'))->andReturn(false);
        $this->assertTrue($apt->hasInstalledPhp());

        $apt = Mockery::mock(Ubuntu::class.'[installed]', [new CommandLine(), new Filesystem()]);
        $apt->shouldReceive('installed')->once()->with(resolve(Ubuntu::class)->getConfig('php-latest'))->andReturn(false);
        $apt->shouldReceive('installed')->once()->with(resolve(Ubuntu::class)->getConfig('php-56'))->andReturn(false);
        $apt->shouldReceive('installed')->once()->with(resolve(Ubuntu::class)->getConfig('php-55'))->andReturn(false);
        $this->assertFalse($apt->hasInstalledPhp());
    }

    public function test_restart_restarts_the_service_using_ubuntu_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq restart');
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->restartService('dnsmasq');
    }

    public function test_stop_stops_the_service_using_ubuntu_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq stop');
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->stopService('dnsmasq');
    }

    public function test_linked_php_returns_linked_php_formula_name()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn(true);
        $files->shouldReceive('readLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn('/test/path/php7.0/test');
        swap(Filesystem::class, $files);
        $this->assertEquals(resolve(Ubuntu::class)->getConfig('php-latest'), resolve(Ubuntu::class)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn(true);
        $files->shouldReceive('readLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn('/test/path/php5.6/test');
        swap(Filesystem::class, $files);
        $this->assertEquals(resolve(Ubuntu::class)->getConfig('php-56'), resolve(Ubuntu::class)->linkedPhp());
    }

    /**
     * @expectedException DomainException
     */
    public function test_linked_php_throws_exception_if_no_php_link()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn(false);
        swap(Filesystem::class, $files);
        resolve(Ubuntu::class)->linkedPhp();
    }

    /**
     * @expectedException DomainException
     */
    public function test_linked_php_throws_exception_if_unsupported_php_version_is_linked()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn(true);
        $files->shouldReceive('readLink')->once()->with(resolve(Ubuntu::class)->getConfig('php-bin'))->andReturn('/test/path/php42/test');
        swap(Filesystem::class, $files);
        resolve(Ubuntu::class)->linkedPhp();
    }

    public function test_install_or_fail_will_install_packages()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('apt-get install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->installOrFail('dnsmasq');
    }

    /**
     * @expectedException DomainException
     */
    public function test_install_or_fail_throws_exception_on_failure()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->andReturnUsing(function ($command, $onError) {
            $onError('test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->installOrFail('dnsmasq');
    }
}
