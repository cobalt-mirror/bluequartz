#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_vsite_handler.pl, v1.2.0.2 Sun 30 Aug 2009 02:42:08 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.

# This handler is run whenever a CODB Object called "Vsite" with namespace 
# "PHPVsite" is created, destroyed or modified. 
#
# If the "Vsite" Object of namespace "PHPVsite" Object with "applicable" => "siteXX" 
# is created or modified, it edits /etc/httpd/conf/vhosts/siteXX to write the 
# correct php_admin_flag's to that site's include file and Apache is restarted.

# Debugging switch:
$DEBUG = "0";

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

if ($whatami eq "handler") {
    $cce->connectfd();

    # Get our events from the event handler stack:
    $oid = $cce->event_oid();
    $obj = $cce->event_object();

    $old = $cce->event_old();
    $new = $cce->event_new();

    # Get Object System from CODB to find out which platform type this is:
    @sysoids = $cce->find('System');
    ($ok, $mySystem) = $cce->get($sysoids[0]);
    $platform = $mySystem->{'productBuild'};
    if ($platform == "5106R") {
	# CentOS5 related PHP found:
	$legacy_php = "1";
    }
    else {
	# More modern PHP found:
	$legacy_php = "0";
    }

    # Get Object PHP from CODB to find out how php.ini is configured:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);

    # Poll info about the Vsite in question:
    ($ok, $vsite) = $cce->get($oid);
    #$vsite->{"basedir"}

    # Get PHPVsite:
    ($ok, $vsite_php_settings) = $cce->get($oid, "PHPVsite");

    # Get PHP:
    ($ok, $vsite_php) = $cce->get($oid, "PHP");

    # Event is create or modify:
    if ((($cce->event_is_create()) || ($cce->event_is_modify()))) {

	# Edit the vhost container or die!:
	if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{"name"}), *edit_vhost, $vsite_php_settings)) {
	    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
	    exit(1);
	}
	
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

	    @vsite_php_settings_temp = split(":", $vsite_php_settings->{"open_basedir"});
	    foreach $entry (@vsite_php_settings_temp) {
		#system("echo $entry >> /tmp/debug.ms");
		$entry =~ s/\/home\/.sites\/(.*)\/(.*)\///;
		if ($entry ne "") {
		    push(@vsite_php_settings_new, $entry);
		}
	    }
	    if ($vsite_php_settings->{"open_basedir"} ne "") {
		$vsite_php_settings->{"open_basedir"} = join(":", @vsite_php_settings_new);
	    }

	    # Decision if we write 'open_basedir' to the site include file or not. We do NOT
	    # write an empty open_basedir. So if it is empty, we simply skip this step:
	    if ($empty_open_basedir != "1") {
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


$cce->bye('SUCCESS');
exit(0);

