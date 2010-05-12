#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: am_voltage.pl,v 1.5.2.3 2002/02/15 03:12:16 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use AM::Util;
use strict;
use SendEmail;
use CCE;

my $proc_voltage = "/proc/cobalt/sensors/voltage";
my $adm1029ctl = "/sbin/adm1029ctl";
my %am_states = am_get_statecodes();
my $sensor = {
    Vcore => { min => 1.2, max => 2.0 },
    Vtt   => { min => 1.0, max => 1.7 },
    Vbat  => { min => 1.7 }
};

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

# adjust margins ~10% or we never see error case.
# ( when voltage exceeds adm1029 high/low limit )
# ( the ACPI event handler will shutdown system )
for (keys %$sensor) {
    my $s = $sensor->{$_};
    next unless $s->{max};

    if ($s->{max} < $s->{min}) {
	$s->{min} = 0;
	$s->{max} = 0;
	next;
    }

    my $range = $s->{max} - $s->{min};
    $s->{min} += $range / 10;
    $s->{max} -= $range / 10;
}

unless (-e $proc_voltage and open PROC, $proc_voltage) {
    print "[[base-power.cant_get_power_info]]";
    exit $am_states{AM_STATE_NOINFO};
}

while (<PROC>) {
    next unless /^\d+\s*\[(.*?)\]:\s*(-?[\d\.]+)$/;
    my $name = $1;
    my $volt = $2;
    next unless exists($sensor->{$name});
    my $s = $sensor->{$name};

    if (($s->{min} and $volt < $s->{min}) or 
	($s->{max} and $volt > $s->{max})) {
	$s->{AM_STATE_RED} = 1;
    }

    if ($name eq 'Vbat' and $volt < $s->{min}) { # Special case -- the battery
	my $cce = new CCE();
	$cce->connectuds();
	my ($am_oid) = $cce->find('ActiveMonitor');

	# send email
	my ($ok, $am) = $cce->get($am_oid);
	my @am_recips = $cce->scalar_to_array($am->{alertEmailList});
	my $recips = join(',', @am_recips);
	SendEmail::sendEmail($recips, 'admin',
		'[[base-power.batteryCriticalSubject]]',
		'[[base-power.batteryCritical]]');
	$cce->bye('SUCCESS');
    } 
}
close PROC;

sub am_voltage_sysfault {
    eval {
	require CCE;
	my $cce = new CCE();
	eval $cce->connectuds();
	my ($am_oid) = $cce->find('ActiveMonitor');
	my ($ok) = $cce->set($am_oid, 'Voltage', { 'sysfault' => 1});
    };
}

if ($sensor->{Vtt}{AM_STATE_RED} or $sensor->{Vcore}{AM_STATE_RED}) {
    &am_voltage_sysfault;
    print "[[base-power.powerCritical]]";
    exit $am_states{AM_STATE_RED};
}

if ($sensor->{Vbat}{AM_STATE_RED}) {
    &am_voltage_sysfault;
    print "[[base-power.batteryCritical]]";
    exit $am_states{AM_STATE_RED};
}

print "[[base-power.powerOK]]";
exit $am_states{AM_STATE_GREEN};

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
