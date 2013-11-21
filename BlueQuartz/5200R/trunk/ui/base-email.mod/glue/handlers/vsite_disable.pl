#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks, Inc.  All rights reserved.
# $Id: vsite_disable.pl 1495 2010-06-25 09:15:33Z shibuya $
#
# use Sauce::Util::editfile by Hisao

use strict;
use CCE;
use Email;
use Sauce::Service;
use Sauce::Util;

sub nonDuplicatedArray {
  my @Duplicated=@_;
  my %seen=();
  my (@NonDuplicatedArray,@Unique);
  @Unique = grep {! $seen{$_}++} @Duplicated;
  @NonDuplicatedArray = sort(@Unique);
  return @NonDuplicatedArray;
}

my $Access = $Email::ACCESS;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();

my ($ok, $domain) = $cce->get($oid);

my $virtualsite = $domain->{'fqdn'};
my @server_aliases = $cce->scalar_to_array($domain->{'mailAliases'});
push(@server_aliases, $virtualsite);
my $access_list;
my @emailList;
if ( $domain->{'emailDisabled'} eq "1" ) {
  foreach my $server(@server_aliases) {
    if ($server) {
      push(@emailList, $server);
    }
  }
}

@emailList = nonDuplicatedArray(@emailList);
foreach my $entry(@emailList) {
  $access_list .= $entry . "\t\t550 User unknown\n";
}

if (!Sauce::Util::replaceblock($Access,
    "### Start Block Email for Virtual Site: $virtualsite ###", $access_list,
    "### END Block Email for Virtual Site: $virtualsite ###")) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => $Access });
    $cce->bye('FAIL');
    exit(1);
}
system("/usr/sbin/postalias hash:$Access > /dev/null 2>&1");

$cce->bye("SUCCESS");
exit(0);

