#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# $Id: subdomain-user-delete.pl, v2.0 2008/12/30 13:14:00 Exp $

use CCE;

$cce = new CCE;
$cce->connectfd();

my $old = $cce->event_old();

@oids = $cce->find('Subdomains', { 'group' => $old->{'site'},  'hostname' => $old->{'name'}, 'isUser' => '1'});
if ( $oids[0] ) {
  $cce->destroy($oids[0]); 
}

$cce->bye('SUCCESS');
exit(0);
