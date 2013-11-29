#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: suspend_user.pl,v 1.1.2.2 Tue 23 Jun 2009 03:15:33 PM EDT mstauber Exp $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# Copyright 2008-2009 Team BlueOnyx. All rights reserved.
#
# Watch the ui_enabled property and toggle the enabled property provided
# that the Vsite (if any) the User belongs to is not suspended.
#

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $user = $cce->event_object();

my $enabled = $user->{ui_enabled};
if ($user->{site} ne '') {
	# see if the site this user is a member of is suspended
	my @sites = $cce->find('Vsite',
			{ 'name' => $user->{site}, 'suspend' => 1 });
	if (scalar(@sites) != 0) {
		# site is suspended.  don't allow user to be enabled.
		$enabled = 0;
	}
}

my ($ok) = $cce->set($cce->event_oid(), '', { 'enabled' => $enabled });
if (!$ok) {
	$cce->bye('FAIL');
	exit(1);
}
else {
    # User accounts MUST be locked or unlocked. Otherwise suspended users can still use SMTP-Auth:
    if ($enabled == "0") {
	# Lock account:
	system("/usr/sbin/usermod -L $user->{name}");
    }
    else {
	# Unlock account:
	system("/usr/sbin/usermod -U $user->{name}");
    }
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
