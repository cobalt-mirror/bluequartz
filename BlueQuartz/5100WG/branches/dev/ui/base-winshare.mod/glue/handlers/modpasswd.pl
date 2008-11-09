#!/usr/bin/perl -I/usr/sausalito/handlers/base/winshare -I/usr/sausalito/perl
#
# Copyright(c) 2000, Cobalt Networks, Inc.
# Author: asun@cobalt.com
#
# modify smb password file.
#
use Sauce::Util;
use CCE;
use smb;

my $cce = new CCE;
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $uid = getpwnam($obj->{name});

# edit the smb password file. we pass in the old user
# so that we can match existing entries.  
my $err = Sauce::Util::editfile(smb::smb_getpasswd, *smb::edit_smbpass, 
				$obj->{name}, $uid, $obj->{password}, 
				$obj->{fullName}, $obj->{shell}, 
				$old->{name});

if($err == -1) {

    open(PASS, "|/usr/bin/smbpasswd -s $obj->{name}");
    print PASS "$obj->{password}\n$obj->{password}";
    close PASS;
    $err=$?;
#    $err = system("echo $obj->{password}\n$obj->{password} | /usr/bin/smbpasswd $obj->{name}");
    $err = 0 if ($err > 2); # friendly smbpasswd errors tolerated
    $err=!$err; #evil hack
}

if ($err == 0) {
    $cce->bye('FAIL', '[[base-winshare.cantModifyPassword]]');
    exit 1;
}

# check if this is the admin user..., if so, update root's password
if ($obj->{systemAdministrator}) {
	my $err = Sauce::Util::editfile(smb::smb_getpasswd, *smb::edit_smbpass, 
				'root', 0, $obj->{password}, 
				$obj->{fullName}, $obj->{shell}, 
				'root');
	system('/usr/bin/smbpasswd', 'root', $obj->{password}) if ($err == -1);
}

# restart smb if it's running.
Sauce::Service::service_run_init('smb', 'restart') 
	if Sauce::Service::service_get_init('smb');

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
