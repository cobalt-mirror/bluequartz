#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: enableAM.pl,v 1.1.1.2 2006/08/09 06:28:37 bsmith Exp $
# Copyright 2006 NuOnce Networks, Inc.  All rights reserved.
# 
#
use strict;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# retreive object data:
my $oid = $cce->event_oid();
my $ns = $cce->event_namespace();
my $prop = $cce->event_property();
my ($ok, $newobj) = $cce->get($oid, $ns);

if (!$oid || !$ns ||!$prop) {
  $cce->bye('FAIL', 'Bad oid or namespace');
  exit(1);
}

my $newval = $newobj->{enabled};

my @oids = $cce->find("ActiveMonitor");
if($oids[0] =~ /^\d+$/) {
	$cce->set($oids[0], "mysql", {enabled => $newval});
}

$cce->bye('SUCCESS');
exit(0);
