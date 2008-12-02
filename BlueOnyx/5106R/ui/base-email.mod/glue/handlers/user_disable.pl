#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks, Inc.  All rights reserved.
# $Id: user_disable.pl 1037 2007-12-15 01:24:48Z brian $
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

my ($ok, $user) = $cce->get($oid);
my ($ok, $email) = $cce->get($oid, "Email");

my $group = $user->{'site'};
my $username = $user->{'name'};

my @oid = $cce->find('Vsite', { 'name' => $group });
my ($ok, $domain) = $cce->get($oid[0]);

my $virtualsite = $domain->{'fqdn'};

my @user_aliases =  $cce->scalar_to_array($email->{'aliases'});
push(@user_aliases, $username);

my @server_aliases = $cce->scalar_to_array($domain->{'mailAliases'});

push(@server_aliases, $virtualsite);
my $access_list;
my @emailList;
if ( $user->{'emailDisabled'} eq "1" ) {
  foreach my $server(@server_aliases) {
    foreach my $user_name(@user_aliases) {
      if ($user_name) {
        push(@emailList, $user_name . '@' . $server);
      }
    }
  }
}

@emailList = nonDuplicatedArray(@emailList);
foreach my $entry(@emailList) {
  $access_list .= $entry . "\t\tERROR:5.1.1:550 User unknown\n";
}

if (!Sauce::Util::replaceblock($Access,
    "### Start Block Email for User: $username on Virtual Site: $virtualsite ###", $access_list,
    "### END Block Email for User: $username on Virtual Site: $virtualsite ###")) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => $Access });
    $cce->bye('FAIL');
    exit(1);
}

$cce->bye("SUCCESS");
exit(0);

