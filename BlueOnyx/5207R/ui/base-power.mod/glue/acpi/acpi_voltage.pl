#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: acpi_voltage.pl,v 1.2.2.2 2002/02/20 04:03:19 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;
use CCE;
use SendEmail;

my $failure = 0;
my $proc_voltage = "/proc/cobalt/sensors/voltage";
my $adm1029ctl = "/sbin/adm1029ctl";
my $sensor = {
    Vcore => { min => 1.2, max => 2.0 },
    Vtt   => { min => 1.0, max => 1.7 },
};

# Sleep for 10 seconds... give things a chance to get reset.
sleep 10;

if (-x $adm1029ctl and open ADM, "$adm1029ctl --dump |") {
    while (<ADM>) {
        if (/^vtt min: ([\d\.]+)$/) {
            $sensor->{Vtt}{min} = $1;
        }
        elsif (/^vtt max: ([\d\.]+)$/) {
            $sensor->{Vtt}{max} = $1;
        }
        elsif (/^vcore min: ([\d\.]+)$/) {
            $sensor->{Vcore}{min} = $1;
        }
        elsif (/^vcore max: ([\d\.]+)$/) {
            $sensor->{Vcore}{max} = $1;
        }
    }
    close ADM;
}

# If the max is less than the min, don't bother checking
for (keys %$sensor) {
    my $s = $sensor->{$_};
    next unless $s->{max};

    if ($s->{max} < $s->{min}) {
        $s->{min} = 0;
        $s->{max} = 0;
        next;
    }
}

$failure = 1 unless (-e $proc_voltage and open PROC, $proc_voltage);
while (<PROC>) {
    next unless /^\d+\s*\[(.*?)\]:\s*(-?[\d\.]+)$/;
    my $name = $1;
    my $volt = $2;
    next unless exists($sensor->{$name});
    my $s = $sensor->{$name};

    if (($s->{min} and $volt < $s->{min}) or
        ($s->{max} and $volt > $s->{max})) {
	    $failure = 1;
    }
}
close PROC;

# If there's no failure, reset the adm1029 and exit this script
unless ($failure) {
    `$adm1029ctl --reset > /dev/null 2>&1`;
    exit 0;
}

# If we're past this point, the fault is real (not a momentary thing)...
# ... set the sysfault like, send an email and power down.

# Turn on sysfault light.
my $sysfault_light = '/proc/sys/cobalt/sysfault';
if (-f $sysfault_light) {
        `/bin/echo 1 > $sysfault_light 2> /dev/null`;
}

my $cce = new CCE();
$cce->connectuds();
my ($am_oid) = $cce->find('ActiveMonitor');

# send email
my ($ok, $am) = $cce->get($am_oid);
my @am_recips = $cce->scalar_to_array($am->{alertEmailList});
my $recips = join(',', @am_recips);
SendEmail::sendEmail($recips, 'admin',
	'[[base-power.acpi_power_subject]]',
	'[[base-power.acpi_power_shutdown]]');

# shutting down system in 1 minute
system('/sbin/shutdown -h +1 Power supply failing >/dev/null 2>&1 &');
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
