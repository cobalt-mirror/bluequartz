#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# $Id: subdomain-vsite-delete.pl, v2.0 2008/12/30 13:14:00 Exp $

use CCE;

$cce = new CCE;
$cce->connectfd();

my $old = $cce->event_old();

@oids = $cce->find('Subdomains', { 'group' => $old->{'name'} });
foreach $oid (@oids) {
  $cce->destroy($oid); 
}

$cce->bye('SUCCESS');
exit(0);

