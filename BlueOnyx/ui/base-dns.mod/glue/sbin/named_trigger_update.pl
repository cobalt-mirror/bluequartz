#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: named_trigger_update.pl,v Di 02 Apr 2013 00:37:48 CEST mstauber Exp $
# Copyright 2013 Team BlueOnyx, All rights reserved.
# 
# Forces an update of the /etc/named.conf configuration.

use CCE;
my $cce = new CCE;
$cce->connectuds();

my @sysoids = $cce->find('System');
die "Could not find System object in CCE, named initialization failed.\n"
	unless ($sysoids[0] =~ /^\d+$/);

my($ok) = $cce->set($sysoids[0], 'DNS', { 'commit' => time(), 'dirty' => "1" });


die "Could not enable DNS service from CCE, named initialization failed.\n"
	unless ($sysoids[0] =~ /^\d+$/);

