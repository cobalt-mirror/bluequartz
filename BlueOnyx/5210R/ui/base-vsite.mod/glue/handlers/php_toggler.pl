#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_toggler.pl
#
# This handler is run whenever the PHP extra versions get enabled/disabled.
#
# If an extra PHP was present, but is removed, then we check if Vsites (or Apache
# itself) were using it. If so, we default the Vsites back to the "stock" PHP.
# If Apache is using an extra PHP that is going to be disabled (removed), we raise
# an error instead.
# We also enable PHP-FPM services provided Vsites are actually using them. If no
# Vsites are using a given PHP-FPM pool, then we disable that PHP-FPM service and
# stop it from running.

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

    # Get our events from the event handler stack:
    $oid = $cce->event_oid();
    $obj = $cce->event_object();

    $old = $cce->event_old();
    $new = $cce->event_new();

    # Get Object PHP from CODB to find out which PHP version we use:
    @sysoids = $cce->find('PHP');
    $PHP_server_OID = $sysoids[0];
    ($ok, $PHP) = $cce->get($PHP_server_OID);
    $PHP_version = $PHP->{'PHP_version'};
    $PHP_version_os = $PHP->{'PHP_version_os'};

    #
    ## Check for presence of third party extra PHP versions:
    #

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

    # Check if known extra PHP versions are present.
    if (defined($PHP_server_OID)) {
        # Walk through all known PHP versions:
        for $phpVer (keys %known_php_versions) {
            # Poll CODB for the PHP version settings:
            ($ok, $$phpVer) = $cce->get($PHP_server_OID, $phpVer);

            $phpFpmPath = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/sbin/php-fpm";
            $fpm_service = 'php-fpm-' . $known_php_versions{$phpVer};
            $fpm_service_status = service_get_init($fpm_service);
            $known_php_fpm_pool_dirs{$phpVer} = '/etc/php-fpm-' . $known_php_versions{$phpVer} . '.d/';

            $phpBinaryPath = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/bin/php";
            $reportedVersion = `$phpBinaryPath -v|grep "(cli)"|awk {'print \$2'}`;
            chomp($reportedVersion);
            $seen_php_versions{$phpVer} = $reportedVersion;
            $seen_php_versions{$reportedVersion} = $phpVer;

            if ($ok) {
                &debug_msg("Processing PHP version $phpVer ... \n");
                # Check if there is a disabled PHP version:
                if ($$phpVer->{'enabled'} eq "0") {
                    &debug_msg("PHP version $phpVer - is disabled ... \n");

                    # Check if Apache is using a disabled version as DSO:
                    if ($reportedVersion eq $PHP_version) {
                        &debug_msg("Apache itself is using PHP version $phpVer - Raising error. \n");
                        $cce->bye('FAIL', '[[base-vsite.attemptToDisableUsedPHP]]');
                        exit(1);
                    }

                    # If so, check if any Vsite is using that version of PHP:
                    (@vhosts) = $cce->findx('Vsite');
                    foreach $vhost (@vhosts) {
                        # Get PHP version that Vhosts are using:
                        ($ok, $xvsite_php) = $cce->get($vhost, 'PHP');
                        # Check if it matches a disabled PHP version:
                        if ($xvsite_php->{'version'} eq $phpVer) {
                            &debug_msg("Vsite with OID $vhost was using PHP version $phpVer - Moving it back to OS provided PHP. \n");
                            # It does? Move it back to the default OS provided PHP:
                            ($ok) = $cce->set($vhost, 'PHP', { 'version' => 'PHPOS' });
                        }
                    }
                }
            }
        }
    }
    else {
        $cce->bye('FAIL');
        exit(1);
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