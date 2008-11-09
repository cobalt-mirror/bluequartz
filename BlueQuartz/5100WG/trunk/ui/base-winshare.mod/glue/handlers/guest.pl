#!/usr/bin/perl -I. -I/usr/sausalito/perl
#
# Copyright(c) 2000, Cobalt Networks, Inc.
# Author: asun@cobalt.com
#
# modify global and guest-share for guest access.
#
use Sauce::Util;
use Sauce::Service;
use Sauce::Config;
use CCE;
use smb;

my $cce = new CCE(Namespace => 'FileShare');
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();

my (%settings,$err);

if (($old->{guestEnabled} ne $obj->{guestEnabled}) or
    ($old->{guestWorkGroup} ne $obj->{guestWorkGroup})) { 
    $err = Sauce::Util::editfile(smb::smb_getconf, *smb::edit_guest,
				$old->{guestWorkGroup}, $obj->{guestWorkGroup},
				$obj->{guestEnabled});
    if (!$err) {
	$cce->bye('FAIL', '[[base-winshare.cantToggleSetting]]');
	exit 1;
    }
}

if ($old->{guestUser} ne $obj->{guestUser}) {
    $settings{'guest account'} = $obj->{guestUser};
    $err = Sauce::Util::editfile(smb::smb_getconf, *smb::edit_global,
				    %settings);
    if ($err) {
	$cce->bye('FAIL', '[[base-winshare.cantConfigFile]]');
	exit 1;
    }
}

# now, restart samba
my ($oid) = $cce->find('System');
my $ok;
($ok, $obj) = $cce->get($oid, 'WinShare');
Sauce::Service::service_toggle_init('smb', $obj->{enabled});
$cce->bye('SUCCESS');
exit 0;
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
