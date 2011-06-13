#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: handle_user.pl,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# handles the destruction of a user, or the user change-name event.

use strict;
use lib qw( /usr/sausalito/perl );
use CCE;

my $cce = new CCE; $cce->connectfd();

my $old = $cce->event_old();
my $new = $cce->event_new();

my @oids = $cce->find("MailList", { 'local_recips' => $old->{name} });

foreach my $oid (@oids) {
  my ($ok, $obj) = $cce->get($oid);
  my (@members) = $cce->scalar_to_array($obj->{local_recips});
  @members = grep { $_ ne $old->{name} } @members;
  if (defined($new->{name})) { push(@members, $new->{name}); }
  $cce->set($oid, "", { 
    'local_recips' => $cce->array_to_scalar(@members) } );
}

$cce->bye("SUCCESS");
exit(0);
