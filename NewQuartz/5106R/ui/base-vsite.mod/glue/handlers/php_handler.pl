#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_handler.pl, v1.2.0.0 Mon 01 Dec 2008 05:36:01 AM CET mstauber Exp $
# Copyright 2006-2008 Solarspeed Ltd. All rights reserved.

# This handler is run whenever a CODB Object called "PHP" is created, destroyed or 
# modified. 
#
# If the "PHP" Object with "applicable" => "server" is created or modified, it 
# updates php.ini with those changes and Apache is restarted.
#
# Class Vsite can have the namespace Object "PHP" in it. This is for PHP settings of
# a site. If this is created or modified, then /etc/http/conf/vhosts/site1 is modified 
# with those changes and Apache is restarted. (Not yet implemented!)
#
# If a site is created, the PHP Object for it doesn't exist yet. Then it is the job of 
# this handler to create it and THEN to make the changes to the sites config file. 
# Finally it also restarts Apache when done. (Not yet implemented!)
#
# Lastly, if a site is deleted, the corresponding sites PHP Object is deleted from CODB
# automatically when that Vsite object is deleted anyway.

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
#$whatami = "constructor";
$whatami = "handler";

# Location of php.ini:
$php_ini = "/etc/php.ini";

#
#### No configureable options below!
#

# Location of config file in which 3rd party vendors can
# specify where their 3rd party PHP's php.ini is located:
#
# IMPORTANT: Do NOT modify the line below, as this script WILL
# be updated through YUM from time to time. Which overwrites 
# any changes you make in here!
$thirdparty = "/etc/thirdparty_php";

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();

    if ($DEBUG eq "1") {
	system("rm -f /tmp/php.debug");
    }

    # Get our events from the event handler stack:
    $oid = $cce->event_oid();
    $obj = $cce->event_object();

    $old = $cce->event_old();
    $new = $cce->event_new();

    # Get Object PHP for php.ini:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);
    $PHP_server_OID = $oids[0];

    if ($DEBUG eq "1") {
	system("echo oid $oid >> /tmp/php.debug");
        system("echo obj $obj >> /tmp/php.debug");
        system("echo out $out >> /tmp/php.debug");
    }
    
    # Check for presence of third party config file:
    &thirdparty_check;

    # We're creating or modifying the main server PHP object:
    if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($PHP_server_OID eq $oid)) {
	if ($DEBUG eq "1") {
	    system("echo 'server create event' >> /tmp/php.debug");
	}
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
		# Edit php.ini:
		&edit_php_ini;

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
else {
    $cce->connectuds();

    # Check for presence of third party config file:
    &thirdparty_check;

    # Get Object PHP for php.ini:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);
    $PHP_server_OID = $oids[0];

    if (-f $php_ini) {
	&edit_php_ini;
	&restart_apache;
    }
    else {
	# Ok, we have a problem: No php.ini found.
	# So we just weep silently and exit. 
	$cce->bye('FAIL', "$php_ini not found!");
	exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse php.ini:
sub ini_read {
    open (F, $php_ini) || die "Could not open $php_ini: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               	# skip blank lines
        next if $line =~ /^\;*$/;               	# skip comment lines
        next if $line =~ /^url_rewriter(.*)$/;    	# skip line starting with url_rewriter.tags
        if ($line =~ /^([A-Za-z_\.]\w*)/) {		
	    $line =~s/\s//g; 				# Remove spaces
	    $line =~s/;(.*)$//g; 			# Remove trailing comments in lines
	    $line =~s/\"//g; 				# Remove double quotation marks

            @row = split (/=/, $line);			# Split row at the equal sign
    	    $CONFIG{$row[0]} = $row[1];			# Hash the splitted row elements
        }
    }
    close(F);

    # At this point we have all switches from php.ini cleanly in a hash, split in key / value pairs.
    # To read how "safe_mode" is set we query $CONFIG{'safe_mode'} for example. 

    # For debugging only:
    if ($DEBUG > "1") {
	while (my($k,$v) = each %CONFIG) {
    	    print "$k => $v\n";
	}
    }

    # For debugging only:
    if ($DEBUG == "1") {
	print "safe mode: " . $CONFIG{'safe_mode'} . "\n";
    }

}

sub thirdparty_check {
    # Check for presence of third party config file:
    if (-f $thirdparty) {
	open (F, $thirdparty) || die "Could not open $thirdparty: $!";
	while ($line = <F>) {
    	    chomp($line);
    	    next if $line =~ /^\s*$/;               	# skip blank lines
    	    next if $line =~ /^#$/;               	# skip comments
    	    if ($line =~ /^\/(.*)\/php\.ini$/) {
		$php_ini = $line;
	    }
	}
	close(F);
    }
}

sub restart_apache {
    # Restarts Apache - hard restart:
    service_run_init('httpd', 'restart');
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
	'upload_max_filesize'
	);
}

sub edit_php_ini {

    # Build output hash:
    $server_php_settings_writeoff = { 
	'safe_mode' => $server_php_settings->{"safe_mode"}, 
	'safe_mode_allowed_env_vars' => $server_php_settings->{"safe_mode_allowed_env_vars"}, 
	'safe_mode_exec_dir' => $server_php_settings->{"safe_mode_exec_dir"}, 
	'safe_mode_gid' => $server_php_settings->{"safe_mode_gid"}, 
	'safe_mode_include_dir' => $server_php_settings->{"safe_mode_include_dir"}, 
	'safe_mode_protected_env_vars' => $server_php_settings->{"safe_mode_protected_env_vars"},	
	'register_globals' => $server_php_settings->{"register_globals"}, 
	'allow_url_fopen' => $server_php_settings->{"allow_url_fopen"}, 
	'allow_url_include' => $server_php_settings->{"allow_url_include"}, 
	'disable_classes' => $server_php_settings->{"disable_classes"}, 
	'disable_functions' => $server_php_settings->{"disable_functions"}, 
	'open_basedir' => $server_php_settings->{"open_basedir"}, 
	'post_max_size' => $server_php_settings->{"post_max_size"}, 
	'upload_max_filesize' => $server_php_settings->{"upload_max_filesize"}
	};

    # Write changes to php.ini using Sauce::Util::hash_edit_function. The really GREAT thing
    # about this function is that it replaces existing values and appends those new ones that 
    # are missing in the output file. And it does it for ALL values in our hash in one go.

    $ok = Sauce::Util::editfile(
        $php_ini,
        *Sauce::Util::hash_edit_function,
        ';',
        { 're' => '=', 'val' => ' = ' },
        $server_php_settings_writeoff);

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing $php_ini!");
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

