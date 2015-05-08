#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_handler.pl
#
# This handler is run whenever a CODB Object called "PHP" is created, destroyed or 
# modified. 
#
# If the "PHP" Object with "applicable" => "server" is created or modified, it 
# updates php.ini with those changes and Apache is restarted. Additionally it
# handles updates to the PHP-FPM master-pool config files.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

# Uncomment correct type:
#$whatami = "constructor";
$whatami = "handler";

# Location of php.ini:
$php_ini = "/etc/php.ini";

# Location of the config file for Apache PHP DSO:
$php_dso_conf = '/etc/httpd/conf.modules.d/10-php.conf';
$php_dso_location = '/usr/lib64/httpd/modules/libphp5.so';

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
    $platform = $PHP->{'PHP_version'};
    if ($platform >= "5.3") {
        # More modern PHP found:
        $legacy_php = "0";
    }
    else {
        # Older PHP found:
        $legacy_php = "1";
    }

    # Get 'System' Object:
    @system_oid = $cce->find('System');
    ($ok, $System) = $cce->get($system_oid[0]);

    # Get system Timezone out of CODB:
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
                            'OS' => '9000',
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
            if ( -f $phpFpmPath ) {
                $known_php_fpm_pool_dirs{$phpVer} = '/etc/php-fpm-' . $known_php_versions{$phpVer} . '.d/';
                $known_php_fpm_pool_files{$phpVer} = $xpool_file;
                $known_php_fpm_pool_services{$phpVer} = 'php-fpm-' . $known_php_versions{$phpVer};
            }
            else {
                &debug_msg("Not adding $xpool_file as $phpFpmPath not there! \n");
            }
            # If we don't have $reportedVersion the binary reported a failure:
            if (( -f $phpFpmPath) && ($reportedVersion ne "")) {
                ($ok) = $cce->set($PHP_server_OID, "$phpVer", { 'present' => '1', 'version' => $reportedVersion });
            }
            else {
                ($ok) = $cce->set($PHP_server_OID, "$phpVer", { 'present' => '0', 'enabled' => '0', 'version' => "" });
            }
        }
    }
    else {
        $cce->bye('FAIL');
        exit(1);
    }

    # Add the OS related PHP into the mix as well:
    $known_php_inis{'OS'} = $php_ini;
    $seen_php_versions{'OS'} = $PHP->{'PHP_version_os'};
    $known_php_fpm_pool_dirs{'OS'} = '/etc/php-fpm.d/';
    $known_php_fpm_pool_files{'OS'} = '/etc/php-fpm.d/www.conf';
    $known_php_fpm_pool_services{'OS'} = 'php-fpm';

    # Vsite is going to be deleted.
    # We need to check if it has PHP-FPM enabled.
    # If so, we need to remove the pool file and need to restart PHP-FPM.
    if ($cce->event_is_destroy()) {
        $oid = $cce->event_oid();

        # Get Vsite info:
        $xvsite = $cce->event_old();

        &debug_msg("DELETE: Vsite $xvsite->{'fqdn'} (OID: $oid - Name: $xvsite->{'name'}) is supposed to be deleted. \n");

        # Get group just to be sure:
        $pool_group = $xvsite->{"name"};

        # Location of the PHP-FPM pool files:
        $pool_file_wildcard = '/etc/php-fpm*.d/';

        # Path:
        $pool_zapper = $pool_file_wildcard . $pool_group . '.conf';

        # Check for presence of a pool config of that name:
        $xcheck = `ls -k1 $pool_zapper|wc -l`;
        chomp($xcheck);

        # Check if it has PHP-FPM enabled:
        if ($xcheck ne "0") {
            &debug_msg("DELETE: Vsite $xvsite->{'fqdn'} ($xvsite->{'name'}) has PHP-FPM active! \n");

            # Check for the complete file name again:
            $xcheck_file = `ls -k1 $pool_zapper`;
            chomp($xcheck_file);

            &debug_msg("DELETE: Need to delete the file $xcheck_file \n");

            # Now we could just return the one reported file. But here is the catch:
            # It could have reported more than one if the server was already a bit
            # screwy. So we delete via Wildcard:
            &debug_msg("DELETE: Deleting via Wildcard $pool_zapper \n");
            system("/bin/rm -f $pool_zapper");

        }
    }

    if ($cce->event_is_create()) {
        &debug_msg("EVENT IS CREATE: $oid.\n");

        $new_vsite_php_settings_writeoff = { 
                'allow_url_fopen' => $PHP->{"allow_url_fopen"}, 
                'allow_url_include' => $PHP->{"allow_url_include"},
                'register_globals' => 'Off',  
                'open_basedir' => $PHP->{"open_basedir"}, 
                'post_max_size' => $PHP->{"post_max_size"}, 
                'upload_max_filesize' => $PHP->{"upload_max_filesize"},
                'max_execution_time' => $PHP->{"max_execution_time"}, 
                'max_input_time' => $PHP->{"max_input_time"}, 
                'max_input_vars' => $PHP->{"max_input_vars"}, 
                'memory_limit' => $PHP->{"memory_limit"},
                'force_update' => time()
        };

        if (($seen_php_versions{$PHP->{"PHP_version"}} eq 'PHP53') && ($PHP->{'register_globals_exception'} eq "1")) {
            &debug_msg("PHP-5.3 detected. Applying 'register_globals_exception' and setting 'register_globals' to 'On'.\n");
            $new_vsite_php_settings_writeoff->{'register_globals'} = 'On';
        }

        #&debug_msg("Populating new Vsite's PHPVsite with defaults from Server's PHP config.\n");
        #($ok) = $cce->set($oid, 'PHPVsite', $new_vsite_php_settings_writeoff);
    }

    # We're creating or modifying the main server PHP object:
    if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($PHP_server_OID eq $oid)) {

        # If someone used the "expert mode", move the temporary php.ini to 
        # the right place, chown it and restart Apache:
        # Function disabled for now!
        $edisabled = "1";
        if ((-f "/tmp/php.ini") && ($edisabled ne "1")) {
            system("/bin/chown root:root /tmp/php.ini");
            system("/bin/cp /tmp/php.ini $php_ini");
            system("/bin/rm -f /tmp/php.ini");
            &restart_apache;
        }
        else {
            # Someone used the GUI to edit some php.ini parameters. Update
            # the existing php.ini and restart Apache:
            if (-f $php_ini) {

                # Check if we need to switch the default PHP version to a different one:
                if ($PHP->{'PHP_version_os'} ne $PHP->{'PHP_version'}) {
                    &debug_msg("Default PHP version has changed to $PHP->{'PHP_version'}. Updating configs. \n");
                    &php_switcher;
                }

                # Edit all php.ini's of all known PHP versions:
                for $phpVer_ini (keys %known_php_inis) {
                    if (-f $known_php_inis{$phpVer_ini}) {
                        &debug_msg("Calling edit for $known_php_inis{$phpVer_ini} - $seen_php_versions{$phpVer_ini} \n");
                        &edit_php_ini($known_php_inis{$phpVer_ini}, $seen_php_versions{$phpVer_ini});
                    }
                }

                # Update the PHP-FPM master-pools:
                for $pool_file (keys %known_php_fpm_pool_files) {
                    &debug_msg("Calling edit for Pool-File $known_php_fpm_pool_files{$pool_file} - $known_pool_ports{$pool_file} \n");
                    &handle_fpm_master_pool($known_php_fpm_pool_files{$pool_file}, $known_pool_ports{$pool_file}, $known_php_fpm_pool_services{$pool_file}, $known_php_fpm_pool_dirs{$pool_file});
                }

                # Update PHP settings for Vsites:
                &update_vsites;

                # Restart Apache:
                &restart_apache;
            }
            else {
                # Ok, we have a problem: No php.ini found.
                # So we just weep silently and exit. 
                $cce->bye('FAIL', "$php_ini not found!");
                exit(1);
            }
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse php.ini:
sub ini_read {
    open (F, $php_ini) || die "Could not open $php_ini: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;                   # skip blank lines
        next if $line =~ /^\;*$/;                   # skip comment lines
        next if $line =~ /^url_rewriter(.*)$/;      # skip line starting with url_rewriter.tags
        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
        $line =~s/\s//g;                # Remove spaces
        $line =~s/;(.*)$//g;            # Remove trailing comments in lines
        $line =~s/\"//g;                # Remove double quotation marks

            @row = split (/=/, $line);          # Split row at the equal sign
            $CONFIG{$row[0]} = $row[1];         # Hash the splitted row elements
        }
    }
    close(F);

    # At this point we have all switches from php.ini cleanly in a hash, split in key / value pairs.
    # To read how "safe_mode" is set we query $CONFIG{'safe_mode'} for example. 

    if ($System->{'productBuild'} ne "5106R") {
        # If we're indeed running for the first time, make sure safe defaults
        # are set for all our remaining switches of most importance:
        $CONFIG{"safe_mode"} = "Off";
    }

}

