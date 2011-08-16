#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: am_fans.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use AM::Util;
use strict;
use CCE;
use SendEmail;

my %am_states = am_get_statecodes();
my $speed;
my $red = 0;
my $yellow = 0;
my ($failed, $fannum, @redfailed, @yellowfailed);
my @output;

if (!defined($ENV{rThreshold})) {
    $ENV{rThreshold} = 2000;
}

if (!defined($ENV{yThreshold})) {
    $ENV{yThreshold} = 4000;
}

my $ret = open FANS, '< /proc/cobalt/faninfo';
if (!$ret) {
    print "[[base-am.cant_get_faninfo]]";
    exit $am_states{AM_STATE_NOINFO};
}
	
while (<FANS>) {
    /fan (\d+).*: (\d+)/;
    # ugly and bad hack until we get hardware abstraction
    # alpine ships with only fans 1, 2 and 3 plugged in
    if ($1 ne '1' && $1 ne '2' && $1 ne '3') {
	next;
    }
    $fannum = $1;
    $speed = $2;
    if ($speed < $ENV{rThreshold}) {
	push @redfailed, $fannum;
	$red = 1;
	next;
    }
    if ($speed < $ENV{yThreshold}) {
	push @yellowfailed, $fannum;
	$yellow = 1;
	next;
    }
}
    
if ($red) {
    # sysfault light
    my $cce = new CCE();
    $cce->connectuds();
    my ($am_oid) = $cce->find('ActiveMonitor');
    my ($ok) = $cce->set($am_oid, 'Fans', { 'sysfault' => 1});

    # send email
    my ($ok, $am) = $cce->get($am_oid);
    my @am_recips = $cce->scalar_to_array($am->{alertEmailList});
    my $recips = join(',', @am_recips);
    my @body;
    foreach my $failure (@redfailed) {
	push @body, "[[base-am.acpi_fans_shutdown_fan$failure]]";
    }
    SendEmail::sendEmail($recips, 'admin', 
       '[[base-am.acpi_fans_subject]]', join("\n", @body));
    
    # shutting down system in 3 minutes ... if we're on Alpine
    my $PRODUCT;
    my $PRODUCTFILE='/proc/cobalt/systype';
    chomp ($PRODUCT = `cat $PRODUCTFILE`) if ( -f $PRODUCTFILE );
    if ( $PRODUCT eq 'Alpine' ) {
        system('/sbin/shutdown -h +3 Fan has failed >/dev/null 2>&1 &');
    }

    foreach my $failure (@redfailed) {
	push @output, "[[base-am.amFansDangerouslySlow_email_fan$failure]]";
    }
    print join("\n", @output);
    exit $am_states{AM_STATE_RED};
}

if ($yellow) {
    foreach my $failure (@yellowfailed) {
	push @output, "[[base-am.amFansSlow_email_fan$failure]]";
    }
    print join("\n", @output);
    exit $am_states{AM_STATE_YELLOW};
}

print "[[base-am.amFansOK]]";
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
