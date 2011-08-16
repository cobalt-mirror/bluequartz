#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# $Id: subdomain-delete.pl, v2.0 2008/12/30 13:16:00 Exp $

use CCE;
use Sauce::Service;

$cce = new CCE;
$cce->connectfd();

$old = $cce->event_old();

$subdomain_config_dir = "/etc/httpd/conf.d/subdomains";
$subdomain_config_file = $subdomain_config_dir . "/" . $old->{'group'} . "." . $old->{'hostname'} . ".conf";

system("/bin/rm -f $subdomain_config_file");

service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);
