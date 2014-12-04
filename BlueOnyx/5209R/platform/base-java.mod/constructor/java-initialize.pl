#!/usr/bin/perl
# $Id: java-initialize.pl,v 1.0.0 Thu Nov 27 07:31:06 2008 mstauber Exp $
# Copyright 2008, Solarspeed Ltd. and NuOnce Networks, Inc., All rights reserved.
#
# On first (re)start of CCE after base-java install copy the distributed config
# files into the right places and restart tomcat6 if need be.

use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;

my $cce = new CCE;

$cce->connectuds();

$cmd_tomcat = '/sbin/service tomcat6';
$sts_tomcat = "UNKNOWN";
$sts_tempfile = '/tmp/.tomcat6';
$tomcat_properties = '/etc/tomcat6/tomcat-users.xml';

# Only do anything if we haven't already performed this step:
if (! -f "/etc/tomcat6/.setup") {
    system("/bin/cp /etc/tomcat6/tomcat-users.xml.dist /etc/tomcat6/tomcat-users.xml");
    system("/bin/cp /etc/tomcat6/tomcat6.logrotate.dist /etc/logrotate.d/tomcat6");
    system("/bin/chmod 0660 /etc/tomcat6/tomcat-users.xml");
    system("/bin/chown tomcat:tomcat /etc/tomcat6/tomcat-users.xml");
    system("/bin/touch /etc/tomcat6/.setup");
    system("/bin/echo '# Do not remove this file. Thanks!' >> /etc/tomcat6/.setup");

    # Set the password for Tomcat user 'admin' to some random string of 11 character length:
    # We do this to prevent the introduction of a default password weakness:
    $random_string=&generate_random_string(11);
    $ret = Sauce::Util::editfile($tomcat_properties, *edit_policy, $random_string);

    # Check tomcat6 status - this will work regardless which language the console is set to:
    $rtn_tomcat = system("$cmd_tomcat status > $sts_tempfile");
    open (F, $sts_tempfile) || die "Could not open $sts_tempfile: $!";
    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               # skip blank lines
        if ($line =~ /([0-9])/) {
                $sts_tomcat = "RUNNING";
        }
        else {
                $sts_tomcat = "STOPPED";
	}        
    }
    close(F);
    system("/bin/rm -f $sts_tempfile");
    
    # tomcat6 is already running. We need to restart it:
    if ($sts_tomcat eq "RUNNING") {
	system("$cmd_tomcat restart > /dev/null 2>&1");
    }

}

# This function generates random strings of a given length
sub generate_random_string {
    my $length_of_randomstring=shift;
    my @chars=('a'..'z','A'..'Z','0'..'9','_');
    my $random_string;
    foreach (1..$length_of_randomstring) {
	# rand @chars will generate a random 
	# number between 0 and scalar @chars
	$random_string.=$chars[rand @chars];
    }
    return $random_string;
}

sub edit_policy {
    my ($in, $out, $max) = @_;
    my $maxConnect = "  <user username=\"admin\" password=\"$max\" roles=\"admin,manager\"/>\n";

    while(<$in>) {
        if(/  <user username=\"admin\" password(.+)$/) {
    	    print $out $maxConnect;
        } else {
            print $out $_;
        }
    }
    return 1;
}

$cce->bye('SUCCESS');
exit(0);

