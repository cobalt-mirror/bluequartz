#!/usr/bin/perl -w -I/usr/sausalito/perl 
# $Id: productGen_features.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $obj = $cce->event_object();

my %v2iii = {
	'/etc/lcd.d/10main.m/30POWER.m' => '/etc/lcd.d/10main.m/.30POWER.m',
	'/etc/lcd.d/10main.m/30POWER.s' => '/etc/lcd.d/10main.m/.30POWER.s',
	};
	
my (@am_oids) = $cce->find('ActiveMonitor');
my ($v, $voltage) = $cce->get( $am_oids[0], 'Voltage');

my $f;
if($obj->{productGen} eq 'III') { # hard power switch; no voltage detection
	foreach $f (keys %v2iii) {
		$DEBUG && warn "$f rename to ". $v2iii{$f}."\n";
		rename ($f, $v2iii{$f}) if ((-d $f) || (-e $f));
	}

	if($v) {
		if (!$voltage->{hideUI} || 
			$voltage->{enabled} || 
			$voltage->{monitor}) {
			$cce->set($am_oids[0], 'Voltage', {
				'hideUI' => 1,
				'enabled' => 0,
				'monitor' => 0
				});
		}
	} 

} elsif ($obj->{productGen} eq 'V') { # soft power, wake-on-lan capable
	foreach $f (keys %v2iii) {
		$DEBUG && warn $v2iii{$f}." rename to $f\n";
		rename ($v2iii{$f}, $f) if ((-d $v2iii{$f}) || (-e $v2iii{$f}));
	}
	
	if($v) {
		if ($voltage->{hideUI} || 
			!$voltage->{enabled} || 
			!$voltage->{monitor}) {
			$cce->set($am_oids[0], 'Voltage', {
				'hideUI' => 0,
				'enabled' => 1,
				'monitor' => 1 
				});
		}
	} 
} 
# else we ignore power features 

system('/etc/rc.d/init.d/lcdsleep stop >/dev/null 2>&1');
system('/etc/rc.d/init.d/lcdsleep start >/dev/null 2>&1');

$cce->bye('SUCCESS');
exit(0);
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
