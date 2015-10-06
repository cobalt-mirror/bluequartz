#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: webscripting.pl
#
# This is triggered by changes to the Vsite PHP, CGI, or SSI namespaces.
# It maintains the scripting part of the vhost conf

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

$extra_PHP_basepath = '/home/solarspeed/';

my $cce = new CCE;
$cce->connectfd();

&debug_msg("Init of webscripting.pl\n");

# The catch: This handler got fired by a transaction to either a 'Vsite' or 'VirtualHost' object.
# We need the info from the 'Vsite' object, as only that contains the namespaces for PHP, CGI 
# and so on. Hence we now check what we're working with and make sure we get all the right data:

# 'name' is identical in 'Vsite' and 'VirtualHost' objects:
my $vsite = $cce->event_object();

# Get the Event-OID to find out if we're firing on a 'VirtualHost' of 'Vsite' transaction:
my ($ok, $my_event_oid_content) = $cce->get($cce->event_oid(), '');
&debug_msg("Class for " . $vsite->{name} . " is: " . $my_event_oid_content->{'CLASS'} . "\n");

if ($my_event_oid_content->{'CLASS'} ne "Vsite") {
    # Ok, so we're probably firing on a 'VirtualHost' object. Get the right Vsite object instead:
    my ($Vsite_oid) = $cce->find('Vsite', { 'name' => $vsite->{name} });
    if ($Vsite_oid) {
        &debug_msg("OID for " . $vsite->{name} . " Vsite obj is: " . $Vsite_oid . "\n");
        ($ok, $php) = $cce->get($Vsite_oid, 'PHP');
        ($ok, $cgi) = $cce->get($Vsite_oid, 'CGI');
        ($ok, $ssi) = $cce->get($Vsite_oid, 'SSI');
        ($ok, $Vsite) = $cce->get($Vsite_oid, '');
    }
    else {
        $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
        &debug_msg("Fail0: [[base-apache.cantEditVhost]]\n");
        exit(1);
    }
}
else {
    # The 'Vsite' object already *is* the Event-Object:
    ($ok, $php) = $cce->get($cce->event_oid(), 'PHP');
    ($ok, $cgi) = $cce->get($cce->event_oid(), 'CGI');
    ($ok, $ssi) = $cce->get($cce->event_oid(), 'SSI');
    ($ok, $Vsite) = $cce->get($cce->event_oid(), '');
}

######################################## Start vsite/php.d parsing stuff ##########################################

&debug_msg("Vsite name/group is: " . $Vsite->{name} . "\n");

#
## PHP Application related extra-settings:
#

# Find out homedir of the Vsite:
my ($VirtualHost_oid) = $cce->find('Vsite', { 'name' => $Vsite->{name} });
if ($VirtualHost_oid) {
    ($ok, $VirtualHost) = $cce->get($VirtualHost_oid, '');
}
else {
        $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
        &debug_msg("Fail0b: [[base-apache.cantEditVhost]]\n");
        exit(1);
}
my $site_dir = homedir_get_group_dir($VirtualHost->{name}, $VirtualHost->{volume});
&debug_msg("home $site_dir\n");
$custom_php_include_dir = $site_dir . "/php.d";
if (!-d $custom_php_include_dir) {
    system("mkdir $custom_php_include_dir");
}
if (-d $custom_php_include_dir) {
    system('chown', '-R', "root.$VirtualHost->{name}", $custom_php_include_dir);
    system('chmod', '-R', "0644", $custom_php_include_dir);
}

# Get PHPVsite to determine how PHP is configured for this site:
($sys_oid) = $cce->find('Vsite', {'name' => $Vsite->{name}});
($ok, $active_php_settings) = $cce->get($sys_oid, "PHPVsite");

# Start sane:
@php_vars_that_need_changing = ();
@php_vars_that_need_changing_the_hard_way = ();
@app_php_vars = ();
$vsite_php_settings_writeoff_extra = {};

# GUI supported PHP flags that we check against:
@php_gui_supported = qw/register_globals safe_mode safe_mode_gid max_execution_time max_input_time memory_limit post_max_size upload_max_filesize allow_url_fopen allow_url_include/;

