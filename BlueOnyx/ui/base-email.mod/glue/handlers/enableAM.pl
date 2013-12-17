#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: enableAM.pl 1040 2007-12-16 04:43:14Z mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# 
#
use strict;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# retreive object data:
my $oid = $cce->event_oid();
my $ns = $cce->event_namespace();
my ($ok, $newobj, $oldobj) = $cce->get($oid, $ns);

if (!$oid || !$ns) {
  $cce->bye('FAIL', 'Bad oid or namespace');
  exit(1);
}

my %nsmap = (
	enableSMTP => "SMTP",
	enableImap => "IMAP",
	enablePop => "POP3",
	enableSMTPAuth => "SMTPAuth",
	enablepopRelay => "popRelay"
);
my @oids = $cce->find("ActiveMonitor");
my $prop;

foreach $prop (("enableSMTP", "enableImap", "enablePop", "enableSMTPAuth", "enablepopRelay")) {
	my ($oldval, $newval);
	$oldval = $oldobj->{$prop} ? 1 : 0;
	$newval = $newobj->{$prop} ? 1 : 0;

	# only set on a real change of boolean value, not just string value
	if (!($oldval eq $newval)) {
		$cce->set($oids[0], $nsmap{$prop}, {enabled => $newval});
	}
}

if ($newobj->{enableSMTP} || $newobj->{enableImap} || $newobj->{enablePop} || $newobj->{enableSMTPAuth} || $newobj->{enablepopRelay}) {
    $cce->set($oids[0], 'Email', { 'enabled' => 1 });
} else {
    $cce->set($oids[0], 'Email', { 'enabled' => 0 });
}

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
