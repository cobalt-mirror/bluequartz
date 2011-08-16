#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: generate_process_list.pl v1.1.0.0 Thu 02 Jul 2009 01:00:42 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler polls a server for running processes and stores that info into CCE.

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$DEBUG = 0;

my $cce = new CCE('Domain' => 'base-console');

if ($DEBUG == 0) {
    $cce->connectfd();
}
else {
    $cce->connectuds(); # only for debugging
}

if ($DEBUG == 0) {
    ($sys_oidnode) = $cce->find('SOL_Console');
    ($ok, $MainNode) = $cce->get($sys_oidnode);
}

# Location of the temporary-dump:
$psdump = "/tmp/console.process-list";

# We are starting sane:
$bad_info_detected = '0';

# Data handling:
&main;

# If we detect bad info, we will try again. Up to three times.
# Then we give up as something is barfed up badly.
if ($bad_info_detected < '3') {
    &main;
}
else {
    exit 1;
}

$cce->bye('SUCCESS');
exit(0);

##
# Subs:
##

sub main {

    # Generate dump:
    system("/bin/ps auxwf > $psdump");

    open (F, $psdump) || die "Could not open $psdump: $!";

    $PROCS = "";
    while ($line = <F>) {
        chomp($line);
        $PROCS = $PROCS . "#DELI#" . $line;
    }

    &feedthemonster;
    close(F);
    system("/bin/rm -f $psdump");

}

# Subroutine that feeds the data into CCE:
sub feedthemonster {
    ($sys_oid) = $cce->find('SOL_Console');
    ($ok, $sys) = $cce->get($sys_oid);
		
    ($ok) = $cce->set($sys_oid, '',{
        'sol_processes' => $PROCS,
        'timestamp' => time()
        });

}

$cce->bye('SUCCESS');
exit(0);

