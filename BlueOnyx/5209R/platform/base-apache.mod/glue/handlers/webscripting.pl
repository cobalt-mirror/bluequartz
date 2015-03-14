#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: webscripting.pl
#
# This is triggered by changes to the Vsite PHP, CGI, or SSI namespaces.
# It maintains the scripting part of the vhost conf

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

$extra_PHP_basepath = '/home/solarspeed/';

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

&debug_msg("Init of webscripting.pl\n");

my($ok, $php) = $cce->get($cce->event_oid(), 'PHP');
($ok, my $cgi) = $cce->get($cce->event_oid(), 'CGI');
($ok, my $ssi) = $cce->get($cce->event_oid(), 'SSI');
($ok, my $Vsite) = $cce->get($cce->event_oid(), '');

###

#
## Check for presence of third party extra PHP versions:
#

# Known PHP versions:
%known_php_versions = (
                        'PHP53' => '5.3',
                        'PHP54' => '5.4',
                        'PHP55' => '5.5',
                        'PHP56' => '5.6'
                        );

%php_handlers = (
                        'PHPOS' => 'x-httpd-suphp',
                        'PHP53' => 'x-httpd-suphp-5.3',
                        'PHP54' => 'x-httpd-suphp-5.4',
                        'PHP55' => 'x-httpd-suphp-5.5',
                        'PHP56' => 'x-httpd-suphp-5.6'
                        );

@sysoids = $cce->find('PHP');
$PHP_server_OID = $sysoids[0];
($ok, $PHP) = $cce->get($PHP_server_OID);

# Check if known extra PHP versions are present. If so, update CODB accordingly:
if (defined($PHP_server_OID)) {
    for $phpVer (keys %known_php_versions) {
        $phpFpmPath = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/sbin/php-fpm";
        $phpBinaryPath = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/bin/php";
        $known_php_inis{$phpVer} = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/etc/php.ini";
        $reportedVersion = `$phpBinaryPath -v|grep "(cli)"|awk {'print \$2'}`;
        chomp($reportedVersion);
        $seen_php_versions{$phpVer} = $reportedVersion;
        $seen_php_versions{$reportedVersion} = $phpVer;
        # Add FPM pool files to the mix:
        $xpool_file = '/etc/php-fpm-' . $known_php_versions{$phpVer} . '.d/www.conf';
        $xpool_directory = '/etc/php-fpm-' . $known_php_versions{$phpVer} . '.d/';
        if ( -f $xpool_file ) {
            $known_php_fpm_pool_files{$phpVer} = $xpool_file;
            $known_php_fpm_pool_directories{$phpVer} = $xpool_directory;
            $known_php_fpm_pool_services{$phpVer} = 'php-fpm-' . $known_php_versions{$phpVer};
        }
        else {
            &debug_msg("Not adding $xpool_file as it's not there! \n");
        }
    }
}
else {
    $cce->bye('FAIL');
    exit(1);
}
###

if(not $ok)
{
    $cce->bye('FAIL', '[[base-apache.cantReadWebScripting]]');
    &debug_msg("Fail1: [[base-apache.cantReadWebScripting]]\n");
    exit(1);
}

