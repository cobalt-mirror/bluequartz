#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: listmod_config,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# depends on:
#		name
#		moderator
#     	      	postPolicy subPolicy
#		_CREATE

use MailMan; # should be a local file
use CCE;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

# hack to make spoofing a membership to a private list harder
# make sure the moderator email is of the form user@fqdn
MailMan::munge_members($cce, $obj);

# create subscriber list:
MailMan::rewrite_config($cce, $obj);

$cce->bye('SUCCESS');
exit(0);

