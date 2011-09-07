#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: admserv_php_ini_timezone_set.pl, v1.2.0.0 Mon 01 Dec 2008 05:36:01 AM CET mstauber Exp $
# Copyright 2006-2011 Solarspeed.net. All rights reserved.

# This handler is run whenever a CODB Object called "PHP" is created, destroyed or 
# modified. 
#
# If the "PHP" Object with "applicable" => "server" is created or modified, it 
# updates php.ini with those changes and Apache is restarted.

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
$whatami = "constructor";

# Location of php.ini:
$php_ini = "/etc/admserv/php.ini";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;
use Sauce::Config;
use FileHandle;
use File::Copy;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "constructor") {
    $cce->connectuds();

    # Get system Timezone out of CODB:
    @system_oid = $cce->find('System');
    ($ok, $tzdata) = $cce->get($system_oid[0], "Time");
    $timezone = $tzdata->{'timeZone'};

    # Update AdmServ php.ini:
    if (-f $php_ini) {
	# Edit php.ini:
	&edit_php_ini;
	# Note to self: We do not restart AdmServ, as this could be tricky.
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

}

sub edit_php_ini {

    # Build output hash:
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

