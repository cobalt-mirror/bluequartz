#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_fpm-checker.pl
#
# This runs at the end of 'Vsite._DESTROY', 'Vsite.PHP.*' or 'PHP.*'
# transactions and makes sure that PHP-FPM is enabled and running.
#
# It also handles the extra PHP-FPM pools of extra PHP packages.
# The 'master' PHP-FPM process of the OS should always be running.
# Those of extra PHP versions naturally should only be running if
# that extra PHP package in question is there *and* there is 
# actually a group config file in it. Which would indicate that at
# least one Vsite is using that pool.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

# Uncomment correct type:
#$whatami = "constructor";
$whatami = "handler";

#
#### No configureable options below!
#

$extra_PHP_basepath = '/home/solarspeed/';

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;
use Sauce::Config;
use FileHandle;
use File::Copy;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();

    # Known PHP versions:
    %known_php_versions = (
                            'PHP53' => '5.3',
                            'PHP54' => '5.4',
                            'PHP55' => '5.5',
                            'PHP56' => '5.6',
                            'PHP70' => '7.0',
                            'PHP71' => '7.1',
                            'PHP72' => '7.2',
                            'PHP73' => '7.3',
                            'PHP74' => '7.4',
                            'PHP75' => '7.5',
                            'PHP76' => '7.6',
                            'PHP77' => '7.7',
                            'PHP78' => '7.8',
                            'PHP79' => '7.9'
                            );

    # Known PHP Paths:
    %known_php_paths = (
                            'PHP53' => '/etc/php-fpm-5.3.d/',
                            'PHP54' => '/etc/php-fpm-5.4.d/',
                            'PHP55' => '/etc/php-fpm-5.5.d/',
                            'PHP56' => '/etc/php-fpm-5.6.d/',
                            'PHP70' => '/etc/php-fpm-7.0.d/',
                            'PHP71' => '/etc/php-fpm-7.1.d/',
                            'PHP72' => '/etc/php-fpm-7.2.d/',
                            'PHP73' => '/etc/php-fpm-7.3.d/',
                            'PHP74' => '/etc/php-fpm-7.4.d/',
                            'PHP75' => '/etc/php-fpm-7.5.d/',
                            'PHP76' => '/etc/php-fpm-7.6.d/',
                            'PHP77' => '/etc/php-fpm-7.7.d/',
                            'PHP78' => '/etc/php-fpm-7.8.d/',
                            'PHP79' => '/etc/php-fpm-7.9.d/'
                            );

    # Known PHP Services:
    %known_php_services = (
                            'PHP53' => 'php-fpm-5.3',
                            'PHP54' => 'php-fpm-5.4',
                            'PHP55' => 'php-fpm-5.5',
                            'PHP56' => 'php-fpm-5.6',
                            'PHP70' => 'php-fpm-7.0',
                            'PHP71' => 'php-fpm-7.1',
                            'PHP72' => 'php-fpm-7.2',
                            'PHP73' => 'php-fpm-7.3',
                            'PHP74' => 'php-fpm-7.4',
                            'PHP75' => 'php-fpm-7.5',
                            'PHP76' => 'php-fpm-7.6',
                            'PHP77' => 'php-fpm-7.7',
                            'PHP78' => 'php-fpm-7.8',
                            'PHP79' => 'php-fpm-7.9'
                            );

    # Get OID of 'ActiveMonitor':
    @AMOID = $cce->find('ActiveMonitor');
    for $phpVer (keys %known_php_paths) {

        # Set 'ActiveMonitor' NameSpace:
        $am_NameSpace = 'FPM' . $phpVer;

        # Get current state of ActiveMonitor.$am_NameSpace Obj:
        ($ok, $ActiveMonitor) = $cce->get($AMOID[0], "$am_NameSpace");

        # Is the service in question enabled?
        $ServiceStatus = service_get_init($known_php_services{$phpVer});

        &debug_msg("Processing PHP-FPM check for $known_php_versions{$phpVer} \n");
        if (-d '/home/solarspeed/php-' . $known_php_versions{$phpVer}) {
            &debug_msg("Directory /home/solarspeed/php-$known_php_versions{$phpVer} exists. \n");

            &debug_msg("Service Status for: " . $known_php_services{$phpVer} . " is: $ServiceStatus\n");

            # Check for pools files in this pool:
            $xcheck_file = $known_php_paths{$phpVer} . 'site*.conf';
            $xcheck = `ls -k1 $xcheck_file|wc -l`;
            chomp($xcheck);
            if ($xcheck eq '0') {
                &debug_msg("Stopping PHP-FPM ($known_php_services{$phpVer}) and turning it off as no Vsite is using it.\n");
                if (($ActiveMonitor->{enabled} ne "0") && ($ServiceStatus eq "1")) {
                    ($ok) = $cce->set($AMOID[0], "$am_NameSpace", { 'enabled' => '0' });
                    &debug_msg("Telling Sauce::Service to turn $known_php_services{$phpVer} off and to stop it.\n");
                    service_set_init($known_php_services{$phpVer}, 'off');
                    service_run_init($known_php_services{$phpVer}, 'stop');
                }
            }
            else {
                &debug_msg("Restarting PHP-FPM ($known_php_services{$phpVer}) and making sure it is enabled as Vsites are using it.\n");
                if (($ActiveMonitor->{enabled} ne "0") && ($ServiceStatus eq "1")) {
                    ($ok) = $cce->set($AMOID[0], "$am_NameSpace", { 'enabled' => '1' });
                    &debug_msg("Telling Sauce::Service to turn $known_php_services{$phpVer} on and to restart it.\n");
                    service_set_init($known_php_services{$phpVer}, 'on');
                    service_run_init($known_php_services{$phpVer}, 'restart');
                }
                # Note to self:
                # =============
                # condreload:   Not reloading if service is stopped
                # condrestart:  Not restarting if service is stopped
                # reload:       Not reloading if service is stopped
                #
                # So all we can really do is a bloody 'restart'. Way to go, Systemd!
            }
        }
        else {
            &debug_msg("Service Status for: " . $known_php_services{$phpVer} . " is: $ServiceStatus\n");
            if (($ActiveMonitor->{enabled} ne "0") && ($ServiceStatus eq "1")) {
                &debug_msg("Stopping PHP-FPM ($known_php_services{$phpVer}) and turning it off as this PKG is not installed!\n");
                ($ok) = $cce->set($AMOID[0], "$am_NameSpace", { 'enabled' => '0' });
                service_set_init($known_php_services{$phpVer}, 'off');
                service_run_init($known_php_services{$phpVer}, 'stop');
            }
        }
    }
    # Unconditionally enable and restart master PHP-FPM:
    ($ok, $ActiveMonitor) = $cce->get($AMOID[0], 'PHPFPMMASTER');
    if ($ActiveMonitor->{enabled} ne "1") {
        ($ok) = $cce->set($AMOID[0], "PHPFPMMASTER", { 'enabled' => '1' });
    }
    service_set_init('php-fpm', 'on');
    service_run_init('php-fpm', 'restart');

    # Now the *real* catch: Check if they're running:
    for $phpVer (keys %known_php_paths) {
        if (-d '/home/solarspeed/php-' . $known_php_versions{$phpVer}) {
            &debug_msg("Verifying FPM-Status for PHP-$known_php_versions{$phpVer}.\n");

            # Check for pools files in this pool:
            $xcheck_file = $known_php_paths{$phpVer} . 'site*.conf';
            $xcheck = `ls -k1 $xcheck_file|wc -l`;
            chomp($xcheck);
            if ($xcheck gt '0') {
                $process_running = `ps -axf|grep "php-fpm: master process"|grep -v grep|grep $known_php_versions{$phpVer}|wc -l`;
                chomp($process_running);
                if ($process_running eq "0") {
                    &debug_msg("Restarting PHP-FPM ($known_php_services{$phpVer}) as it failed startup.\n");
                    service_run_init($known_php_services{$phpVer}, 'restart');
                }
            }
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 