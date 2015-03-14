#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_vsite_handler.pl

# This handler is run whenever a CODB Object called "Vsite" with namespace 
# "PHPVsite" is created, destroyed or modified. 
#
# If the "Vsite" Object of namespace "PHPVsite" Object with "applicable" => "siteXX" 
# is created or modified, it edits /etc/httpd/conf/vhosts/siteXX to write the 
# correct php_admin_flag's to that site's include file and Apache is restarted.
#
# If suPHP is enabled, it will also create a copy of php.ini in the basedir of the
# Vsite and will modify it with the PHP settings configured for the site in question.
# If suPHP is disabled, that custom php.ini file will be deleted. It is protected
# against modifications through chattrib, although that may be a bit excessive.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

$whatami = "handler";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir homedir_get_user_dir);
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$extra_PHP_basepath = '/home/solarspeed/';

# Location of php.ini - may get overridden by thirdparty_check():
$php_ini = "/etc/php.ini";


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
    $platform = $PHP->{'PHP_version'};
    if ($platform >= "5.3") {
        # More modern PHP found:
        $legacy_php = "0";
    }
    else {
        # Older PHP found:
        $legacy_php = "1";
    }

    # Get system Timezone out of CODB:
    @system_oid = $cce->find('System');
    ($ok, $tzdata) = $cce->get($system_oid[0], "Time");
    $timezone = $tzdata->{'timeZone'};

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

    # Known PHP-FPM pool ports:
    %known_pool_ports = (
                            'PHPOS' => '9000',
                            'PHP53' => '8953',
                            'PHP54' => '8954',
                            'PHP55' => '8955',
                            'PHP56' => '8956'
                            );

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

    # Add the OS related PHP into the mix as well:
    $known_php_inis{'PHPOS'} = $php_ini;
    $seen_php_versions{'PHPOS'} = $PHP->{'PHP_version_os'};
    $seen_php_versions{$PHP->{'PHP_version_os'}} = 'PHPOS';
    $known_php_fpm_pool_files{'PHPOS'} = '/etc/php-fpm.d/www.conf';
    $known_php_fpm_pool_directories{'PHPOS'} = '/etc/php-fpm.d/';
    $known_php_fpm_pool_services{'PHPOS'} = 'php-fpm';

    # Check for presence of third party config file:
    &thirdparty_check;

    # Get Object PHP from CODB to find out how php.ini is configured:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);

    # Poll info about the Vsite in question:
    ($ok, $vsite) = $cce->get($oid);

    # Get PHPVsite:
    ($ok, $vsite_php_settings) = $cce->get($oid, "PHPVsite");

    # Get PHP:
    ($ok, $vsite_php) = $cce->get($oid, "PHP");

    &debug_msg("Vsite is supposed to be using $vsite_php->{'version'} and " . $seen_php_versions{$vsite_php->{'version'}} . " \n");

    # Find out what 'open_basedir' needs to be set to:
    &open_basedir_handling;

    # Event is create or modify:
    if ((($cce->event_is_create()) || ($cce->event_is_modify()))) {

        # Edit the vhost container or die!:
        &debug_msg("Editing Vhost $vsite->{'name'} through php_vsite_handler.pl \n");
        if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{"name"}), *edit_vhost, $vsite_php_settings)) {
            &debug_msg("Failed to edit Vhost $vsite->{'name'}  through php_vsite_handler.pl \n");
            $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
            exit(1);
        }

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
            $custom_php_ini_path = $vsite->{'basedir'} . "/php.ini";
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
            &handle_fpm_pools
        }

        # prefered_siteAdmin toggles chowning of /web ownership:
        &change_owner;
        
        # Restart Apache:
        &restart_apache;
    }
}

$cce->bye('SUCCESS');
exit(0);

sub restart_apache {
    # Restarts Apache - soft restart:
    service_run_init('httpd', 'reload');
}

