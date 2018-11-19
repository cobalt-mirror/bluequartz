#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: virtual_host.pl
# handle the creation of configuration files for individual vhosts
#

use CCE;
use Sauce::Config;
use Sauce::Util;
use Sauce::Service;
use Base::Httpd qw(httpd_get_vhost_conf_file);
use FileHandle;
use Data::Dumper;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
        &debug_msg("Debugging enabled for virtual_host.pl\n");
}

my $cce = new CCE;

$cce->connectfd();

my $vhost = $cce->event_object();
my $vhost_new = $cce->event_new();
my $vhost_old = $cce->event_old();

# write SSL configuration in /etc/httpd/conf/vhosts/siteX, not use mod_perl

#
### Get all Parameters from CODB that we need:
#

my ($void) = $cce->find('Vsite', {'name' => $vhost->{name}});
my ($ok, $vsite) = $cce->get($void);
my ($ok, $userwebs) = $cce->get($void, 'USERWEBS');
my ($PHPoid) = $cce->find('PHP');
my ($ok, $PHP_server) = $cce->get($PHPoid);

my ($ok, $PHP_Vsite) = $cce->get($void, "PHPVsite");
my ($ok, $vsite_php) = $cce->get($void, "PHP");
my ($ok, $vhost_ssl) = $cce->get($void, 'SSL');
my ($ok, $cgi) = $cce->get($void, 'CGI');
my ($ok, $ssi) = $cce->get($void, 'SSI');
my ($ok, $redirect) = $cce->get($void, 'REDIRECT');
my ($ok, $java) = $cce->get($void, 'Java');
my ($ok, $NginxVsite) = $cce->get($void, 'Nginx');

$vhost->{basedir} = $vsite->{basedir};
# Please note: 'Vsite' has 'basedir', while 'VirtualHost' has 'documentRoot'. And 'documentRoot' is in /web:
$vhost->{documentRoot} = $vsite->{basedir} . '/web';
my ($ok, $ssl) = $cce->get($void, 'SSL');
$vhost->{ssl_expires} = $ssl->{expires};

# Get "System" . "Web":
my ($oid) = $cce->find('System');
my ($ok, $objWeb) = $cce->get($oid, 'Web');
my ($ok, $Nginx) = $cce->get($oid, 'Nginx');
my ($ok, $tzdata) = $cce->get($oid, "Time");
my $timezone = $tzdata->{'timeZone'};

# Extra PHP location:
my $extra_PHP_basepath = '/home/solarspeed/';

# Location of php.ini - may get overridden by thirdparty_check():
my $php_ini = "/etc/php.ini";

#
### PHP Version related static defines:
#
%php_handlers = (
                    'PHPOS' => 'x-httpd-suphp',
                    'PHP53' => 'x-httpd-suphp-5.3',
                    'PHP54' => 'x-httpd-suphp-5.4',
                    'PHP55' => 'x-httpd-suphp-5.5',
                    'PHP56' => 'x-httpd-suphp-5.6',
                    'PHP70' => 'x-httpd-suphp-7.0',
                    'PHP71' => 'x-httpd-suphp-7.1',
                    'PHP72' => 'x-httpd-suphp-7.2',
                    'PHP73' => 'x-httpd-suphp-7.3',
                    'PHP74' => 'x-httpd-suphp-7.4',
                    'PHP75' => 'x-httpd-suphp-7.5',
                    'PHP76' => 'x-httpd-suphp-7.6',
                    'PHP77' => 'x-httpd-suphp-7.7',
                    'PHP78' => 'x-httpd-suphp-7.8',
                    'PHP79' => 'x-httpd-suphp-7.9'
                    );

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

