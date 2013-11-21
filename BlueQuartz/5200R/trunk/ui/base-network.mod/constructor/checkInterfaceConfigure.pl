#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id$
# Copyright 2010 Project BlueQuartz.  All rights reserved.

use strict;
use CCE;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectuds();

my $configure = 1;

if (open(SYSCFG, '/etc/sysconfig/bluequartz')) {
  while(<SYSCFG>) {
    if (/^INTERFACE_CONFIGURE=(\w+)$/i) {
      if ($1 =~ /true/i) {
        $configure = 1;
      }
    }
  }
}

my @oids = $cce->find('System');
my $sys_oid = $oids[0];
my ($ok) = $cce->set($sys_oid, 'Network', { 'interfaceConfigure' => $configure });

@oids = $cce->find('IPPoolingRange');
if ($#oids < 0 && $configure == 0) {
  my @net_oids = $cce->find('Network', {'real' => 1});
  foreach my $oid (@net_oids) {
    my ($ok, $obj) = $cce->get($oid);
    my $ipaddr = $obj->{'ipaddr'};
    $ok = $cce->create('IPPoolingRange', {'min' => $ipaddr, 'max' => $ipaddr});
  }
  my ($ok) = $cce->set($sys_oid, 'Network', {'pooling' => 1});
}

$cce->bye('SUCCESS');
exit(0);

