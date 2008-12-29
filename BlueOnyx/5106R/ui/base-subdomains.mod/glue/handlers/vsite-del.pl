#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks.  All rights reserved.
# $Id: vsite-del.pl,v 1.0 2006/01/26 10:41:00 Exp $

use CCE;
use Sauce::Util;
use Sauce::Service;

$cce = new CCE;
$cce->connectfd();

$old = $cce->event_old();

$subdomain_config_dir = "/etc/httpd/conf/vhosts/subdomains";
$subdomain_config_file = $subdomain_config_dir . "/" . $old->{'group'} . "." . $old->{'hostname'} . ".conf";

system("/bin/rm -f $subdomain_config_file");

$cce->bye('SUCCESS');
exit(0);
