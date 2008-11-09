#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: addUser.pl 3 2003-07-17 15:19:15Z will $
#
# Enable pptp uiRights for newly created users if "all" users have
# pptp rights as set in the Administration->Security->PPTP page

use strict;
use CCE;

my $cce = new CCE();
$cce->connectfd();

# this handler should only be called when a user is created
my $oid = $cce->event_oid();

my $sysoid = ($cce->find('System'))[0];
my ($ok, $sys_pptp) = $cce->get($sysoid, 'Pptp');

if(($sys_pptp->{allowType} eq 'all') && ($sys_pptp->{enabled}))
{
	my($aok, $user_obj) = $cce->get($oid);
	$user_obj->{uiRights} ||= '&';
	$user_obj->{uiRights} .= 'enableRemoteAccess&';
	
	$ok = $cce->set($oid, '', {'uiRights' => $user_obj->{uiRights}});
}

$cce->bye("SUCCESS");
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
