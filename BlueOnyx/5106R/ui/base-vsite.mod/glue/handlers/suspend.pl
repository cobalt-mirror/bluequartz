#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Copyright 2008-2009 Team BlueOnyx. All rights reserved.
# $Id: suspend.pl,v 1.3.2.2 Tue Jun 23 12:44:35 2009 mstauber Exp $
#
# Handle most of the site related stuff that needs to happen when a site 
# is suspended.

use CCE;

my $cce = new CCE;
$cce->connectfd();

# things to do on site suspend
# 1. (maybe) disable mailing lists or maybe just maillist server.
# 2. disable VirtualHost
# 3. suspend all site users

my $vsite = $cce->event_object();

# deal with mailing lists when they actually somewhat work

# disable my VirtualHost
my ($vhost) = $cce->findx('VirtualHost', { 'name' => $vsite->{name} });
my ($ok) = $cce->set($vhost, '', 
        { 'enabled' => ($vsite->{suspend} ? 0 : 1) });

if (not $ok) {
    $cce->bye('FAIL', '[[base-vsite.cantDisableVhost]]');
    exit(1);
}

# suspend/unsuspend all site users
my @users = ();
if ($vsite->{suspend}) {
	#
	# site being suspended, so find all site members that are currently
	# enabled
	#
	@users = $cce->findx('User',
			{ 'site' => $vsite->{name}, 'enabled' => 1 });
} 
else {
	#
	# site being unsuspended, so find all site members who
	# should be reenabled
	#
	@users = $cce->findx('User',
			{ 'site' => $vsite->{name}, 'ui_enabled' => 1 });
}

for my $user (@users) {
    ($ok) = $cce->set($user, '', { 'enabled' => ($vsite->{suspend} ? 0 : 1) });

    #
    ### User accounts MUST be locked or unlocked. Otherwise suspended users can still use SMTP-Auth:
    #

    # Get the username:
    ($ok, my $myuser) = $cce->get($user);

    if ($vsite->{suspend} == "0") {
        # Unlock account:
        system("/usr/sbin/usermod -U $myuser->{name}");
        }
    else {
        # Lock account:
        system("/usr/sbin/usermod -L $myuser->{name}");
    }

    if (not $ok) {
        $cce->bye('FAIL', '[[base-vsite.cantSuspendUsers]]');
        exit(1);
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
