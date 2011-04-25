#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/mailman
# $Id: listmod_members,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# listmod_members depends on:
#		name
#		local_recips
#		remote_recips
#		_CREATE

use MailMan; # should be a local file
use CCE;
my $cce = new CCE;
$cce->connectfd();

my $DEBUG = 0;
$DEBUG && warn `date`;

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

# hack to make sure all subscriber email addresses include @fqdn, so 
# it is harder to spoof yourself.  This may or may not break the qube, but
# oh well, the qube suffers from this problem as well.
MailMan::munge_members($cce, $obj);

# create subscriber list:
MailMan::rewrite_members($obj);

$cce->bye('SUCCESS');
exit(0);

