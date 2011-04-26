#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: mailman_destroy,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# Mailman deconfigurator

my $DEBUG = 0;
$DEBUG && warn `date`;

use CCE;
use Sauce::Util;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
$DEBUG && warn "Event oid: $oid";
my $obj = $cce->event_old(); # the old values of the object 

if($cce->event_is_destroy()) {
  # delete aliases
  $DEBUG && warn "purging aliaess, event_is_destroy";
  Sauce::Util::editfile('/etc/mail/aliases.mailman',
    \&Sauce::Util::replace_unique_entries, $oid,
    {});
  Sauce::Util::modifyfile('/etc/mail/aliases.db');
  system('/bin/rm -f /etc/mail/aliases.mailman.backup.*');
  $DEBUG && warn "Running newaliases";
  system('/usr/bin/newaliases > /dev/null 2>&1');
}

if($obj->{name}) {
  my $name = $obj->{name};

  if($obj->{site}) # We're virtualized 
  {
    # Remove the list and the archives...
    my $remove = `/usr/lib/mailman/bin/rmlist -a $name`;
    $cf_name = $oid . "-" . "$name.cfg";
    Sauce::Util::unlinkfile("/var/lib/mailman/lists/$cf_name");
  }
  $DEBUG && warn "Deletion base dir: $parent\n";

}

$internal_list = $obj->{internal_name};
system("/usr/lib/mailman/bin/rmlist -a $internal_list");

$DEBUG && close(STDERR);

$cce->bye('SUCCESS');
exit(0);  
