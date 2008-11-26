#!/usr/bin/perl
# $Id: java-initialize.pl,v 1.0.0 Wed Nov 26 15:17:50 2008 mstauber Exp $
# Copyright 2008, Solarspeed Ltd. and NuOnce Networks, Inc., All rights reserved.
#
# On first (re)start of CCE after base-java install copy the distributed config
# files into the right places and restart Tomcat5 if need be.

use lib qw(/usr/sausalito/perl);
use CCE;

my $cce = new CCE;

$cce->connectuds();

$cmd_tomcat = '/etc/init.d/tomcat5';
$sts_tomcat = "UNKNOWN";
$sts_tempfile = '/tmp/.tomcat5';

# Only do anything if we haven't already performed this step:
if (! -f "/etc/tomcat5/.setup") {
    system("/bin/cp /etc/tomcat5/tomcat-users.xml.dist /etc/tomcat5/tomcat-users.xml");
    system("/bin/cp /etc/tomcat5/tomcat5.logrotate.dist /etc/logrotate.d/tomcat5");
    system("/bin/chmod 0660 /etc/tomcat5/tomcat-users.xml");
    system("/bin/chown tomcat:tomcat /etc/tomcat5/tomcat-users.xml");
    system("/bin/touch /etc/tomcat5/.setup");
    system("/bin/echo '# Do not remove this file. Thanks!' >> /etc/tomcat5/.setup");

    # Check Tomcat5 status:
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
    
    # Tomcat5 is already running. We need to restart it:
    if ($sts_tomcat eq "RUNNING") {
	system("$cmd_tomcat restart > /dev/null 2>&1");
    }

}

$cce->bye('SUCCESS');
exit(0);

