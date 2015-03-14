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
                            'PHP56' => '5.6'
                            );

    # Known PHP Paths:
    %known_php_paths = (
                            'PHP53' => '/etc/php-fpm-5.3.d/',
                            'PHP54' => '/etc/php-fpm-5.4.d/',
                            'PHP55' => '/etc/php-fpm-5.5.d/',
                            'PHP56' => '/etc/php-fpm-5.6.d/'
                            );

    # Known PHP Services:
    %known_php_services = (
                            'PHP53' => 'php-fpm-5.3',
                            'PHP54' => 'php-fpm-5.4',
                            'PHP55' => 'php-fpm-5.5',
                            'PHP56' => 'php-fpm-5.6'
                            );

    # Get OID of 'ActiveMonitor':
	@AMOID = $cce->find('ActiveMonitor');

	for $phpVer (keys %known_php_paths) {

		# Set 'ActiveMonitor' NameSpace:
		$am_NameSpace = 'FPM' . $phpVer;

		&debug_msg("Processing PHP-FPM check for $known_php_versions{$phpVer} \n");
		if (-d '/home/solarspeed/php-' . $known_php_versions{$phpVer}) {
			&debug_msg("Directory /home/solarspeed/php-$known_php_versions{$phpVer} exists. \n");

			# Check for pools files in this pool:
            $xcheck_file = $known_php_paths{$phpVer} . 'site*.conf';
            $xcheck = `ls -k1 $xcheck_file|wc -l`;
            chomp($xcheck);
            if ($xcheck eq '0') {
				&debug_msg("Stopping PHP-FPM ($known_php_services{$phpVer}) and turning it off as no Vsite is using it.\n");
				($ok) = $cce->set($AMOID[0], "$am_NameSpace", { 'enabled' => '0' });
				service_set_init($known_php_services{$phpVer}, 'off');
				service_run_init($known_php_services{$phpVer}, 'stop');
            }
            else {
				&debug_msg("Restarting PHP-FPM ($known_php_services{$phpVer}) and making sure it is enabled as Vsites are using it.\n");
				($ok) = $cce->set($AMOID[0], "$am_NameSpace", { 'enabled' => '1' });
				service_set_init($known_php_services{$phpVer}, 'on');
				service_run_init($known_php_services{$phpVer}, 'restart');

				# Note to self:
				# =============
				# condreload:	Not reloading if service is stopped
				# condrestart:	Not restarting if service is stopped
				# reload:		Not reloading if service is stopped
				#
				# So all we can really do is a bloody 'restart'. Way to go, Systemd!
            }
		}
		else {
			&debug_msg("Stopping PHP-FPM ($known_php_services{$phpVer}) and turning it off as this PKG is not installed!\n");
			($ok) = $cce->set($AMOID[0], "$am_NameSpace", { 'enabled' => '0' });
			service_set_init($known_php_services{$phpVer}, 'off');
			service_run_init($known_php_services{$phpVer}, 'stop');
		}
	}
	# Unconditionally enable and restart master PHP-FPM:
	($ok) = $cce->set($AMOID[0], "PHPFPMMASTER", { 'enabled' => '1' });
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