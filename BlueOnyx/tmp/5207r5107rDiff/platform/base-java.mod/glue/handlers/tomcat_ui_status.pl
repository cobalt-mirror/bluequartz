#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: tomcat_ui_status.pl, v1.1.0 Fri 28 Nov 2008 02:23:44 AM CET mstauber Exp $
# Copyright 2006-2008 Solarspeed Ltd. All rights reserved.

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# check to see if the service is presently running;
$status = &tomcat_pstest();

# Write the info into CCE:
&feedthemonster;

$cce->bye('SUCCESS');
exit(0);

sub tomcat_pstest {
        my $running = 0;
        open(PS, "/bin/ps axwww|") || die "Process list command '/bin/ps' unavailable: $!";
        while(<PS>)
        {
                if (/java/ && /tomcat6/)
                {
                        $running = 1;
                        last;
                }
        }
        close(PS);

        return $running;
}

sub feedthemonster {
    ($sys_oid) = $cce->find('System');
    if ($sys_oid) {
	($ok) = $cce->set($sys_oid, 'JavaStatus', {
	    'TomcatStatus' => $status
	});
    }
}