if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{name}), 
                            *edit_vhost, $php, $cgi, $ssi, $vsite->{fqdn}))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    &debug_msg("Fail2: [[base-apache.cantEditVhost]]\n");
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub edit_vhost
{
    my ($in, $out, $php, $cgi, $ssi, $fqdn) = @_;

    my $script_conf = '';

    my $begin = '# BEGIN WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';
    my $end = '# END WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';

    if ($cgi->{enabled})
    {
        $script_conf .= "AddHandler cgi-wrapper .cgi\nAddHandler cgi-wrapper .pl\n";
    }

    if ($ssi->{enabled})
    {
        $script_conf .= "AddHandler server-parsed .shtml\nAddType text/html .shtml\n";
    }

    # Get prefered_siteAdmin for ownerships:
    if ($php->{prefered_siteAdmin} ne "") {
        $prefered_siteAdmin = $php->{prefered_siteAdmin};
    }
    else {
        $prefered_siteAdmin = 'apache';
    }

    if ($php->{enabled})
    {
                # Handle suPHP:
                if ($php->{suPHP_enabled}) {
                    # Handle suPHP:

                    &debug_msg("suPHP: I should be using $php_handlers{$php->{version}} for this.\n");

                    $script_conf .= "<IfModule mod_suphp.c>\n";
                    $script_conf .= "    suPHP_Engine on\n";
                    $script_conf .= "    suPHP_UserGroup $prefered_siteAdmin $Vsite->{name}\n";
                    $script_conf .= "    AddType application/" . $php_handlers{$php->{version}} . " .php\n";
                    $script_conf .= "    AddHandler " . $php_handlers{$php->{version}} . " .php .php5 .php4 .php3 .phtml\n";
                    $script_conf .= "    suPHP_AddHandler " . $php_handlers{$php->{version}} . "\n";
                    $script_conf .= "    suPHP_ConfigPath $Vsite->{basedir}/\n";
                    $script_conf .= "</IfModule>\n";
                }
                # Handle mod_ruid2:
                elsif ($php->{mod_ruid_enabled}) {
                    $script_conf .= "<FilesMatch \\.php\$>\n";
                    $script_conf .= "    SetHandler application/x-httpd-php\n";
                    $script_conf .= "</FilesMatch>\n";
                    $script_conf .= "<IfModule mod_ruid2.c>\n";
                    $script_conf .= "     RMode config\n";
                    $script_conf .= "     RUidGid $prefered_siteAdmin $Vsite->{name}\n";
                    $script_conf .= "</IfModule>\n";
                }
                # Handle FPM/FastCGI:
                elsif ($php->{fpm_enabled}) {
                        # Assign a port number based on the GID of the Vsite.
                        # Our GID's for siteX start at 1001, so we need to add
                        # 8000 to it to get above 9000.
                        ($_name, $_passwd, $gid, $_members) = getgrnam($Vsite->{name});
                        if ($gid ne "") {
                            $fpmPort = $gid+8000;
                        }
                        else {
                            # Fallback if we didn't get a port:
                            $fpmPort = 9000;
                        }
                        # Double fallback:
                        if ($fpmPort < 9000) {
                            $fpmPort = 9000;
                        }
                        # Join the config together:
                        $script_conf .= "ProxyPassMatch ^/(.*\\.php(/.*)?)\$ fcgi://127.0.0.1:$fpmPort$Vsite->{basedir}/web/\n";
                }
                # Handle 'regular' PHP via DSO:
                else {
                    $script_conf .= "<FilesMatch \\.php\$>\n";
                    $script_conf .= "    SetHandler application/x-httpd-php\n";
                    $script_conf .= "</FilesMatch>\n";
                } 
    }

    my $last;
    my $enableSSL = 0;
    while(<$in>)
    {
        if(/^<\/VirtualHost>/i) { $last = $_; last; }

        if(/^$begin$/)
        {
            while(<$in>)
            {
                if(/^$end$/) { last; }
            }
        }
        else
        {
            print $out $_;
        }
    }

    print $out $begin, "\n";
    print $out $script_conf;
    print $out $end, "\n";
    print $out $last;

    # Preserve the remainder of the config file and continue with the SSL
    # Section if it is present:
    while(<$in>) {
        if(/^<\/VirtualHost>/i) { $enableSSL = 1; $last = $_; last; }

        if(/^$begin$/) {
            while(<$in>) {
                if(/^$end$/) { last; }
            }
        }
        else {
            print $out $_;
        }
    }

    if ($enableSSL) {
        print $out $begin, "\n";
        print $out $script_conf;
        print $out $end, "\n";
        print $out $last;

        while(<$in>) {
            print $out $_;
        }
    }

    return 1;
}

# For debugging:
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

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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