sub edit_vhost {
    my ($in, $out, $php, $cgi, $ssi) = @_;

    my $script_conf = '';

    my $begin = '# BEGIN PHP SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';
    my $end = '# END PHP SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';

    if ($vsite_php->{"enabled"} eq "1") {

    # Making sure 'safe_mode_include_dir' has the bare minimum defaults:
    @smi_temporary = split(":", $vsite_php_settings->{"safe_mode_include_dir"});
    @smi_baremetal_minimums = ('/usr/sausalito/configs/php/', '.');
    @smi_temp_joined = (@smi_temporary, @smi_baremetal_minimums);
    
    # Remove duplicates:
    foreach my $var ( @smi_temp_joined ){
        if ( ! grep( /$var/, @safe_mode_include_dir ) ){
            push(@safe_mode_include_dir, $var );
        }
    }
    $vsite_php_settings->{"safe_mode_include_dir"} = join(":", @safe_mode_include_dir);

    # Making sure 'safe_mode_allowed_env_vars' has the bare minimum defaults:
    @smaev_temporary = split(",", $vsite_php_settings->{"safe_mode_allowed_env_vars"});
    @smi_baremetal_minimums = ('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
    @smaev_temp_joined = (@smaev_temporary, @smi_baremetal_minimums);
    
    # Remove duplicates:
    foreach my $var ( @smaev_temp_joined ){
        if ( ! grep( /$var/, @safe_mode_allowed_env_vars ) ){
            push(@safe_mode_allowed_env_vars, $var );
        }
    }
    $vsite_php_settings->{"safe_mode_allowed_env_vars"} = join(",", @safe_mode_allowed_env_vars);

    if ($legacy_php == "1") {
        # These options only apply to PHP versions prior to PHP-5.3:
        if ($vsite_php_settings->{"safe_mode"} ne "") {
            $script_conf .= 'php_admin_flag safe_mode ' . $vsite_php_settings->{"safe_mode"} . "\n"; 
        }
        if ($vsite_php_settings->{"safe_mode_gid"} ne "") {
            $script_conf .= 'php_admin_flag safe_mode_gid ' . $vsite_php_settings->{"safe_mode_gid"} . "\n";
        }
        if ($vsite_php_settings->{"safe_mode_allowed_env_vars"} ne "") {
            $script_conf .= 'php_admin_value safe_mode_allowed_env_vars ' . $vsite_php_settings->{"safe_mode_allowed_env_vars"} . "\n"; 
        }
        if ($vsite_php_settings->{"safe_mode_exec_dir"} ne "") {
            $script_conf .= 'php_admin_value safe_mode_exec_dir ' . $vsite_php_settings->{"safe_mode_exec_dir"} . "\n"; 
        }

        if ($vsite_php_settings->{"safe_mode_include_dir"} ne "") {
            $script_conf .= 'php_admin_value safe_mode_include_dir ' . $vsite_php_settings->{"safe_mode_include_dir"} . "\n"; 
        }
        if ($vsite_php_settings->{"safe_mode_protected_env_vars"} ne "") {
            $script_conf .= 'php_admin_value safe_mode_protected_env_vars ' . $vsite_php_settings->{"safe_mode_protected_env_vars"} . "\n"; 
        }
    }

    if ($vsite_php_settings->{"register_globals"} ne "") {
        $script_conf .= 'php_admin_flag register_globals ' . $vsite_php_settings->{"register_globals"} . "\n"; 
    }
    if ($vsite_php_settings->{"allow_url_fopen"} ne "") {
        $script_conf .= 'php_admin_flag allow_url_fopen ' . $vsite_php_settings->{"allow_url_fopen"} . "\n"; 
    }
    if ($vsite_php_settings->{"allow_url_include"} ne "") {
        $script_conf .= 'php_admin_flag allow_url_include ' . $vsite_php_settings->{"allow_url_include"} . "\n"; 
    }

    if ($vsite_php_settings->{"open_basedir"} ne "") {
        $script_conf .= 'php_admin_value open_basedir ' . $vsite_php_settings->{"open_basedir"} . "\n";
    }

    if ($vsite_php_settings->{"post_max_size"} ne "") {
        $script_conf .= 'php_admin_value post_max_size ' . $vsite_php_settings->{"post_max_size"} . "\n"; 
    }
    if ($vsite_php_settings->{"upload_max_filesize"} ne "") {
        $script_conf .= 'php_admin_value upload_max_filesize ' . $vsite_php_settings->{"upload_max_filesize"} . "\n"; 
    }
    if ($vsite_php_settings->{"max_execution_time"} ne "") {
        $script_conf .= 'php_admin_value max_execution_time ' . $vsite_php_settings->{"max_execution_time"} . "\n"; 
    }
    if ($vsite_php_settings->{"max_input_time"} ne "") {
        $script_conf .= 'php_admin_value max_input_time ' . $vsite_php_settings->{"max_input_time"} . "\n"; 
    }
    if ($vsite_php_settings->{"memory_limit"} ne "") {
        $script_conf .= 'php_admin_value memory_limit ' . $vsite_php_settings->{"memory_limit"} . "\n"; 
    }

    # Email related:
    $script_conf .= 'php_admin_flag mail.add_x_header On' . "\n";
    $script_conf .= 'php_admin_value sendmail_path /usr/sausalito/sbin/phpsendmail' . "\n";
    $script_conf .= 'php_admin_value auto_prepend_file /usr/sausalito/configs/php/set_php_headers.php' . "\n";

    }

    my $last;
    my $enableSSL = 0;
    while(<$in>) {
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

sub change_owner {
    # Get new prefered owner:
    $new_owner = $vsite_php->{'prefered_siteAdmin'};

    # Get sites basedir:
    $vsite_basedir = $vsite->{"basedir"};

    # Get /web directory of the site in question:
    $webdir = $vsite->{"basedir"} . "/web";

    # Get GID just to be sure:
    $new_GID = $vsite->{"name"};

    if (($new_owner ne "") && ($webdir ne "") && ($vsite_basedir ne "")) {
        # Chown this sites /web to the prefered UID and also re-set the GID while we're at it:
        system("/bin/chown -R $new_owner:$new_GID $webdir");
        # Also chown the basedir of the site to this users UID, but don't do it recursively:
        system("/bin/chown $new_owner:$new_GID $vsite_basedir");
        # If we have subdomains under /vhosts, we need to chown them as well:
        if (-d "$vsite_basedir/vhosts") {
            system("/bin/chown -R $new_owner:$new_GID $vsite_basedir/vhosts");
        }
    }
}

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

sub thirdparty_check {
    # Check for presence of third party config file:
    $php_ini = $known_php_inis{$seen_php_versions{$platform}};
    &debug_msg("Using php.ini $php_ini " . " for platform " . $seen_php_versions{$platform} . "\n");

}

sub edit_php_ini {

    if ($vsite_php->{'version'} ne "PHP53") {
        &debug_msg("PHP version is $vsite_php->{'version'} - turning 'register_globals' off.\n");
        $vsite_php_settings->{"register_globals"} = "Off";
    }
    else {
        &debug_msg("Keeping 'register_globals' as it is as we're using: " . $vsite_php->{'version'} . "\n");
    }

    if ($legacy_php == "0") {
        # Build output hash for PHP-5.3 or newer:
        $vsite_php_settings_writeoff = { 
                'register_globals' => $vsite_php_settings->{"register_globals"}, 
                'allow_url_fopen' => $vsite_php_settings->{"allow_url_fopen"}, 
                'allow_url_include' => $vsite_php_settings->{"allow_url_include"}, 
                'disable_classes' => $vsite_php_settings->{"disable_classes"}, 
                'disable_functions' => $vsite_php_settings->{"disable_functions"}, 
                'open_basedir' => $vsite_php_settings->{"open_basedir"}, 
                'post_max_size' => $vsite_php_settings->{"post_max_size"}, 
                'upload_max_filesize' => $vsite_php_settings->{"upload_max_filesize"},
                'max_execution_time' => $vsite_php_settings->{"max_execution_time"}, 
                'max_input_time' => $vsite_php_settings->{"max_input_time"}, 
                'memory_limit' => $vsite_php_settings->{"memory_limit"},
                'mail.add_x_header' => 'On',
                'sendmail_path' => '/usr/sausalito/sbin/phpsendmail',
                'auto_prepend_file' => '/usr/sausalito/configs/php/set_php_headers.php',
                'date.timezone' => "'" . $timezone . "'"

        };
    }
    else {
        # Build output hash for and older PHP:
        $vsite_php_settings_writeoff = { 
                'safe_mode' => $vsite_php_settings->{"safe_mode"}, 
                'safe_mode_allowed_env_vars' => $vsite_php_settings->{"safe_mode_allowed_env_vars"}, 
                'safe_mode_exec_dir' => $vsite_php_settings->{"safe_mode_exec_dir"}, 
                'safe_mode_gid' => $vsite_php_settings->{"safe_mode_gid"}, 
                'safe_mode_include_dir' => $vsite_php_settings->{"safe_mode_include_dir"}, 
                'safe_mode_protected_env_vars' => $vsite_php_settings->{"safe_mode_protected_env_vars"},
                'register_globals' => $vsite_php_settings->{"register_globals"}, 
                'allow_url_fopen' => $vsite_php_settings->{"allow_url_fopen"}, 
                'allow_url_include' => $vsite_php_settings->{"allow_url_include"}, 
                'open_basedir' => $out_open_basedir, 
                'post_max_size' => $vsite_php_settings->{"post_max_size"}, 
                'upload_max_filesize' => $vsite_php_settings->{"upload_max_filesize"},
                'max_execution_time' => $vsite_php_settings->{"max_execution_time"}, 
                'max_input_time' => $vsite_php_settings->{"max_input_time"}, 
                'memory_limit' => $vsite_php_settings->{"memory_limit"},
                'mail.add_x_header' => 'On',
                'sendmail_path' => '/usr/sausalito/sbin/phpsendmail',
                'auto_prepend_file' => '/usr/sausalito/configs/php/set_php_headers.php',
                'date.timezone' => "'" . $timezone . "'" 
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
}

sub open_basedir_handling {

    # Get 'open_basedir' settings for this Vsite:
    @vsite_php_settings_temporary = split(":", $vsite_php_settings->{"open_basedir"});
    # Get 'open_basedir' settings for the entire server:
    @my_server_php_settings_temp = split(":", $PHP->{'open_basedir'});
    # Merge them:
    @vsite_php_settings_temp_joined = (@vsite_php_settings_temporary, @my_server_php_settings_temp);

    # For debugging:
    &debug_msg("Server settings for 'open_basedir': $PHP->{'open_basedir'} \n");
    &debug_msg("User additions for 'open_basedir' : $vsite_php_settings->{'open_basedir'} \n");

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

    # Assemble the output:
    $vsite_php_settings->{"open_basedir"} = join(":", @vsite_php_settings_new);

    # At this point we add the Vsite's basedir:
    $vsite_php_settings->{"open_basedir"} = $vsite_php_settings->{"open_basedir"} . ':' . $vsite->{"basedir"} . '/'; 

    # Make sure that the path to the prepend file directory is allowed, too:
    unless ($vsite_php_settings->{"open_basedir"} =~ m/\/usr\/sausalito\/configs\/php\//) {
        &debug_msg("Adding prepend file directory to open_basedir through php_vsite_handler.pl \n");
        $vsite_php_settings->{"open_basedir"} = $vsite_php_settings->{"open_basedir"} . ':/usr/sausalito/configs/php/';
    }

    # More debugging output:
    &debug_msg("Open Basedir is now set to: $vsite_php_settings->{'open_basedir'} \n");
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
        $pool_conf .= 'pm.max_children = 5' . "\n";
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
            if ($vsite_php_settings->{"safe_mode"} ne "") {
                $pool_conf .= 'php_admin_flag[safe_mode] = ' . $vsite_php_settings->{"safe_mode"} . "\n"; 
            }
            if ($vsite_php_settings->{"safe_mode_gid"} ne "") {
                $pool_conf .= 'php_admin_flag[safe_mode_gid] =' . $vsite_php_settings->{"safe_mode_gid"} . "\n";
            }
            if ($vsite_php_settings->{"safe_mode_allowed_env_vars"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_allowed_env_vars] = ' . $vsite_php_settings->{"safe_mode_allowed_env_vars"} . "\n"; 
            }
            if ($vsite_php_settings->{"safe_mode_exec_dir"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_exec_dir] = ' . $vsite_php_settings->{"safe_mode_exec_dir"} . "\n"; 
            }

            if ($vsite_php_settings->{"safe_mode_include_dir"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_include_dir] = ' . $vsite_php_settings->{"safe_mode_include_dir"} . "\n"; 
            }
            if ($vsite_php_settings->{"safe_mode_protected_env_vars"} ne "") {
                $pool_conf .= 'php_admin_value[safe_mode_protected_env_vars] = ' . $vsite_php_settings->{"safe_mode_protected_env_vars"} . "\n"; 
            }
        }

        if ($vsite_php->{'version'} ne "PHP53") {
            &debug_msg("PHP version is $vsite_php->{'version'} - turning 'register_globals' off.\n");
            $vsite_php_settings->{"register_globals"} = "Off";
        }
        else {
            &debug_msg("Keeping 'register_globals' as it is as we're using: " . $vsite_php->{'version'} . "\n");
        }

        if ($vsite_php_settings->{"register_globals"} ne "") {
            $pool_conf .= 'php_admin_flag[register_globals] = ' . $vsite_php_settings->{"register_globals"} . "\n"; 
        }
        if ($vsite_php_settings->{"allow_url_fopen"} ne "") {
            $pool_conf .= 'php_admin_flag[allow_url_fopen] = ' . $vsite_php_settings->{"allow_url_fopen"} . "\n"; 
        }
        if ($vsite_php_settings->{"allow_url_include"} ne "") {
            $pool_conf .= 'php_admin_flag[allow_url_include] = ' . $vsite_php_settings->{"allow_url_include"} . "\n"; 
        }

        if ($vsite_php_settings->{"open_basedir"} ne "") {
            $pool_conf .= 'php_admin_value[open_basedir] = ' . $vsite_php_settings->{"open_basedir"} . "\n";
        }

        if ($vsite_php_settings->{"post_max_size"} ne "") {
            $pool_conf .= 'php_admin_value[post_max_size] = ' . $vsite_php_settings->{"post_max_size"} . "\n"; 
        }
        if ($vsite_php_settings->{"upload_max_filesize"} ne "") {
            $pool_conf .= 'php_admin_value[upload_max_filesize] = ' . $vsite_php_settings->{"upload_max_filesize"} . "\n"; 
        }
        if ($vsite_php_settings->{"max_execution_time"} ne "") {
            $pool_conf .= 'php_admin_value[max_execution_time] = ' . $vsite_php_settings->{"max_execution_time"} . "\n"; 
        }
        if ($vsite_php_settings->{"max_input_time"} ne "") {
            $pool_conf .= 'php_admin_value[max_input_time] = ' . $vsite_php_settings->{"max_input_time"} . "\n"; 
        }
        if ($vsite_php_settings->{"memory_limit"} ne "") {
            $pool_conf .= 'php_admin_value[memory_limit] = ' . $vsite_php_settings->{"memory_limit"} . "\n"; 
        }

        # Email related:
        $pool_conf .= 'php_admin_flag[mail.add_x_header] = On' . "\n";
        $pool_conf .= 'php_admin_value[sendmail_path] = /usr/sausalito/sbin/phpsendmail' . "\n";
        $pool_conf .= 'php_admin_value[auto_prepend_file] = /usr/sausalito/configs/php/set_php_headers.php' . "\n";

        # Edit the Pool config file or die!:
        &debug_msg("Editing PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");

        if(!Sauce::Util::editfile($pool_file, *pool_printer)) {
            &debug_msg("Failed to edit PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");
            $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
            exit(1);
        }

        system("/usr/bin/chmod 644 $pool_file");

    }
}

sub pool_printer {
    ($in, $out) = @_;
    print $out $pool_conf;
    return 1;
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