#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: am_temp.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use AM::Util;
use strict;
use CCE;

my $proc_thermal = "/proc/cobalt/sensors/thermal";
my $adm1029ctl = "/sbin/adm1029ctl";
my %am_states = am_get_statecodes();
my $red = 0;
my $yellow = 0;
my $sensor = {
    CPU  => { max => 80 },
    Case => { max => 58 },
};

if (-x $adm1029ctl and open ADM, "$adm1029ctl --dump |") {
    while (<ADM>) {
	if (/^cpu temp max: (-?[\d\.]+)$/) {
	    $sensor->{CPU}{max} = $1 if ($1 > 0);
	}
	elsif (/^board temp max: (-?[\d\.]+)$/) {
	    $sensor->{Case}{max} = $1 if ($1 > 0);
	}
    }
    close ADM;
}

for (keys %$sensor) {
    my $s = $sensor->{$_};
    $s->{AM_STATE_RED}    = 0.90 * $s->{max};
    $s->{AM_STATE_YELLOW} = 0.80 * $s->{max};
}

unless (-e $proc_thermal and open PROC, $proc_thermal) {
    print "[[base-am.cant_get_tempinfo]]";
    exit $am_states{AM_STATE_NOINFO};
}

while (<PROC>) {
    next unless /^\d+\s*\[(.*?)\]:\s*(-?[\d\.]+)$/;
    my $name = $1;
    my $temp = $2;
    next unless exists($sensor->{$name});
    my $s = $sensor->{$name};

    if ($temp > $s->{AM_STATE_RED}) {
	$red = 1;
    }
    elsif ($temp > $s->{AM_STATE_YELLOW}) {
	$yellow = 1;
    }
}
close PROC;
    
if ($red) {
    # sysfault light
    my $cce = new CCE();
    $cce->connectuds();
    my ($am_oid) = $cce->find('ActiveMonitor');
    my ($ok) = $cce->set($am_oid, 'Temp', { 'sysfault' => 1 });
    $cce->bye('SUCCESS');

    print "[[base-am.amTempsDangerouslyHot]]";
    exit $am_states{AM_STATE_RED};
}

if ($yellow) {
    print "[[base-am.amTempsHot]]";
    exit $am_states{AM_STATE_YELLOW};
}

print "[[base-am.amTempsOK]]";
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
