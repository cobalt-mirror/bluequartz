#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks, Inc.  All rights reserved.
# $Id: vsite_disable.pl 834 2006-08-03 13:59:23Z shibuya $
#
# use Sauce::Util::editfile by Hisao

use strict;
use CCE;
use Email;
use Sauce::Service;
use Sauce::Util;

my $Access = $Email::ACCESS;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();

my ($ok, $domain) = $cce->get($oid);

my $virtualsite = $domain->{'fqdn'};
my @server_aliases = $cce->scalar_to_array($domain->{'mailAliases'});
push(@server_aliases, $virtualsite);
my $access_list;
if ( $domain->{'emailDisabled'} eq "1" ) {
    foreach my $server(@server_aliases) {
        if ($server) {
            $access_list .= $server . "\t\tERROR:5.1.1:550 User unknown\n";
        }
    }
}

if (!Sauce::Util::replaceblock($Access,
    "### Start Block Email for Virtual Site: $virtualsite ###", $access_list,
    "### END Block Email for Virtual Site: $virtualsite ###")) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => $Access });
    $cce->bye('FAIL');
    exit(1);
}

$cce->bye("SUCCESS");
exit(0);

