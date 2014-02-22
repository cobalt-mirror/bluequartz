#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# $Id: subdomain-user.pl, v2.0 2008/12/30 11:15:00 Exp $

use CCE;
use Sauce::Util;
use Sauce::Service;
use Base::HomeDir qw(homedir_get_user_dir);

umask(002);

$cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
$obj = $cce->event_object();

($ok, $user) = $cce->get($oid);
($ok, $subdomains) = $cce->get($oid, 'subdomains');

$alt_root = $user->{volume};
$name = $user->{name};
$site = $user->{site};

$home_dir = homedir_get_user_dir($name, $site, $alt_root);

if ( $subdomains->{'enabled'} eq "1" ) {

  ($ok, $error) = $cce->create('Subdomains', {
    'webpath' => $home_dir . "/web/",
    'hostname' => $user->{'name'},
    'group' => $user->{'site'},
    'isUser' => '1'
  });

  if (not $ok) {
    $cce->warn('[[base-subdomains.duplicateEntry]]');
    $cce->bye('FAIL');
    exit(1);
  }
} else {
  @oids = $cce->find('Subdomains', { 'group' => $user->{'site'},  'hostname' => $user->{'name'}, 'isUser' => '1'});
  if ( $oids[0] ) {
    $cce->destroy($oids[0]); 
  }
}

service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

