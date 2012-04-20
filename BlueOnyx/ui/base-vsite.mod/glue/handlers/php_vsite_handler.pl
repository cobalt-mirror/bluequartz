#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_vsite_handler.pl, v1.2.0.4 Mon 22 Aug 2011 10:26:07 PM EDT mstauber Exp $
# Copyright 2006-2011 Team BlueOnyx. All rights reserved.

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

# Location of config file in which 3rd party vendors can
# specify where their 3rd party PHP's php.ini is located:
#
# IMPORTANT: Do NOT modify the line below, as this script WILL
# be updated through YUM from time to time. Which overwrites
# any changes you make in here!
$thirdparty = "/etc/thirdparty_php";

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
    ($ok, $mySystem) = $cce->get($sysoids[0]);
    $platform = $mySystem->{'PHP_version'};
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
		&debug_msg("Configuring $custom_php_ini_path through php_vsite_handler.pl \n");
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

	    # Some BX users apparently want open_basedir to be empty. Security wise this is a bad idea,
	    # but if they really want to let their pants down that far ... <sigh>. New provision to check
	    # if 'open_basedir' is empty in the GUI. We act a bit later on based on this switch:
	    $empty_open_basedir = "0";
	    if ($vsite_php_settings->{"open_basedir"} eq "") {
		$empty_open_basedir = "1";
	    }

	    # We need to remove any site path references from open_basedir, because they could be from the wrong site,
	    # like during a cmuImport, when it inherited the path it had on the server it was exported from.

	    @vsite_php_settings_temporary = split(":", $vsite_php_settings->{"open_basedir"});
	    @my_server_php_settings_temp = split(":", $mySystem->{'open_basedir'});
	    @vsite_php_settings_temp_joined = (@vsite_php_settings_temporary, @my_server_php_settings_temp);

	    # Remove duplicates:
	    foreach my $var ( @vsite_php_settings_temp_joined ){
   		if ( ! grep( /$var/, @vsite_php_settings_temp ) ){
      		    push( @vsite_php_settings_temp, $var );
      		}
   	    }

	    # Remove Vsite home directory from 'open_basedir' for now. We add it again later:
	    foreach $entry (@vsite_php_settings_temp) {
		$entry =~ s/\/home\/.sites\/(.*)\/(.*)\///;
		if ($entry ne "") {
		    push(@vsite_php_settings_new, $entry);
		}
	    }
	    if ($vsite_php_settings->{"open_basedir"} ne "") {
		$vsite_php_settings->{"open_basedir"} = join(":", @vsite_php_settings_new);
	    }

	    # Make sure that the path to the prepend file directory is allowed, too:
	    unless ($vsite_php_settings->{"open_basedir"} =~ m/\/usr\/sausalito\/configs\/php\//) {
	        &debug_msg("1. Adding prepend file directory to open_basedir through php_vsite_handler.pl \n");
    	        $vsite_php_settings->{"open_basedir"} .= $vsite_php_settings->{"open_basedir"} . ':/usr/sausalito/configs/php/';
	    }

	    # Decision if we write 'open_basedir' to the site include file or not. We do NOT
	    # write an empty open_basedir. So if it is empty, we simply skip this step:
	    if ($empty_open_basedir != "1") {

		# Make sure that the path to the prepend file directory is allowed, too:
		unless ($vsite_php_settings->{"open_basedir"} =~ m/\/usr\/sausalito\/configs\/php\//) {
		    &debug_msg("2. Adding prepend file directory to open_basedir through php_vsite_handler.pl \n");
    	    	    $vsite_php_settings->{"open_basedir"} .= $vsite_php_settings->{"open_basedir"} . ':/usr/sausalito/configs/php/';
		}

		# Decide if we need to add the sites homedir to open_basedir or not:
		if ($vsite_php_settings->{"open_basedir"} =~ m/$vsite->{"basedir"}\//) {
		    # If the site's basedir path is already present, we use whatever paths open_basedir currently has:
		    $script_conf .= 'php_admin_value open_basedir ' . $vsite_php_settings->{"open_basedir"} . "\n"; 
		}
		else {
		    # If the sites path to it's homedir is missing, we add it here:
		    $script_conf .= 'php_admin_value open_basedir ' . $vsite_php_settings->{"open_basedir"} . ':' . $vsite->{"basedir"} . '/' . "\n"; 
		}
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

    # preserve the remainder of the config file
    while(<$in>) {
        print $out $_;
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
    if (-f $thirdparty) {
        open (F, $thirdparty) || die "Could not open $thirdparty: $!";
        while ($line = <F>) {
            chomp($line);
            next if $line =~ /^\s*$/;                   # skip blank lines
            next if $line =~ /^#$/;                     # skip comments
            if ($line =~ /^\/(.*)\/php\.ini$/) {
                $php_ini = $line;
            }  
        }
        close(F); 
    }
}

sub edit_php_ini {

    if ($vsite_php_settings->{"open_basedir"} =~ m/$vsite->{"basedir"}\//) {
        # If the site's basedir path is already present, we use whatever paths open_basedir currently has:
        $out_open_basedir = $vsite_php_settings->{"open_basedir"};
    }
    else {
	# If the sites path to it's homedir is missing, we add it here:
	$out_open_basedir = $vsite_php_settings->{"open_basedir"} . ':' . $vsite->{"basedir"} . '/';
    }

    unless ($vsite_php_settings->{"open_basedir"} =~ m/\/usr\/sausalito\/configs\/php\//) {
	&debug_msg("Adding prepend file directory to Vsite's php.ini's open_basedir through php_vsite_handler.pl \n");
	$out_open_basedir = $vsite_php_settings->{"open_basedir"} . ':/usr/sausalito/configs/php/';
    }

    &debug_msg("Server wide Open Basedir is set to: $server_php_settings->{'open_basedir'} \n");
    &debug_msg("Open Basedir was set to: $vsite_php_settings->{'open_basedir'} \n");
    &debug_msg("Open Basedir is now set to: $out_open_basedir \n");

    if ($legacy_php == "0") {
        # Build output hash for PHP-5.3 or newer:
        $vsite_php_settings_writeoff = { 
                'register_globals' => $vsite_php_settings->{"register_globals"}, 
                'allow_url_fopen' => $vsite_php_settings->{"allow_url_fopen"}, 
                'allow_url_include' => $vsite_php_settings->{"allow_url_include"}, 
                'disable_classes' => $vsite_php_settings->{"disable_classes"}, 
                'disable_functions' => $vsite_php_settings->{"disable_functions"}, 
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

$cce->bye('SUCCESS');
exit(0);