# Do we have custom HTTPD config? Eg PHP settings?
if (-d $custom_php_include_dir) {
    foreach my $fp (glob("$custom_php_include_dir/*.ini")) {
        open my $fh, "<", $fp or die "can't read open '$fp': $OS_ERROR";
        while (<$fh>) {
            &debug_msg("Processing $fh with content: $_ \n");
            if (($_ =~ /^#(.*)$/i) || ($_ =~ /^;(.*)$/i)) {
                # Skip lines that start with # or ; and therefore are comments:
                next;
            }
            ($php_operator, $php_flag, $php_value) = split(/\s/, $_);
            push(@app_php_vars, ("$php_operator|$php_flag|$php_value"));
        }
        close $fh or die "can't read close '$fp': $OS_ERROR";
    }
}

# Walk through the PHP config we inherited from the conf file:
foreach (@app_php_vars) {
    ($php_operator, $php_flag, $php_value) = split(/\|/, $_);
    if ((length($php_operator) gt "0") && (length($php_flag) gt "0") && (length($php_value) gt "0")) {
        &debug_msg("Debug1: $php_operator - $php_flag - $php_value \n");
    
        # Check if the flag from conf file is amongst the supported GUI flags:
        if (grep $_ eq $php_flag, @php_gui_supported) {
            &debug_msg("Debug2: App wants $php_flag set to $php_value, but Vsite has $php_flag set to $active_php_settings->{$php_flag} \n");
    
            # Check if we really need to bump the sites PHP settings for this 
            # parameter, or if the desired setting is worse than what we already have:
    
            # 'php_admin_flag' are usually 'On' or 'Off'. In our cases 'Off' is the safe and therefore preferred setting:
            if ($php_operator eq "php_admin_flag") {
                if (($php_value =~ /^On$/i) && ($active_php_settings->{$php_flag} eq "Off")) {
                &debug_msg("Debug3: App wants $php_flag 'On' which is usually 'Off'. \n");
                push(@php_vars_that_need_changing, ("$php_operator|$php_flag|$php_value"));
                }
                else {
                    &debug_msg("Debug3: Ignoring request to change $php_flag to 'On' as it is already 'On'. \n");
                }
            }
            # 'php_admin_value' that we care about are usually numerical with optional unit:
            if (($php_operator eq "php_admin_value") || ($php_operator eq "php_value")) {
    
                # We need to strip the optional unit 'M' from the end of our values:
                undef $value_worker;
                undef $value_worker_gui;
                if ($php_value =~ /^\d+M$/i) { 
                    $value_worker = substr($php_value, 0, -1);
                }
                else {
                    $value_worker = $php_value;
                }
                if ($active_php_settings->{$php_flag} =~ /^\d+M$/i) { 
                    $value_worker_gui = substr($active_php_settings->{$php_flag}, 0, -1);
                }
                else {
                    $value_worker_gui = $active_php_settings->{$php_flag};
                }
    
                # Now compare if the desired value is greater than what the GUI already has set:
                if ($value_worker > $value_worker_gui) {
                    push(@php_vars_that_need_changing, ("$php_operator|$php_flag|$php_value"));
                    &debug_msg("Debug4: App wants $php_flag set to $value_worker, but GUI has it set to $value_worker_gui. \n");
                }
                else {
                    &debug_msg("Debug4: Ignoring request to change $php_flag to $value_worker. \n");
                }
            }
        }
        else {
            # When we get here, then this means that the App config file contained options which the GUI doesn't support.
            # So we need to take care of them the old fashioned way and store them away separately for now:
            &debug_msg("Pushing $php_operator|$php_flag|$php_value \n");
            if (!$vsite_php_settings_writeoff_extra->{"$php_flag"}) {
                # Preventing duplicates, first one wins.
                if ((length($php_operator) gt "0") && (length($php_flag) gt "0") && (length($php_value) gt "0")) {
                    push(@php_vars_that_need_changing_the_hard_way, ("$php_operator|$php_flag|$php_value"));
                    $vsite_php_settings_writeoff_extra->{"$php_flag"} = "$php_value";
                }
            }
        }
    }
}

# At this point we looped through all the supported PHP vars. Push them to the GUI using supported methods:
$easy_way = scalar(@php_vars_that_need_changing);
if ($easy_way > 0) {
    $php_gui_writeoff = {};
    foreach (@php_vars_that_need_changing) {
        ($php_operator, $php_flag, $php_value) = split(/\|/, $_);
        &debug_msg("Pushing $php_flag - $php_value for the easy way. \n");
        $php_gui_writeoff->{"$php_flag"} = "$php_value";
    }
}

# Now we need to take care of any PHP related settings that are not handled directly by CODB:
$hard_way = scalar(@php_vars_that_need_changing_the_hard_way);
if ($hard_way > 0) {
    &debug_msg("There are $hard_way unsupported flags left to settle.\n");
    $php_extra_cfg  = "";
    foreach (@php_vars_that_need_changing_the_hard_way) {
        ($php_operator, $php_flag, $php_value) = split(/\|/, $_);
        if (($php_operator eq "php_admin_value") || ($php_operator eq "php_value") || ($php_operator eq "php_flag")) {
            if ((length($php_operator) gt "0") && (length($php_flag) gt "0") && (length($php_value) gt "0")) {
                $php_extra_cfg .=  "    $php_operator $php_flag $php_value\n";
                &debug_msg("$php_operator $php_flag $php_value\n");
            }
        }
    }
}

# Write off the changes to CODB and set 'force_update' so that the config files (httpd, php.ini's and FPM-pools)
# are updated accordingly:
if (($easy_way > 0) || ($hard_way > 0)) {
    # Adding the 'force_update' switch so that the GUI updates the PHP settings:
    $php_gui_writeoff->{'force_update'} = time();
    # Writing our wishlist of changes off to CCE, pushing the supported PHP changes active. This updates both the 
    # sitex Vhost container as well as the separate php.ini of suPHP enabled sites.
    $cce->set($sys_oid, 'PHPVsite', $php_gui_writeoff);
}

######################################## End vsite/php.d parsing stuff ##########################################

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
        $script_conf .= "<Directory $Vsite->{basedir}/web>\n";
        $script_conf .= "    AddHandler cgi-script .cgi .pl\n";
        $script_conf .= "    Options +ExecCGI\n";
        $script_conf .= "</Directory>\n";
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