# Known PHP-FPM pool ports:
%known_pool_ports = (
                        'OS' => '9000',
                        'PHP53' => '8953',
                        'PHP54' => '8954',
                        'PHP55' => '8955',
                        'PHP56' => '8956',
                        'PHP70' => '8957',
                        'PHP71' => '8958',
                        'PHP72' => '8959',
                        'PHP73' => '8960',
                        'PHP74' => '8961',
                        'PHP75' => '8962',
                        'PHP76' => '8963',
                        'PHP77' => '8964',
                        'PHP78' => '8965',
                        'PHP79' => '8966'
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

# HTTP and SSL ports:
$httpPort = "80";
if ($objWeb->{'httpPort'}) {
    $httpPort = $objWeb->{'httpPort'};
}
$sslPort = "443";
if ($objWeb->{'sslPort'}) {
    $sslPort = $objWeb->{'sslPort'};
}

$HSTS = '0';
$HSTS_head = "";
$HSTS_tail = "";
$HSTS_line = '';
if ($objWeb->{'HSTS'} == '1') {
    $HSTS = $objWeb->{'HSTS'};
    $HSTS_head = "Header always set Strict-Transport-Security";
    $HSTS_tail = "max-age=15768000";
    $HSTS_line = $HSTS_head . ' "' . $HSTS_tail .';"';
}

&debug_msg("HTTP Port: $httpPort\n");
&debug_msg("SSL Port: $sslPort\n");
&debug_msg("HSTS: $HSTS\n");
&debug_msg("Base::Httpd::vhost_dir: $Base::Httpd::vhost_dir\n");
&debug_msg("vhost->{name}: $vhost->{name}\n");

# make sure the directory exists before trying to edit the file
if (!-d $Base::Httpd::vhost_dir)
{
    Sauce::Util::makedirectory($Base::Httpd::vhost_dir);
    Sauce::Util::chmodfile(0751, $Base::Httpd::vhost_dir);
}

if (!Sauce::Util::editfile(
        httpd_get_vhost_conf_file($vhost->{name}), *edit_vhost, $vhost))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

Sauce::Util::chmodfile(0644, httpd_get_vhost_conf_file($vhost->{name}));

# create include file
my $include_file = httpd_get_vhost_conf_file($vhost->{name}) . '.include';
if (!-e $include_file) {
    my $fh = new FileHandle(">$include_file");
    if ($fh) {
    print $fh "# ${include_file}\n";
    print $fh "# user customizations can be added here.\n\n";
    $fh->close();
    }
    Sauce::Util::chmodfile(0644, $include_file);
}

$cce->bye('SUCCESS');
exit(0);


sub edit_vhost
{
    my ($in, $out, $vhost) = @_;

    my $user_root = $vhost->{documentRoot};
    &debug_msg("user_root: " . $vhost->{documentRoot} . "\n");
    $user_root =~ s#web$#users/\$1/web/\$3#;
    &debug_msg("user_root: $user_root \n");

    my $site_root = $vhost->{documentRoot};
    $site_root =~ s/\/web$//;

    my $include_file = httpd_get_vhost_conf_file($vhost->{name}) . '.include';

    #
    ### 'Web Server Aliases' & 'Web Server Alias redirects':
    #

    $aliasRewrite = '';
    $aliasRewriteSSL = '';
    &debug_msg("Before trigger.\n");
    my $ServerAlias = '';
    my @webAliases = ();
    if ($vsite->{webAliases}) {
        @webAliases = $cce->scalar_to_array($vsite->{webAliases});
        $ServerAlias = 'ServerAlias ' . join(' ', @webAliases);
        $nginx_ServerAlias = join(' ', @webAliases);
        &debug_msg("ServerAlias: $ServerAlias\n");
    }
    if (($vsite->{webAliases}) && ($vsite->{webAliasRedirects} eq "0")) {
        &debug_msg("After trigger.\n");
        foreach $alias (@webAliases) {
            &debug_msg("Alias alt: $alias\n");
            $alias =~ s/^\*/\\*/g;
            &debug_msg("Alias neu: $alias\n");
            $aliasRewrite .= "RewriteCond %{HTTP_HOST}                !^$alias(:$httpPort)?\$ [NC]\n";
            $aliasRewriteSSL .= "RewriteCond %{HTTP_HOST}                !^$alias(:$sslPort)?\$ [NC]\n";
        }
    }

    #
    ### USERWEBS:
    #

    my $userDirStatus = '   UserDir disabled';
    if ($userwebs->{enabled}) {
        $userDirStatus = '   UserDir enabled';
    }
    $conf_userwebs = "<IfModule mod_userdir.c>\n$userDirStatus\n</IfModule>";

    #
    ### Vsite PHP Settings - including parsing of the php.d directory:
    #

    ######################################## Start vsite/php.d parsing stuff ##########################################

    #
    ## PHP Application related extra-settings:
    #

    my $platform = $PHP_server->{'PHP_version'};
    if ($platform >= "5.3") {
        # More modern PHP found:
        $legacy_php = "0";
    }
    else {
        # Older PHP found:
        $legacy_php = "1";
    }

    # Find out homedir of the Vsite:
    &debug_msg("site_dir: $vsite->{basedir}\n");
    $custom_php_include_dir = $vsite->{basedir} . "/php.d";
    &debug_msg("custom_php_include_dir $custom_php_include_dir\n");
    if (!-d $custom_php_include_dir) {
        system("mkdir $custom_php_include_dir");
    }
    if (-d $custom_php_include_dir) {
        system('chown', '-R', "root.$vsite->{name}", $custom_php_include_dir);
        system('chmod', '-R', "0644", $custom_php_include_dir);
    }

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
                &debug_msg("Debug2: App wants $php_flag set to $php_value, but Vsite has $php_flag set to $PHP_Vsite->{$php_flag} \n");
        
                # Check if we really need to bump the sites PHP settings for this 
                # parameter, or if the desired setting is worse than what we already have:
        
                # 'php_admin_flag' are usually 'On' or 'Off'. In our cases 'Off' is the safe and therefore preferred setting:
                if ($php_operator eq "php_admin_flag") {
                    if (($php_value =~ /^On$/i) && ($PHP_Vsite->{$php_flag} eq "Off")) {
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
                    if ($PHP_Vsite->{$php_flag} =~ /^\d+M$/i) { 
                        $value_worker_gui = substr($PHP_Vsite->{$php_flag}, 0, -1);
                    }
                    else {
                        $value_worker_gui = $PHP_Vsite->{$php_flag};
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
                    if (($php_operator eq "php_admin_value") || ($php_operator eq "php_value") || ($php_operator eq "php_flag")) {
                        if ((length($php_operator) gt "0") && (length($php_flag) gt "0") && (length($php_value) gt "0")) {
                            push(@php_vars_that_need_changing_the_hard_way, ("$php_operator|$php_flag|$php_value"));
                            $vsite_php_settings_writeoff_extra->{"$php_flag"} = "$php_value";
                        }
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
        $php_fpm_extra_cfg  = "";
        foreach (@php_vars_that_need_changing_the_hard_way) {
            ($php_operator, $php_flag, $php_value) = split(/\|/, $_);
            if (($php_operator eq "php_admin_value") || ($php_operator eq "php_value") || ($php_operator eq "php_flag")) {
                if ((length($php_operator) gt "0") && (length($php_flag) gt "0") && (length($php_value) gt "0")) {
                    $php_extra_cfg .=  "$php_operator $php_flag $php_value\n";
                    $php_fpm_extra_cfg .=  "$php_operator" . '[' . "$php_flag" . ']' . " = $php_value\n";
                    &debug_msg("$php_operator $php_flag $php_value\n");
                }
            }
        }
    }

    ######################################## End vsite/php.d parsing stuff ##########################################

    ######################################## Start: General PHP Handling   ##########################################

    #
    ## Check for presence of third party extra PHP versions:
    #
    
    # Check if known extra PHP versions are present. If so, update CODB accordingly:
    if (defined($PHPoid)) {
        for $phpVer (keys %known_php_versions) {
            $phpFpmPath = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/sbin/php-fpm";
            $phpBinaryPath = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/bin/php";
            $known_php_inis{$phpVer} = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/etc/php.ini";
            $reportedVersion = `$phpBinaryPath -v|grep "(cli)"|awk {'print \$2'}`;
            chomp($reportedVersion);
            $seen_php_versions{$phpVer} = $reportedVersion;
            $seen_php_versions{$reportedVersion} = $phpVer;
            $phpPEARpath{$phpVer} = $extra_PHP_basepath . "php-" . $known_php_versions{$phpVer} . "/share/pear";
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

    # Add the OS related PHP into the mix as well:
    $known_php_inis{'PHPOS'} = $php_ini;
    $seen_php_versions{'PHPOS'} = $PHP_server->{'PHP_version_os'};
    $seen_php_versions{$PHP_server->{'PHP_version_os'}} = 'PHPOS';
    $phpPEARpath{'PHPOS'} = "/usr/share/pear";
    $known_php_fpm_pool_files{'PHPOS'} = '/etc/php-fpm.d/www.conf';
    $known_php_fpm_pool_directories{'PHPOS'} = '/etc/php-fpm.d/';
    $known_php_fpm_pool_services{'PHPOS'} = 'php-fpm';

    # Check for presence of third party config file:
    &thirdparty_check;

    if ((!defined $vsite_php->{'version'}) || ($vsite_php->{'version'} eq "")) {
        $vsite_php->{'version'} = $seen_php_versions{$PHP_server->{'PHP_version'}};
    }

    &debug_msg("Vsite is supposed to be using $vsite_php->{'version'} and " . $seen_php_versions{$vsite_php->{'version'}} . " \n");

    # Find out what 'open_basedir' needs to be set to:
    &open_basedir_handling;

    ######################################## End: General PHP Handling   ##########################################

    ######################################## Start: PHP Vsite Config    ##########################################

    my $PHP_Config_Lines = "# BEGIN PHP SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.\n";

    if ($vsite_php->{"enabled"} eq "1") {

        # Making sure 'safe_mode_include_dir' has the bare minimum defaults:
        @smi_temporary = split(":", $PHP_Vsite->{"safe_mode_include_dir"});
        @smi_baremetal_minimums = ('/usr/sausalito/configs/php/', '.');
        @smi_temp_joined = (@smi_temporary, @smi_baremetal_minimums);
        
        # Remove duplicates:
        foreach my $var ( @smi_temp_joined ){
            if ( ! grep( /$var/, @safe_mode_include_dir ) ){
                push(@safe_mode_include_dir, $var );
            }
        }
        $PHP_Vsite->{"safe_mode_include_dir"} = join(":", @safe_mode_include_dir);

        # Making sure 'safe_mode_allowed_env_vars' has the bare minimum defaults:
        @smaev_temporary = split(",", $PHP_Vsite->{"safe_mode_allowed_env_vars"});
        @smi_baremetal_minimums = ('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
        @smaev_temp_joined = (@smaev_temporary, @smi_baremetal_minimums);
        
        # Remove duplicates:
        foreach my $var ( @smaev_temp_joined ){
            if ( ! grep( /$var/, @safe_mode_allowed_env_vars ) ){
                push(@safe_mode_allowed_env_vars, $var );
            }
        }
        $PHP_Vsite->{"safe_mode_allowed_env_vars"} = join(",", @safe_mode_allowed_env_vars);

        if ($legacy_php == "1") {
            # These options only apply to PHP versions prior to PHP-5.3:
            if ($PHP_Vsite->{"safe_mode"} ne "") {
                $PHP_Config_Lines .= 'php_admin_flag safe_mode ' . $PHP_Vsite->{"safe_mode"} . "\n"; 
            }
            if ($PHP_Vsite->{"safe_mode_gid"} ne "") {
                $PHP_Config_Lines .= 'php_admin_flag safe_mode_gid ' . $PHP_Vsite->{"safe_mode_gid"} . "\n";
            }
            if ($PHP_Vsite->{"safe_mode_allowed_env_vars"} ne "") {
                $PHP_Config_Lines .= 'php_admin_value safe_mode_allowed_env_vars ' . $PHP_Vsite->{"safe_mode_allowed_env_vars"} . "\n"; 
            }
            if ($PHP_Vsite->{"safe_mode_exec_dir"} ne "") {
                $PHP_Config_Lines .= 'php_admin_value safe_mode_exec_dir ' . $PHP_Vsite->{"safe_mode_exec_dir"} . "\n"; 
            }

            if ($PHP_Vsite->{"safe_mode_include_dir"} ne "") {
                $PHP_Config_Lines .= 'php_admin_value safe_mode_include_dir ' . $PHP_Vsite->{"safe_mode_include_dir"} . "\n"; 
            }
            if ($PHP_Vsite->{"safe_mode_protected_env_vars"} ne "") {
                $PHP_Config_Lines .= 'php_admin_value safe_mode_protected_env_vars ' . $PHP_Vsite->{"safe_mode_protected_env_vars"} . "\n"; 
            }
        }

        if ($PHP_Vsite->{"register_globals"} ne "") {
            $PHP_Config_Lines .= 'php_admin_flag register_globals ' . $PHP_Vsite->{"register_globals"} . "\n"; 
        }
        if ($PHP_Vsite->{"allow_url_fopen"} ne "") {
            $PHP_Config_Lines .= 'php_admin_flag allow_url_fopen ' . $PHP_Vsite->{"allow_url_fopen"} . "\n"; 
        }
        if ($PHP_Vsite->{"allow_url_include"} ne "") {
            $PHP_Config_Lines .= 'php_admin_flag allow_url_include ' . $PHP_Vsite->{"allow_url_include"} . "\n"; 
        }

        if ($PHP_Vsite->{"open_basedir"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value open_basedir ' . $PHP_Vsite->{"open_basedir"} . "\n";
        }

        if ($PHP_Vsite->{"post_max_size"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value post_max_size ' . $PHP_Vsite->{"post_max_size"} . "\n"; 
        }
        if ($PHP_Vsite->{"upload_max_filesize"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value upload_max_filesize ' . $PHP_Vsite->{"upload_max_filesize"} . "\n"; 
        }
        if ($PHP_Vsite->{"max_execution_time"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value max_execution_time ' . $PHP_Vsite->{"max_execution_time"} . "\n"; 
        }
        if ($PHP_Vsite->{"max_input_time"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value max_input_time ' . $PHP_Vsite->{"max_input_time"} . "\n"; 
        }
        if ($PHP_Vsite->{"max_input_vars"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value max_input_vars ' . $PHP_Vsite->{"max_input_vars"} . "\n"; 
        }
        if ($PHP_Vsite->{"memory_limit"} ne "") {
            $PHP_Config_Lines .= 'php_admin_value memory_limit ' . $PHP_Vsite->{"memory_limit"} . "\n"; 
        }

        # Email related:
        $PHP_Config_Lines .= 'php_admin_flag mail.add_x_header On' . "\n";
        $PHP_Config_Lines .= 'php_admin_value sendmail_path /usr/sausalito/sbin/phpsendmail' . "\n";
        $PHP_Config_Lines .= 'php_admin_value auto_prepend_file /usr/sausalito/configs/php/set_php_headers.php' . "\n";

        # Add vsite/php.d/* include related extras:
        $PHP_Config_Lines .= "# From $custom_php_include_dir/:\n";
        $PHP_Config_Lines .= $php_extra_cfg;
    }
    $PHP_Config_Lines .= '# END PHP SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';

    ######################################## End: PHP Vsite Config    ##########################################

    ######################################## Start: suPHP and PHP-FPM Handling  ################################

    # Handle custom php.ini for suPHP enabled sites:
    $basedir_vsite = $vsite->{"basedir"};
    $custom_php_ini_path = $vsite->{'basedir'} . "/php.ini";
    
    # If it exists, use the php.ini of the version of PHP specified:
    if ( -f $known_php_inis{$vsite_php->{'version'}}) {
        $php_ini = $known_php_inis{$vsite_php->{'version'}};
    }

    if ($vsite_php->{'suPHP_enabled'} == "1") {

        # If there is already a custom php.ini, delete it first:
        if (-f $custom_php_ini_path) {
            &debug_msg("Deleting old $custom_php_ini_path through php_vsite_handler.pl \n");
            system("/usr/bin/chattr -i $custom_php_ini_path");
            system("/bin/rm -f $custom_php_ini_path");
        }

        # Copy main php.ini (thirdparty one or from distribution) to vsite's basedir:
        system("/bin/cp $php_ini $custom_php_ini_path");
        system("/bin/chmod 644 $custom_php_ini_path");
        system("/bin/chown root:root $custom_php_ini_path");

        # Run a search and replace through Vsite php.ini to update it with the PHP
        # settings configured for this site:
        &debug_msg("Configuring $custom_php_ini_path through php_vsite_handler.pl, using $php_ini as template. \n");
        &edit_php_ini;

        # Protect Vsite php.ini against modification.
        # As it's root owned and 644, this may be a bit redundant:
        system("/usr/bin/chattr +i $custom_php_ini_path");
    }
    else {
        # suPHP disabled. Delete custom php.ini:
        if (-f $custom_php_ini_path) {
            system("/usr/bin/chattr -i $custom_php_ini_path");
            system("/bin/rm -f $custom_php_ini_path");
            &debug_msg("Deleting $custom_php_ini_path through php_vsite_handler.pl \n");
        }
    }

    # For better readability it's in if-else:
    if ($vsite_php->{'fpm_enabled'} == "1") {
        &handle_fpm_pools;
    }
    else {
        &handle_fpm_pools;
    }

    ######################################## End: suPHP and PHP-FPM Handling  ################################

    ######################################## Start: Web-Owner changes         ################################

    # Get current UID/GID of $vhost->{documentRoot}'s /web directory:
    if ( -d $vhost->{documentRoot}) {
        my ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat($vhost->{documentRoot});
        my ($uname) = getpwuid($uid);
        my ($gname) = getgrgid($gid);
        &debug_msg("Directory " . $vhost->{documentRoot} . " is owned by $uname:$gname \n");

        # Check if $vhost->{documentRoot} is owned by currently desired UID (and that the GID is correct) and if not,
        # then fix the bloody ownerships:
        if ($vsite_php->{prefered_siteAdmin}) {
            if (($vsite_php->{prefered_siteAdmin} ne $uname) || ($vsite->{name} ne $gname)) {

                # /web directory of the Vsite in question:
                $webdir = $vhost->{documentRoot};

                # Vsite basedir dir:
                $vsite_basedir = $vhost->{basedir};

                # New User of the Vsite:
                $new_owner = $vsite_php->{prefered_siteAdmin};

                # Group of the Vsite:
                $new_GID = $vsite->{name};

                &debug_msg("Web Owner: Currently: $uname:$gname - desired: $new_owner:$new_GID\n");

                # Chown this sites /web to the prefered UID and also re-set the GID while we're at it:
                &debug_msg("/bin/chown -R $new_owner:$new_GID $webdir\n");
                system("/bin/chown -R $new_owner:$new_GID $webdir");
                # Also chown the basedir of the site to this users UID, but don't do it recursively:
                &debug_msg("/bin/chown $new_owner:$new_GID $vsite_basedir\n");
                system("/bin/chown $new_owner:$new_GID $vsite_basedir");
                # If we have subdomains under /vhosts, we need to chown them as well:
                if (-d "$vsite_basedir/vhosts") {
                    &debug_msg("/bin/chown -R $new_owner:$new_GID $vsite_basedir/vhosts\n");
                    system("/bin/chown -R $new_owner:$new_GID $vsite_basedir/vhosts");
                }
            }
        }
    }

    ######################################## End: Web-Owner changes           ################################

    ######################################## Start: Handle WebScripting Cfg   ################################

    my $WebScripting_Config_Lines = "# BEGIN WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.\n";

    if ($cgi->{enabled}) {
        $WebScripting_Config_Lines .= "AddHandler cgi-wrapper .cgi\n";
        $WebScripting_Config_Lines .= "AddHandler cgi-wrapper .pl\n";       
    }

    if ($ssi->{enabled}) {
        $WebScripting_Config_Lines .= "AddHandler server-parsed .shtml\nAddType text/html .shtml\n";
    }

    # Get prefered_siteAdmin for ownerships:
    if ($vsite_php->{prefered_siteAdmin} ne "") {
        $prefered_siteAdmin = $vsite_php->{prefered_siteAdmin};
    }
    else {
        $prefered_siteAdmin = 'apache';
    }

    if (($redirect->{enabled}) && (($redirect->{type} eq "302") || ($redirect->{type} eq "permanent") || ($redirect->{type} eq "proxy")) && ($redirect->{target} ne "")) {
        if ($redirect->{type} eq "permanent") {
            $redirect->{type} = '301';
        }
        if ($redirect->{type} eq 'proxy') {
            $redirect_line = "RewriteOptions inherit\n";
            $redirect_line .= "ProxyRequests off\n";
            $redirect_line .= "SSLProxyEngine on\n";
            $redirect_line .= "ProxyPreserveHost On\n";
            $redirect_line .= "<Location />\n";
            $redirect_line .= "    ProxyPass " . $redirect->{target} . "\n";
            $redirect_line .= "    ProxyPassReverse " . $redirect->{target} . "\n";
            $redirect_line .= "    Order allow,deny\n";
            $redirect_line .= "    Allow from all\n";
            $redirect_line .= "</Location>\n";
        }
        else {
            $redirect_line = "RewriteOptions inherit\n";
            $redirect_line .= "### START REDIRECT ###\n";
            $redirect_line .= 'RewriteRule ^(.*)$ ' . $redirect->{target} . ' [DPI,L,R=' . $redirect->{type} . "]\n";
            $redirect_line .= "### END REDIRECT ###\n";
        }
    }
    else {
        $redirect_line = "RewriteOptions inherit\n";
    }

    if ($vsite_php->{enabled}) {
                # Handle suPHP:
                if ($vsite_php->{suPHP_enabled}) {
                    # Handle suPHP:

                    &debug_msg("suPHP: I should be using $php_handlers{$vsite_php->{version}} for this.\n");

                    $WebScripting_Config_Lines .= "<IfModule mod_suphp.c>\n";
                    $WebScripting_Config_Lines .= "    suPHP_Engine on\n";
                    $WebScripting_Config_Lines .= "    suPHP_UserGroup $prefered_siteAdmin $vsite->{name}\n";
                    $WebScripting_Config_Lines .= "    AddType application/" . $php_handlers{$vsite_php->{version}} . " .php\n";
                    $WebScripting_Config_Lines .= "    AddHandler " . $php_handlers{$vsite_php->{version}} . " .php .php5 .php4 .php3 .phtml\n";
                    $WebScripting_Config_Lines .= "    suPHP_AddHandler " . $php_handlers{$vsite_php->{version}} . "\n";
                    $WebScripting_Config_Lines .= "    suPHP_ConfigPath $vsite->{basedir}/\n";
                    $WebScripting_Config_Lines .= "</IfModule>\n";
                }
                # Handle mod_ruid2:
                elsif ($vsite_php->{mod_ruid_enabled}) {
                    $WebScripting_Config_Lines .= "<FilesMatch \\.php\$>\n";
                    $WebScripting_Config_Lines .= "    SetHandler application/x-httpd-php\n";
                    $WebScripting_Config_Lines .= "</FilesMatch>\n";
                    $WebScripting_Config_Lines .= "<IfModule mod_ruid2.c>\n";
                    $WebScripting_Config_Lines .= "     RMode config\n";
                    $WebScripting_Config_Lines .= "     RUidGid $prefered_siteAdmin $vsite->{name}\n";
                    $WebScripting_Config_Lines .= "</IfModule>\n";
                }
                # Handle FPM/FastCGI:
                elsif ($vsite_php->{fpm_enabled}) {
                        # Assign a port number based on the GID of the Vsite.
                        # Our GID's for siteX start at 1001, so we need to add
                        # 8000 to it to get above 9000.
                        ($_name, $_passwd, $gid, $_members) = getgrnam($vsite->{name});
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
                        # Join the config together - and take the possibility into account that the documentRoot might be different than usual:
                        # Define default:
                        $normal_docroot = $vhost->{'documentRoot'} . "/web";
                        if ($vhost->{'documentRoot'} ne $normal_docroot) {
                            $VsiteDocRoot = $vhost->{'documentRoot'} . "/";
                        }
                        else {
                            $VsiteDocRoot = $vsite->{basedir} . "/";
                        }
                        # Replace double slash with single slash:
                        $VsiteDocRoot =~ s/\/\//\//g;
                        $WebScripting_Config_Lines .= "RewriteCond $VsiteDocRoot%{REQUEST_FILENAME} -f\n";
                        $WebScripting_Config_Lines .= "RewriteRule ^/(.+\.php(/.*)?)\$ fcgi://127.0.0.1:$fpmPort$VsiteDocRoot\$1 [L,P]\n";
                        $WebScripting_Config_Lines .= "RewriteCond $VsiteDocRoot%{REQUEST_FILENAME} \"\\.php/\"\n";
                        $WebScripting_Config_Lines .= "RewriteRule ^/(.+\.php(/.*)?)\$ fcgi://127.0.0.1:$fpmPort$VsiteDocRoot\$1 [L,P]\n";
                }
                # Handle 'regular' PHP via DSO:
                else {
                    $WebScripting_Config_Lines .= "<FilesMatch \\.php\$>\n";
                    $WebScripting_Config_Lines .= "    SetHandler application/x-httpd-php\n";
                    $WebScripting_Config_Lines .= "</FilesMatch>\n";
                } 
    }
    $WebScripting_Config_Lines .= '# END WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';

    ######################################## End: Handle WebScripting Cfg   ################################

    ######################################## Start: Java Config lines       ################################

    my $Java_Config_Lines = '';
    if ($java->{enabled} eq "1") {
        $Java_Config_Lines .= "# BEGIN JSP SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.\n";
        # $Java_Config_Lines .= "JkMount /* ajp13\n";
        $Java_Config_Lines .= "JkMount /*.jsp ajp13\n";
        $Java_Config_Lines .= "JkMount /servlet/* ajp13\n";
        $Java_Config_Lines .= '# END JSP SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';
    }

    ######################################## End: Java Config lines         ################################

    #
    ### Start Editing Vhost container:
    #

    &debug_msg("Editing Vhost container for $vhost->{fqdn} and using Port $httpPort\n");

    if (!$vhost->{serverAdmin}) {
        $vhost->{serverAdmin} = 'admin';
    }

    # Assemble VirtualHost HTTP/HTTPS IP Address lines and IP related Rewrite-Conditions:
    my $http_ipline = '';
    my $https_ipline = '';
    my $ip_rewrite_cond_http = '';
    my $ip_rewrite_cond_https = '';
    if (($vhost->{ipaddr} ne "") && ($vhost->{ipaddrIPv6} ne "")) {
        # Dual stack:
        $http_ipline = $vhost->{ipaddr} . ':' . $httpPort . ' [' . $vhost->{ipaddrIPv6} . ']:' . $httpPort;
        $https_ipline = $vhost->{ipaddr} . ':' . $sslPort . ' [' . $vhost->{ipaddrIPv6} . ']:' . $sslPort;
        $ip_rewrite_cond_http .= 'RewriteCond %{HTTP_HOST}                !^' . $vhost->{ipaddr} . '(:' . $httpPort . ')?$' . "\n";
        $ip_rewrite_cond_http .= 'RewriteCond %{HTTP_HOST}                !^\[' . $vhost->{ipaddrIPv6} . '\](:' . $httpPort . ')?$';

        $ip_rewrite_cond_https .= 'RewriteCond %{HTTP_HOST}                !^' . $vhost->{ipaddr} . '(:' . $sslPort . ')?$' . "\n";
        $ip_rewrite_cond_https .= 'RewriteCond %{HTTP_HOST}                !^\[' . $vhost->{ipaddrIPv6} . '\](:' . $sslPort . ')?$';
    }
    elsif (($vhost->{ipaddr} eq "") && ($vhost->{ipaddrIPv6} ne "")) {
        # IPv6 only:
        $http_ipline = '[' . $vhost->{ipaddrIPv6} . ']:' . $httpPort;
        $https_ipline = '[' . $vhost->{ipaddrIPv6} . ']:' . $sslPort;
        $ip_rewrite_cond_http .= 'RewriteCond %{HTTP_HOST}                !^\[' . $vhost->{ipaddrIPv6} . '\](:' . $httpPort . ')?$';
        $ip_rewrite_cond_https .= 'RewriteCond %{HTTP_HOST}                !^\[' . $vhost->{ipaddrIPv6} . '\](:' . $sslPort . ')?$';
    }
    else {
        # IPv4 only (default):
        $http_ipline = $vhost->{ipaddr} . ':' . $httpPort;
        $https_ipline = $vhost->{ipaddr} . ':' . $sslPort;
        $ip_rewrite_cond_http .= 'RewriteCond %{HTTP_HOST}                !^' . $vhost->{ipaddr} . '(:' . $httpPort . ')?$';
        $ip_rewrite_cond_https .= 'RewriteCond %{HTTP_HOST}                !^' . $vhost->{ipaddr} . '(:' . $sslPort . ')?$';
    }

my $apache_forwarded_for = '';
if ($Nginx->{enabled} eq '1') {
    $apache_forwarded_for .= "RemoteIPHeader X-Forwarded-For\n";
    if ($vhost->{ipaddr} ne "") {
        $apache_forwarded_for .= "RemoteIPInternalProxy $vhost->{ipaddr}\n";
        $apache_forwarded_for .= "RemoteIPTrustedProxy $vhost->{ipaddr}\n";
    }
    if ($vhost->{ipaddrIPv6} ne "") {
        $apache_forwarded_for .= "RemoteIPInternalProxy $vhost->{ipaddrIPv6}\n";
        $apache_forwarded_for .= "RemoteIPTrustedProxy $vhost->{ipaddrIPv6}\n";
    }
}

    my $vhost_conf =<<END;
# owned by VirtualHost
#NameVirtualHost $http_ipline
#
#### PLEASE NOTE:
#
# Do not edit this file. Anything in this file will be overwritten by the GUI.
# Put your custom additions into $include_file instead!
#
# ServerRoot needs to be set. Otherwise all the vhosts 
# need to go in httpd.conf, which could get very large 
# since there could be thousands of vhosts:
ServerRoot $Base::Httpd::server_root

<VirtualHost $http_ipline>
ServerName $vhost->{fqdn}
$ServerAlias
ServerAdmin $vhost->{serverAdmin}
DocumentRoot $vhost->{documentRoot}
ErrorDocument 401 /error/401-authorization.html
ErrorDocument 403 /error/403-forbidden.html
ErrorDocument 404 /error/404-file-not-found.html
ErrorDocument 500 /error/500-internal-server-error.html
RewriteEngine on
$ip_rewrite_cond_http
RewriteCond %{HTTP_HOST}                !^$vhost->{fqdn}(:$httpPort)?\$ [NC]
$aliasRewrite
RewriteRule ^/(.*)                      http://$vhost->{fqdn}/\$1 [L,R=301]
$redirect_line
$conf_userwebs
AliasMatch ^/~([^/]+)(/(.*))?           $user_root
$PHP_Config_Lines
$WebScripting_Config_Lines
$Java_Config_Lines
$apache_forwarded_for
Include $include_file
</VirtualHost>
END

    # write SSL config
    my $cafile;
    &debug_msg("SSL <VirtualHost>: \$vhost_ssl->{enabled}: $vhost_ssl->{enabled} - \$vhost_ssl->{expires}: $vhost_ssl->{expires}\n");
    if (($vhost_ssl->{enabled} eq "1") && (-f "$vhost->{basedir}/certs/certificate") && (-f "$vhost->{basedir}/certs/key") && ($Nginx->{enabled} ne '1')) {
        &debug_msg("SSL <VirtualHost>: Condition #1: TRUE\n");
        if (-f "$vhost->{basedir}/certs/ca-certs") {
            $cafile = "SSLCACertificateFile $vhost->{basedir}/certs/ca-certs";
            &debug_msg("SSL <VirtualHost>: Condition #2: TRUE\n");
        }

        $vhost_conf .=<<END;

#NameVirtualHost $https_ipline
<VirtualHost $https_ipline>
SSLengine on
SSLCompression off
SSLProtocol TLSv1.2 +TLSv1.1
SSLHonorCipherOrder On
SSLCipherSuite AES256+EECDH:AES256+EDH:AES128+EECDH:AES128+EDH:!aNULL:!eNULL:!NULL:!EXPORT:!IDEA:!3DES:!DES:!MD5:!PSK:!RC4:@STRENGTH
$HSTS_line
$cafile
SSLCertificateFile $vhost->{basedir}/certs/certificate
SSLCertificateKeyFile $vhost->{basedir}/certs/key
ServerName $vhost->{fqdn}
$ServerAlias
ServerAdmin $vhost->{serverAdmin}
DocumentRoot $vhost->{documentRoot}
ErrorDocument 401 /error/401-authorization.html
ErrorDocument 403 /error/403-forbidden.html
ErrorDocument 404 /error/404-file-not-found.html
ErrorDocument 500 /error/500-internal-server-error.html
RewriteEngine on
$ip_rewrite_cond_https
RewriteCond %{HTTP_HOST}                !^$vhost->{fqdn}(:$sslPort)?\$ [NC]
$aliasRewriteSSL
RewriteRule ^/(.*)                      https://$vhost->{fqdn}/\$1 [L,R=301]
$redirect_line
$conf_userwebs
AliasMatch ^/~([^/]+)(/(.*))?           $user_root
$PHP_Config_Lines
$WebScripting_Config_Lines
$Java_Config_Lines
Include $include_file
</VirtualHost>
END
    }

    #
    # Updated: 2017-09-19 by mstauber as per '[BlueOnyx:21390] Re: Apache Webserver SSLCipherSuite - request':
    #
    # Explanation on the SSL Ciphers SSL Protocol and SSL CipherSuites: The fuckers at RedHat had 
    # crippled OpenSSL so that elliptic curve ciphers were missing. Go figure. Honest broker? My ass!
    #
    # They added some of them back in RHEL6.5. See: https://bugzilla.redhat.com/show_bug.cgi?id=319901
    # As far as Apache is concerned, the ECDHE ciphers *still* did not work for a long time. This finally
    # changed and we now do have access to ECDH secp256r1 both on CentOS 6 and CentOS 7. Woot! In fact:
    # we do get identical results for protocols and ciphers on CentOS 6 and CentOS 7 with these settings.
    # 
    # For securing HTTPS we want to achieve the following:
    # - Do not use weak or comprimised ciphers: OK (as best as possible)
    # - Use Strict Transport Security (HSTS): OK, but make it optional, as it can be a pain in the ass.
    # - Support Forward Secrecy (PFS) for as many browsers as possible: OK, but fail for Internet Explorer.
    # - Use ECDH secp256r1 wherever possible, even though we are fully aware about the (at this point rather
    #   philosophical discussion) about wether or not the NSA had a say in the selection of the curve.
    #
    # If ECDHE is not available for a browser, then we fall back to DH 4096 bit or at the worst to DH 4096,
    # which allow Forward Secrecy. It's the next best thing below ECDHE.
    #
    # Most browsers we then get TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA384 (ECDH secp256r1) or at least 
    # TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA (ECDH secp256r1) with TLS_DHE_RSA_WITH_AES_128_CBC_SHA (DH 4096)
    # representing the bottom end with Android 2.3.7 or OpenSSL 0.9.8y.
    #
    # Protocols: Only IE6/XP would use SSLv3, which we disabled due to the 'Pootle'-vulnerability. So IE6/XP
    # and IE8/XP users will no longer be able to connect. All the rest default to TLSv1.2 or TLSv1.1.
    #
    # As of 2018-02-28 we only support TLSv1.2 and TLSv1.1. This removes TLSv1.0 as well, as it will soon  
    # fail PCI compliance tests. 
    #
    # Ciphers: RC4 and other weak ciphers have been disabled.
    #
    # As we now support SNI, some browsers or Robots are left out as far as HTTPS is concerned. 
    #
    # Which is not really our problem. We're not sacrificing our security for fuckers that haven't
    # heard the shot yet and who can't be assed to use a more recent OS.
    #
    # Updated: 2018-03-14: 
    #
    # With the deprecation of the weak DH curves and living within the limitations of the OpenSSL
    # that CentOS provides us with, we switched to: AES256+EECDH:AES256+EDH:AES128+EECDH:AES128+EDH
    # entirely.
    #

    # append line marking the end of the section specifically owned by the VirtualHost
    my $end_mark = "# end of VirtualHost owned section\n";
    $vhost_conf .= $end_mark;

    my $conf_printed = 0;

    while (<$in>)
    {
        if (!$conf_printed && /^$end_mark$/)
        {
            print $out $vhost_conf;
            &debug_msg("Done1: Editing Vhost container for $vhost->{fqdn} and using Port $httpPort\n");
            $conf_printed = 1;
        }
        elsif ($conf_printed)
        {
            # print out information entered by other objects
            print $out $_;
            &debug_msg("Done2: Editing Vhost container for $vhost->{fqdn} and using Port $httpPort\n");
        }
    }

    if (!$conf_printed)
    {
        print $out $vhost_conf;
    }

    #
    ### Handle Nginx SSL-Proxy Vhost config files:
    #

    $nginx_vhosts_file = '/etc/nginx/vsites/' . $vsite->{"name"};

    $server_name = $vhost->{fqdn};
    if ($nginx_ServerAlias ne '') {
        $server_name .= ' ' . $nginx_ServerAlias;
        chomp($server_name);
    }

    my $cafile;
    &debug_msg("SSL <Nginx-Vhost>: \$vhost_ssl->{enabled}: $vhost_ssl->{enabled} - \$vhost_ssl->{expires}: $vhost_ssl->{expires}\n");
    if (($vhost_ssl->{enabled} eq "1") && (-f "$vhost->{basedir}/certs/certificate") && (-f "$vhost->{basedir}/certs/key")) {
        &debug_msg("SSL <Nginx-Vhost>: Condition #1: TRUE\n");

        $combined_cert = $vhost->{basedir} . '/certs/nginx_cert_ca_combined';
        $the_ca_cert = $vhost->{basedir} . '/certs/ca-certs';
        $the_cert = $vhost->{basedir} . '/certs/certificate';
        $the_blank = $vhost->{basedir} . '/certs/blank.txt';

        if (! -f $the_blank) {
            system("echo \"\" > $the_blank");
            system("chmod 640 $the_blank");
        }

        if ((-f $the_ca_cert) && (-f $the_cert)) {
            system("cat $the_cert $the_blank $the_ca_cert > $combined_cert");
            system("chmod 640 $combined_cert");
        }
        elsif ((! -f $the_ca_cert) && (-f $the_cert)) {
            # We have no intermediate.
            system("cat $the_cert > $combined_cert");
            system("chmod 640 $combined_cert");
        }
        if (! -f $combined_cert) {
            # If we still have noting, we go bare:
            system("touch $combined_cert");
            system("chmod 640 $combined_cert");
        }

        #
        ### Start: Nginx Vsite HSTS:
        #

        $includeSubDomains = '';
        if ($NginxVsite->{include_subdomains} eq "1") {
            $includeSubDomains = ' includeSubDomains';
        }
        
        if ($NginxVsite->{HSTS} eq "1") {
            $Nginx_HSTS = 'add_header Strict-Transport-Security "max-age=' . $NginxVsite->{max_age} . ';' . $includeSubDomains . '" always;' . "\n";
            $Nginx_HSTS .= '      include /etc/nginx/headers.d/security.conf;' . "\n";
        }
        else {
            $Nginx_HSTS = '';
        }

        #
        ### End: Nginx Vsite HSTS:
        #

        $nginx_vhost_conf .=<<END;
# Do NOT edit this file. The GUI will replace this file on edits.
server {

    listen              [::]:443 ssl http2;
    listen              443 ssl http2;
    server_name         $server_name;

    include /etc/nginx/headers.d/*.conf;

    ssl_certificate         $combined_cert;
    ssl_certificate_key     $vhost->{basedir}/certs/key;
    ssl_trusted_certificate $combined_cert;

    # Insert external protocols and chiffres for SSL:
    include /etc/nginx/ssl_proto_chiffres.conf;

    # Insert external SSL Session cfg, resolver and OSCP-Stapling:
    include /etc/nginx/ssl_defaults.conf;

    error_page 502 /502-bad-gateway.html;
    location = /502-bad-gateway.html {
        internal;
        root  /var/www/html/error/;
    }

    # Special provisions for /libImage/ for error page gfx:
    location ~ ^/libImage/*.*\$ {
        root   /usr/sausalito/ui/web/;
    }

    location / {
      $Nginx_HSTS
      proxy_http_version   1.1;
      proxy_set_header     Connection "";
      proxy_set_header     Host \$host;
      proxy_set_header     X-Real-IP \$remote_addr;
      proxy_set_header     X-Forwarded-For \$proxy_add_x_forwarded_for;
      proxy_set_header     X-Forwarded-Proto \$scheme;
      proxy_pass           http://$vhost->{fqdn}:80/;
      proxy_read_timeout   90;

      client_max_body_size $PHP_Vsite->{"upload_max_filesize"};

    }
}
END

        # Edit the Nginx-Vhost file:
        if(!Sauce::Util::editfile($nginx_vhosts_file, *nginx_printer)) {
            &debug_msg("Failed to edit $nginx_vhosts_file through virtual_host.pl \n");
            $cce->bye('FAIL', '[[base-nginx.cantEditCfg]]');
            exit(1);
        }
        else {
            &debug_msg("Editing $nginx_vhosts_file through virtual_host.pl - DONE \n");
            system("/usr/bin/chmod 644 $nginx_vhosts_file");
        }
    }

    return 1;
}

#
### Subroutines:
#

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

sub pool_printer {
    ($in, $out) = @_;
    print $out $pool_conf;
    return 1;
}

sub in_array {
     my ($arr,$search_for) = @_;
     my %items = map {$_ => 1} @$arr; # create a hash out of the array values
     return (exists($items{$search_for}))?1:0;
 }

sub thirdparty_check {
    # Check for presence of third party config file:
    $php_ini = $known_php_inis{$seen_php_versions{$platform}};
    &debug_msg("Using php.ini $php_ini " . " for platform " . $seen_php_versions{$platform} . "\n");
}

sub open_basedir_handling {

    # Get 'open_basedir' settings for this Vsite:
    @vsite_php_settings_temporary = split(":", $PHP_Vsite->{"open_basedir"});

    # PEAR speciality:
    &debug_msg("PEAR path should be: " . $phpPEARpath{$vsite_php->{"version"}} . " for PHP version " . $vsite_php->{"version"} . " \n");

    # Remove all known PEAR path additions (to add the right one later):
    for $thisPEAR (keys %phpPEARpath) {
        if (in_array(\@vsite_php_settings_temporary, $phpPEARpath{$thisPEAR})) {
            &debug_msg("PEAR: Removing $phpPEARpath{$thisPEAR} \n");
            @vsite_php_settings_temporary = grep { $_ != $phpPEARpath{$thisPEAR} } @vsite_php_settings_temporary;
        }
    }

    # Get 'open_basedir' settings for the entire server:
    @my_server_php_settings_temp = split(":", $PHP_server->{'open_basedir'});
    # Merge them:
    @vsite_php_settings_temp_joined = (@vsite_php_settings_temporary, @my_server_php_settings_temp);

    # For debugging:
    &debug_msg("Server settings for 'open_basedir': " . $PHP_server->{'open_basedir'} . "\n");
    &debug_msg("User additions for 'open_basedir' : " . $PHP_Vsite->{'open_basedir'} . "\n");

    # Remove duplicates from merged array:
    %obd_helper = map { $_ => 1 } @vsite_php_settings_temp_joined;
    @vsite_php_settings_temp = keys %obd_helper;

    # We need to remove any site path references from open_basedir, because they could be from the wrong site,
    # like during a cmuImport, when it inherited the path it had on the server it was exported from.
    foreach $entry (@vsite_php_settings_temp) {
        $entry =~ s/\/home\/.sites\/(.*)\/(.*)\///;
        if ($entry) {
            push(@vsite_php_settings_new, $entry);
            &debug_msg("Pushing $entry \n");
        }
        else {
            &debug_msg("Not pushing $entry \n");
        }
    }

    # Check if the given PEAR directory is actually there:
    if (-d $phpPEARpath{$vsite_php->{"version"}}) {
        $pear_path = $phpPEARpath{$vsite_php->{"version"}};
        &debug_msg("PEAR additions for 'open_basedir' : $pear_path \n");

        # Check if we have that already included in 'open_basedir' (we shouldn't):
        if (!in_array(\@vsite_php_settings_new, $pear_path)) {
            # Not in yet? Add it:
            push @vsite_php_settings_new, $pear_path;
        }
    }

    # Assemble the output:
    $PHP_Vsite->{"open_basedir"} = join(":", @vsite_php_settings_new);

    # At this point we add the Vsite's basedir:
    $PHP_Vsite->{"open_basedir"} = $PHP_Vsite->{"open_basedir"} . ':' . $vsite->{"basedir"} . '/'; 

    # Make sure that the path to the prepend file directory is allowed, too:
    unless ($PHP_Vsite->{"open_basedir"} =~ m/\/usr\/sausalito\/configs\/php\//) {
        &debug_msg("Adding prepend file directory to open_basedir through php_vsite_handler.pl \n");
        $PHP_Vsite->{"open_basedir"} = $PHP_Vsite->{"open_basedir"} . ':/usr/sausalito/configs/php/';
    }

    # More debugging output:
    &debug_msg("Open Basedir is now set to: $PHP_Vsite->{'open_basedir'} \n");
}

sub edit_php_ini {

    if ($PHP_Vsite->{'version'} ne "PHP53") {
        &debug_msg("PHP version is $PHP_Vsite->{'version'} - turning 'register_globals' off.\n");
        $PHP_Vsite->{"register_globals"} = "Off";
    }
    else {
        &debug_msg("Keeping 'register_globals' as it is as we're using: " . $PHP_Vsite->{'version'} . "\n");
    }

    if ($legacy_php == "0") {
        # Build output hash for PHP-5.3 or newer:
        $vsite_php_settings_writeoff = { 
                'register_globals' => $PHP_Vsite->{"register_globals"}, 
                'allow_url_fopen' => $PHP_Vsite->{"allow_url_fopen"}, 
                'allow_url_include' => $PHP_Vsite->{"allow_url_include"}, 
                'disable_classes' => $PHP_Vsite->{"disable_classes"}, 
                'disable_functions' => $PHP_Vsite->{"disable_functions"}, 
                'open_basedir' => $PHP_Vsite->{"open_basedir"}, 
                'post_max_size' => $PHP_Vsite->{"post_max_size"}, 
                'upload_max_filesize' => $PHP_Vsite->{"upload_max_filesize"},
                'max_execution_time' => $PHP_Vsite->{"max_execution_time"}, 
                'max_input_time' => $PHP_Vsite->{"max_input_time"}, 
                'max_input_vars' => $PHP_Vsite->{"max_input_vars"}, 
                'memory_limit' => $PHP_Vsite->{"memory_limit"},
                'mail.add_x_header' => 'On',
                'sendmail_path' => '/usr/sausalito/sbin/phpsendmail',
                'auto_prepend_file' => '/usr/sausalito/configs/php/set_php_headers.php',
                'date.timezone' => "'" . $timezone . "'",
                'mysql.default_socket' => '/var/lib/mysql/mysql.sock',
                'mysqli.default_socket' => '/var/lib/mysql/mysql.sock',
                'pdo_mysql.default_socket' => '/var/lib/mysql/mysql.sock'
        };
    }
    else {
        # Build output hash for and older PHP:
        $vsite_php_settings_writeoff = { 
                'safe_mode' => $PHP_Vsite->{"safe_mode"}, 
                'safe_mode_allowed_env_vars' => $PHP_Vsite->{"safe_mode_allowed_env_vars"}, 
                'safe_mode_exec_dir' => $PHP_Vsite->{"safe_mode_exec_dir"}, 
                'safe_mode_gid' => $PHP_Vsite->{"safe_mode_gid"}, 
                'safe_mode_include_dir' => $PHP_Vsite->{"safe_mode_include_dir"}, 
                'safe_mode_protected_env_vars' => $PHP_Vsite->{"safe_mode_protected_env_vars"},
                'register_globals' => $PHP_Vsite->{"register_globals"}, 
                'allow_url_fopen' => $PHP_Vsite->{"allow_url_fopen"}, 
                'allow_url_include' => $PHP_Vsite->{"allow_url_include"}, 
                'post_max_size' => $PHP_Vsite->{"post_max_size"}, 
                'upload_max_filesize' => $PHP_Vsite->{"upload_max_filesize"},
                'max_execution_time' => $PHP_Vsite->{"max_execution_time"}, 
                'max_input_time' => $PHP_Vsite->{"max_input_time"}, 
                'max_input_vars' => $PHP_Vsite->{"max_input_vars"}, 
                'memory_limit' => $PHP_Vsite->{"memory_limit"},
                'mail.add_x_header' => 'On',
                'sendmail_path' => '/usr/sausalito/sbin/phpsendmail',
                'auto_prepend_file' => '/usr/sausalito/configs/php/set_php_headers.php',
                'date.timezone' => "'" . $timezone . "'",
                'mysql.default_socket' => '/var/lib/mysql/mysql.sock',
                'mysqli.default_socket' => '/var/lib/mysql/mysql.sock',
                'pdo_mysql.default_socket' => '/var/lib/mysql/mysql.sock'
        };
    }

    # Write changes to php.ini using Sauce::Util::hash_edit_function. The really GREAT thing
    # about this function is that it replaces existing values and appends those new ones that 
    # are missing in the output file. And it does it for ALL values in our hash in one go.

    $ok = Sauce::Util::editfile(
        $custom_php_ini_path,
        *Sauce::Util::hash_edit_function,
        ';',
        { 're' => '=', 'val' => ' = ' },
        $vsite_php_settings_writeoff);

    # Error handling:
    unless ($ok) {
        &debug_msg("Error while editing $custom_php_ini_path through php_vsite_handler.pl \n");
        $cce->bye('FAIL', "Error while editing $custom_php_ini_path!");
        exit(1);
    }

    # Write off vsite/php.d related extras into Vsite's php.ini - if there are any:
    if ($hard_way > 0) {
        &debug_msg("Editing $custom_php_ini_path for php.d related extras through php_vsite_handler.pl \n");
        $ok = Sauce::Util::editfile(
            $custom_php_ini_path,
            *Sauce::Util::hash_edit_function,
            ';',
            { 're' => '=', 'val' => ' = ' },
            $vsite_php_settings_writeoff_extra);

        # Error handling:
        unless ($ok) {
            &debug_msg("Error while editing $custom_php_ini_path for php.d related extras through php_vsite_handler.pl \n");
            $cce->bye('FAIL', "Error while editing $custom_php_ini_path to set php.d related extras!");
            exit(1);
        }
    }
}

sub handle_fpm_pools {
 
    # Get new prefered owner:
    $pool_UID = $vsite_php->{'prefered_siteAdmin'};

    # Fallback:
    if ($pool_UID eq "") {
        $pool_UID = 'apache';
    }

    # Get sites basedir:
    $vsite_basedir = $vsite->{"basedir"};

    # Get group just to be sure:
    $pool_group = $vsite->{"name"};

    # Pool directory:
    &debug_msg("Using " . $vsite_php->{'version'} . " pool directory: $known_php_fpm_pool_directories{$vsite_php->{'version'}} \n");
    $pool_directory = $known_php_fpm_pool_directories{$vsite_php->{'version'}};

    # Pool Port:
    # Assign a port number based on the GID of the Vsite.
    # Our GID's for siteX start at 1001, so we need to add
    # 8000 to it to get above 9000.
    ($_name, $_passwd, $gid, $_members) = getgrnam($pool_group);
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

    # Logic check for fpm_max_children: 
    if (($PHP_server->{'fpm_max_children'} < "1") || ($PHP_server->{'fpm_max_children'} > "255")) {
        $fpm_max_children = "15";
    }
    else {
        $fpm_max_children = $PHP_server->{'fpm_max_children'};
    }

    # Location of the PHP-FPM pool file:
    $pool_file_wildcard = '/etc/php-fpm*.d/';
    if (-d $pool_directory) {
        $pool_file = $pool_directory . "$pool_group.conf";
    }
    else {
        $pool_file = "/etc/php-fpm.d/$pool_group.conf";
    }

    if ($vsite_php->{'fpm_enabled'} == "0") {
        &debug_msg("Deleting PHP-FPM pool config $pool_file_wildcard$pool_group.conf through php_vsite_handler.pl \n");
        # Delete Pool file from all pools:
        system("/bin/rm -f $pool_file_wildcard$pool_group.conf");
    }
    else {

        &debug_msg("Removing $pool_file_wildcard$pool_group.conf \n");
        system("/bin/rm -f $pool_file_wildcard$pool_group.conf");

        &debug_msg("Creating PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");

        #
        ### Define Pool config file contends:
        #

        $pool_conf = '; Global PHP-FPM settings for this Vsite:' . "\n";
        $pool_conf .= '[global]' . "\n";
        $pool_conf .= 'log_level = notice' . "\n";
        $pool_conf .= 'emergency_restart_threshold = 0' . "\n";
        $pool_conf .= 'emergency_restart_interval = 0' . "\n";
        $pool_conf .= 'process_control_timeout = 0' . "\n";
        $pool_conf .= 'daemonize = yes' . "\n";
        $pool_conf .= '' . "\n";
        $pool_conf .= '; Pool name and settings:' . "\n";
        $pool_conf .= '[' . $pool_group . ']' . "\n";
        $pool_conf .= 'listen = 127.0.0.1:' . $fpmPort . "\n";
        $pool_conf .= 'listen.allowed_clients = 127.0.0.1' . "\n";
        $pool_conf .= 'listen.owner = ' . $pool_UID . "\n";
        $pool_conf .= 'listen.group = ' . $pool_group . "\n";
        $pool_conf .= 'listen.mode = 0666' . "\n";
        $pool_conf .= '' . "\n";
        $pool_conf .= '; UID/GID:' . "\n";
        $pool_conf .= 'user = ' . $pool_UID . "\n";
        $pool_conf .= 'group = ' . $pool_group . "\n";
        $pool_conf .= '' . "\n";
        $pool_conf .= '; Set to \'ondemand\' and set limits:' . "\n";
        $pool_conf .= 'pm = ondemand' . "\n";
        $pool_conf .= 'pm.max_children = ' . $fpm_max_children . "\n";
        $pool_conf .= 'pm.process_idle_timeout = 10s' . "\n";
        $pool_conf .= 'pm.max_requests = 500' . "\n";
        $pool_conf .= '' . "\n";
        $pool_conf .= '; Set session path to a directory owned by process user' . "\n";
        $pool_conf .= 'php_value[session.save_handler] = files' . "\n";
        $pool_conf .= 'php_value[session.save_path] = /var/lib/php/session' . "\n";
        $pool_conf .= '' . "\n";
        $pool_conf .= '; PHP Vsite settings' . "\n";

        # Piece the individual PHP Vsite settings together:

        if ($legacy_php == "1") {
            # These options only apply to PHP versions prior to PHP-5.3:
            if ($PHP_Vsite->{"safe_mode"} ne "") {
                $pool_conf .= 'php_admin_flag[safe_mode] = ' . $PHP_Vsite->{"safe_mode"} . "\n"; 
            }
            if ($PHP_Vsite->{"safe_mode_gid"} ne "") {
                $pool_conf .= 'php_admin_flag[safe_mode_gid] =' . $PHP_Vsite->{"safe_mode_gid"} . "\n";
            }
            if ($PHP_Vsite->{"safe_mode_allowed_env_vars"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_allowed_env_vars] = ' . $PHP_Vsite->{"safe_mode_allowed_env_vars"} . "\n"; 
            }
            if ($PHP_Vsite->{"safe_mode_exec_dir"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_exec_dir] = ' . $PHP_Vsite->{"safe_mode_exec_dir"} . "\n"; 
            }

            if ($PHP_Vsite->{"safe_mode_include_dir"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_include_dir] = ' . $PHP_Vsite->{"safe_mode_include_dir"} . "\n"; 
            }
            if ($PHP_Vsite->{"safe_mode_protected_env_vars"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_protected_env_vars] = ' . $PHP_Vsite->{"safe_mode_protected_env_vars"} . "\n"; 
            }
        }

        if ($vsite_php->{'version'} ne "PHP53") {
            &debug_msg("PHP version is $vsite_php->{'version'} - turning 'register_globals' off.\n");
            $PHP_Vsite->{"register_globals"} = "Off";
        }
        else {
            &debug_msg("Keeping 'register_globals' as it is as we're using: " . $vsite_php->{'version'} . "\n");
        }

        if ($PHP_Vsite->{"register_globals"} ne "") {
            $pool_conf .= 'php_admin_flag[register_globals] = ' . $PHP_Vsite->{"register_globals"} . "\n"; 
        }
        if ($PHP_Vsite->{"allow_url_fopen"} ne "") {
            $pool_conf .= 'php_admin_flag[allow_url_fopen] = ' . $PHP_Vsite->{"allow_url_fopen"} . "\n"; 
        }
        if ($PHP_Vsite->{"allow_url_include"} ne "") {
            $pool_conf .= 'php_admin_flag[allow_url_include] = ' . $PHP_Vsite->{"allow_url_include"} . "\n"; 
        }

        if ($PHP_Vsite->{"open_basedir"} ne "") {
            $pool_conf .= 'php_admin_value[open_basedir] = ' . $PHP_Vsite->{"open_basedir"} . "\n";
        }

        if ($PHP_Vsite->{"post_max_size"} ne "") {
            $pool_conf .= 'php_admin_value[post_max_size] = ' . $PHP_Vsite->{"post_max_size"} . "\n"; 
        }
        if ($PHP_Vsite->{"upload_max_filesize"} ne "") {
            $pool_conf .= 'php_admin_value[upload_max_filesize] = ' . $PHP_Vsite->{"upload_max_filesize"} . "\n"; 
        }
        if ($PHP_Vsite->{"max_execution_time"} ne "") {
            $pool_conf .= 'php_admin_value[max_execution_time] = ' . $PHP_Vsite->{"max_execution_time"} . "\n"; 
        }
        if ($PHP_Vsite->{"max_input_time"} ne "") {
            $pool_conf .= 'php_admin_value[max_input_time] = ' . $PHP_Vsite->{"max_input_time"} . "\n"; 
        }
        if ($PHP_Vsite->{"max_input_vars"} ne "") {
            $pool_conf .= 'php_admin_value[max_input_vars] = ' . $PHP_Vsite->{"max_input_vars"} . "\n"; 
        }
        if ($PHP_Vsite->{"memory_limit"} ne "") {
            $pool_conf .= 'php_admin_value[memory_limit] = ' . $PHP_Vsite->{"memory_limit"} . "\n"; 
            &debug_msg("Setting 'memory_limit' to $PHP_Vsite->{'memory_limit'} \n");
        }

        # Email related:
        $pool_conf .= 'php_admin_flag[mail.add_x_header] = On' . "\n";
        $pool_conf .= 'php_admin_value[sendmail_path] = /usr/sausalito/sbin/phpsendmail' . "\n";
        $pool_conf .= 'php_admin_value[auto_prepend_file] = /usr/sausalito/configs/php/set_php_headers.php' . "\n";

        # Add vsite/php.d/* include related extras:
        $pool_conf .= "; From $custom_php_include_dir/:\n";
        $pool_conf .= $php_fpm_extra_cfg;

        # Edit the Pool config file or die!:
        &debug_msg("Editing PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");

        if(!Sauce::Util::editfile($pool_file, *pool_printer)) {
            &debug_msg("Failed to edit PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");
            $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
            exit(1);
        }

        system("/usr/bin/chmod 644 $pool_file");

        # Restart PHP-FPM:
        $VsitePhpVer = $vsite_php->{'version'};
        &debug_msg("Telling Sauce::Service to turn $known_php_services{$VsitePhpVer} on and to restart it.\n");

        # Reload all PHP-FPM-Services to drop bindings to ports that are no longer active.
        # Note: This will not start a service that should be disabled due to no Vsite using it.
        for my $fpmKey (values %known_php_services) {
            &debug_msg("FPM-Reload: $fpmKey \n");
            service_run_init($fpmKey, 'condreload');
        }

        service_set_init($known_php_services{$VsitePhpVer}, 'on');
        service_run_init($known_php_services{$VsitePhpVer}, 'restart');
    }
}

sub nginx_printer {
    ($in, $out) = @_;
    print $out $nginx_vhost_conf;
    return 1;
}

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
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