sub restart_apache {
    # Restarts Apache - hard restart:
    if (-f "/usr/bin/systemctl") {
        # Got Systemd:
        system("systemctl restart httpd.service --no-block");
    }
    else {
        # Thank God, no Systemd:
        system("/sbin/service httpd restart");
    }
}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = (
        'safe_mode',
        'safe_mode_allowed_env_vars',
        'safe_mode_exec_dir',
        'safe_mode_gid',
        'safe_mode_include_dir',
        'safe_mode_protected_env_vars',
        'register_globals',
        'allow_url_fopen',
        'allow_url_include',
        'disable_classes',
        'disable_functions',
        'open_basedir',
        'post_max_size',
        'upload_max_filesize',
        'max_execution_time',
        'max_input_time',
        'max_input_vars',
        'memory_limit',
        'mail.add_x_header',
        'sendmail_path',
        'auto_prepend_file',
        'date.timezone'
        );
}

sub edit_php_ini {
    $php_ini = shift;
    $platform = shift;

    &debug_msg("Called to edit $php_ini \n");
    &debug_msg("Called to deal with $platform \n");

    # Making sure 'open_basedir' has the bare minimum defaults:
    @php_settings_temporary = split(":", $PHP->{"open_basedir"});
    @my_baremetal_minimums = ('/usr/sausalito/configs/php/', '/tmp/', '/var/lib/php/session/');
    @php_settings_temp_joined = (@php_settings_temporary, @my_baremetal_minimums);

    # Remove duplicates:
    foreach my $var ( @php_settings_temp_joined ){
        if ( ! grep( /$var/, @open_basedir ) ){
            push(@open_basedir, $var );
        }
    }
    $PHP->{"open_basedir"} = join(":", @open_basedir);

    # Making sure 'safe_mode_include_dir' has the bare minimum defaults:
    @smi_temporary = split(":", $PHP->{"safe_mode_include_dir"});
    @smi_baremetal_minimums = ('/usr/sausalito/configs/php/');
    @smi_temp_joined = (@smi_temporary, @smi_baremetal_minimums);
        
    # Remove duplicates:
    foreach my $var ( @smi_temp_joined ){
        if ( ! grep( /$var/, @safe_mode_include_dir ) ){
            push(@safe_mode_include_dir, $var );
        }
    }
    $PHP->{"safe_mode_include_dir"} = join(":", @safe_mode_include_dir);

    # Just to be really sure:
    unless (($PHP->{"open_basedir"} =~ m#/usr/sausalito/configs/php/#) && ($PHP->{"open_basedir"} =~ m#/tmp/#) && ($PHP->{"open_basedir"} =~ m#/var/lib/php/session/#)) {
        &debug_msg("Fixing 'open_basedir': It is missing our 'must have' entries. Restoring it to the defaults. \n");
        $PHP->{"open_basedir"} = "/tmp/:/var/lib/php/session/:/usr/sausalito/configs/php/";
    }

    # Making sure 'safe_mode_allowed_env_vars' has the bare minimum defaults:
    @smaev_temporary = split(",", $PHP->{"safe_mode_allowed_env_vars"});
    @smi_baremetal_minimums = ('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
    @smaev_temp_joined = (@smaev_temporary, @smi_baremetal_minimums);
        
    # Remove duplicates:
    foreach my $var ( @smaev_temp_joined ){
        if ( ! grep( /$var/, @safe_mode_allowed_env_vars ) ){
            push(@safe_mode_allowed_env_vars, $var );
        }
    }
    $PHP->{"safe_mode_allowed_env_vars"} = join(",", @safe_mode_allowed_env_vars);

    if ($platform >= "5.4") {
        $PHP->{"register_globals"} = "Off";
    }

    &debug_msg("PF-Debug: $seen_php_versions{$platform} for $platform \n");

    if (($seen_php_versions{$platform} eq "PHP53") && ($PHP->{"register_globals_exception"} eq "1")) {
        &debug_msg("Allowing 'register_globals' = 'On' for $platform \n");
        $PHP->{"register_globals"} = "On";
    }

    if ($legacy_php == "0") {
        # Build output hash for PHP-5.3 or newer:
        $PHP_writeoff = { 
            'register_globals' => $PHP->{"register_globals"}, 
            'allow_url_fopen' => $PHP->{"allow_url_fopen"}, 
            'allow_url_include' => $PHP->{"allow_url_include"}, 
            'disable_classes' => $PHP->{"disable_classes"}, 
            'disable_functions' => $PHP->{"disable_functions"}, 
            'open_basedir' => $PHP->{"open_basedir"}, 
            'post_max_size' => $PHP->{"post_max_size"}, 
            'upload_max_filesize' => $PHP->{"upload_max_filesize"},
            'max_execution_time' => $PHP->{"max_execution_time"}, 
            'max_input_time' => $PHP->{"max_input_time"}, 
            'max_input_vars' => $PHP->{"max_input_vars"}, 
            'memory_limit' => $PHP->{"memory_limit"}, 
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
        $PHP_writeoff = { 
            'safe_mode' => $PHP->{"safe_mode"}, 
            'safe_mode_allowed_env_vars' => $PHP->{"safe_mode_allowed_env_vars"}, 
            'safe_mode_exec_dir' => $PHP->{"safe_mode_exec_dir"}, 
            'safe_mode_gid' => $PHP->{"safe_mode_gid"}, 
            'safe_mode_include_dir' => $PHP->{"safe_mode_include_dir"}, 
            'safe_mode_protected_env_vars' => $PHP->{"safe_mode_protected_env_vars"},   
            'register_globals' => $PHP->{"register_globals"}, 
            'allow_url_fopen' => $PHP->{"allow_url_fopen"}, 
            'allow_url_include' => $PHP->{"allow_url_include"}, 
            'disable_classes' => $PHP->{"disable_classes"}, 
            'disable_functions' => $PHP->{"disable_functions"}, 
            'open_basedir' => $PHP->{"open_basedir"}, 
            'post_max_size' => $PHP->{"post_max_size"}, 
            'upload_max_filesize' => $PHP->{"upload_max_filesize"},
            'max_execution_time' => $PHP->{"max_execution_time"}, 
            'max_input_time' => $PHP->{"max_input_time"}, 
            'max_input_vars' => $PHP->{"max_input_vars"}, 
            'memory_limit' => $PHP->{"memory_limit"},
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
    &debug_msg("Editing $php_ini \n");
    $ok = Sauce::Util::editfile(
        $php_ini,
        *Sauce::Util::hash_edit_function,
        ';',
        { 're' => '=', 'val' => ' = ' },
        $PHP_writeoff);

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing $php_ini!");
        exit(1);
    }
}

sub update_vsites {

    # Find all Vsites:
    my @vhosts = ();
    my (@vhosts) = $cce->findx('Vsite');

    # Walk through all Vsites:
    for my $vsite (@vhosts) {
        ($ok, my $my_vsite) = $cce->get($vsite);
        &debug_msg("Updating PHP settings for Vsite $my_vsite->{fqdn} \n");
        ($ok) = $cce->set($vsite, 'PHPVsite',{ 'force_update' => time() });
    }
}

sub handle_fpm_master_pool {

    # Location of the PHP-FPM master-pool file:
    $pool_file = shift;

    # Pool Port:
    $fpmPort = shift;

    # Name of the service that needs restarting:
    $php_fpm_service = shift;
 
    # Pool directory:
    $pool_dir = shift;

    &debug_msg("Called to edit $pool_file \n");
    &debug_msg("Called on port $fpmPort \n");
    &debug_msg("For Service $php_fpm_service \n");

    # Get new prefered owner:
    $pool_UID = 'apache';

    # Get group just to be sure:
    $pool_group = 'apache';

    &debug_msg("Updating PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");
    
    #
    ### Define Pool config file contends:
    #

    $pool_conf = '; Global PHP-FPM settings for master-pool:' . "\n";
    $pool_conf .= '[global]' . "\n";
    $pool_conf .= 'log_level = notice' . "\n";
    $pool_conf .= 'emergency_restart_threshold = 0' . "\n";
    $pool_conf .= 'emergency_restart_interval = 0' . "\n";
    $pool_conf .= 'process_control_timeout = 0' . "\n";
    $pool_conf .= 'daemonize = yes' . "\n";
    $pool_conf .= '' . "\n";
    $pool_conf .= '; Pool name and settings:' . "\n";
    $pool_conf .= '[www]' . "\n";
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
    $pool_conf .= '; Global PHP settings inherited from the main php.ini:' . "\n";

    # Piece the individual PHP Vsite settings together:

    if ($legacy_php == "1") {
        # These options only apply to PHP versions prior to PHP-5.3:
        if ($PHP->{"safe_mode"} ne "") {
            $pool_conf .= 'php_admin_flag[safe_mode] = ' . $PHP->{"safe_mode"} . "\n"; 
        }
        if ($PHP->{"safe_mode_gid"} ne "") {
            $pool_conf .= 'php_admin_flag[safe_mode_gid] =' . $PHP->{"safe_mode_gid"} . "\n";
        }
        if ($PHP->{"safe_mode_allowed_env_vars"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_allowed_env_vars] = ' . $PHP->{"safe_mode_allowed_env_vars"} . "\n"; 
        }
        if ($PHP->{"safe_mode_exec_dir"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_exec_dir] = ' . $PHP->{"safe_mode_exec_dir"} . "\n"; 
        }

        if ($PHP->{"safe_mode_include_dir"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_include_dir] = ' . $PHP->{"safe_mode_include_dir"} . "\n"; 
        }
        if ($PHP->{"safe_mode_protected_env_vars"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_protected_env_vars] = ' . $PHP->{"safe_mode_protected_env_vars"} . "\n"; 
        }
    }

    if (($php_fpm_service eq "php-fpm-5.3") && ($PHP->{"register_globals_exception"} eq "1")) {
        &debug_msg("Allowing 'register_globals' = 'On' for $php_fpm_service \n");
        $PHP->{"register_globals"} = "On";
    }
    if ($PHP->{"register_globals"} ne "") {
        $pool_conf .= 'php_admin_flag[register_globals] = ' . $PHP->{"register_globals"} . "\n"; 
    }

    if ($PHP->{"allow_url_fopen"} ne "") {
        $pool_conf .= 'php_admin_flag[allow_url_fopen] = ' . $PHP->{"allow_url_fopen"} . "\n"; 
    }
    if ($PHP->{"allow_url_include"} ne "") {
        $pool_conf .= 'php_admin_flag[allow_url_include] = ' . $PHP->{"allow_url_include"} . "\n"; 
    }

    if ($PHP->{"open_basedir"} ne "") {
        $pool_conf .= 'php_admin_value[open_basedir] = ' . $PHP->{"open_basedir"} . "\n";
    }

    if ($PHP->{"post_max_size"} ne "") {
        $pool_conf .= 'php_admin_value[post_max_size] = ' . $PHP->{"post_max_size"} . "\n"; 
    }
    if ($PHP->{"upload_max_filesize"} ne "") {
        $pool_conf .= 'php_admin_value[upload_max_filesize] = ' . $PHP->{"upload_max_filesize"} . "\n"; 
    }
    if ($PHP->{"max_execution_time"} ne "") {
        $pool_conf .= 'php_admin_value[max_execution_time] = ' . $PHP->{"max_execution_time"} . "\n"; 
    }
    if ($PHP->{"max_input_time"} ne "") {
        $pool_conf .= 'php_admin_value[max_input_time] = ' . $PHP->{"max_input_time"} . "\n"; 
    }
    if ($PHP->{"max_input_vars"} ne "") {
        $pool_conf .= 'php_admin_value[max_input_vars] = ' . $PHP->{"max_input_vars"} . "\n"; 
    }
    if ($PHP->{"memory_limit"} ne "") {
        $pool_conf .= 'php_admin_value[memory_limit] = ' . $PHP->{"memory_limit"} . "\n"; 
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

sub pool_printer {
    ($in, $out) = @_;
    print $out $pool_conf;
    return 1;
}

sub php_switcher {

    # Check if the OS supplied PHP is any different than the PHP that Apache DSO is currently using:
    if ($PHP->{'PHP_version_os'} ne $PHP->{'PHP_version'}) {
        $thirdPartyCGI = $extra_PHP_basepath . "php-" . $known_php_versions{$seen_php_versions{$PHP->{PHP_version}}} . "/bin/php-cgi";
        $new_php_dso_location = $extra_PHP_basepath . "php-" . $known_php_versions{$seen_php_versions{$PHP->{PHP_version}}} . "/lib/httpd/libphp5.so";
    }
    else {
        $thirdPartyCGI = "/usr/bin/php-cgi";
        $new_php_dso_location = $php_dso_location;
    }

    &debug_msg("New Apache PHP-CGI location is: $thirdPartyCGI \n");
    &debug_msg("New Apache PHP DSO location is: $new_php_dso_location \n");

    # Update the path to the Apache PHP DSO in the Apache module config:
    if ((-f "$php_dso_conf") && ( -f "$new_php_dso_location")) {
        &debug_msg("Editing: $php_dso_conf \n");
        umask(0077);
        my $stage = "$php_dso_conf~";
        open(HTTPD, "$php_dso_conf");
        unlink($stage);
        sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
        while(<HTTPD>) {
            s/LoadModule php5_module (.*)/LoadModule php5_module $new_php_dso_location/g;
            print STAGE;
        }
        close(STAGE);
        close(HTTPD);
        chmod(0644, $stage);
        if(-s $stage) {
            move($stage,"$php_dso_conf");
            chmod(0644, "$php_dso_conf"); # paranoia
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