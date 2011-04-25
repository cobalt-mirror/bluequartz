#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: grouplist.pl,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# ensure that a mailing list exists for each group.

use lib qw( /usr/sausalito/perl );
use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

my $oldname = $old->{name} || $new->{name};
my ($mloid) = $cce->find("MailMan", { 'group' => $oldname });

if ($mloid && $cce->event_is_destroy()) {
  $cce->set($mloid, "", { 'group' => '' }); # clear group association
  $cce->destroy($mloid); # destroy list
  $cce->bye("SUCCESS");
  exit(0);
}

my $listname = $obj->{name};
#if ($listname eq 'home') { $listname = 'all'; }

if ($mloid && $new->{name}) {
  $cce->set($mloid, "", { 
    'group' => $obj->{name},
    'name' => $listname,
  });
}

if (!$mloid) {
  $cce->create("MailMan", {
    'name' => $obj->{name},
    'group' => $listname,
    'enabled' => '1',
    'update' => '1' });
}

$cce->bye("SUCCESS");
exit(0);

