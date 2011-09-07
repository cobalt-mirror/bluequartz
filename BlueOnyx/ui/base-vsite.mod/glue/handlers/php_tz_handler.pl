#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_zt_handler.pl, v1.2.0.0 Wed 07 Sep 2011 05:42:51 AM CEST mstauber Exp $
# Copyright 2006-2008 Solarspeed.net. All rights reserved.
# Copyright 2008-2011 Team BlueOnyx. All rights reserved.

# This handler is run whenever the Server TimeZone info changes and then updates
# the 'date.timezone' info in our php.ini files.

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
#$whatami = "constructor";
$whatami = "handler";


#
#### No configureable options below!
#

# Location of php.ini:
$php_ini = "/etc/php.ini";

# Location of AdmServ php.ini:
$admserv_php_ini = "/etc/admserv/php.ini";

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
use Sauce::Config;
use FileHandle;
use File::Copy;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();

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

    # Get Object PHP for php.ini:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);
    $PHP_server_OID = $oids[0];

    # Get system Timezone out of CODB:
    @system_oid = $cce->find('System');
    ($ok, $tzdata) = $cce->get($system_oid[0], "Time");
    $timezone = $tzdata->{'timeZone'};


    # Check for presence of third party config file:
    &thirdparty_check;

    # Update Apache php.ini:
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

    # Now the same for AdmServ's php.ini:
    $php_ini = $admserv_php_ini;

    if (-f $php_ini) {
	# Edit php.ini:
	&edit_php_ini;
	# Note to self: No AdmServ reload or restart, as it could be dangerous!
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
    system("/etc/init.d/httpd restart");
}

sub edit_php_ini {

    # Build output hash for PHP-5.3 or newer:
    $server_php_settings_writeoff = { 
	'date.timezone' => "'" . $timezone . "'"
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

