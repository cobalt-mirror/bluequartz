#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/disk
#
# $Id: clear_quota.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# on destroy clear the user or group quota, because the filesystem remembers
# the quota if not reset for xfs.  This only really matters in a disaster
# recovery situation, but it doesn't hurt to do it all the time.
#
# This needs to run before the system account for the user or group gets
# removed, so we can look up the uid/gid with the account name.
#

use CCE;
use Disk;

my $cce = new CCE('Domain' => 'base-disk');
$cce->connectfd();

# this always runs on DESTROY, so always use the old object.
my $object = $cce->event_old();
my $oid = $cce->event_oid();

my $ok = Disk::setquota($cce, $object, $oid);
if (! $ok) {
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
