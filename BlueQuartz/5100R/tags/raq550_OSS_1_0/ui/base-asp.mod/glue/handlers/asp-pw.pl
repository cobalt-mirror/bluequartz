#!/usr/bin/perl -w
# $Id: asp-pw.pl,v 1.7.2.1 2002/05/13 18:34:39 will Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# unified create/modify script for users.
#
# author: Jonathan Mayer <jmayer@cobalt.com>

use strict;
use lib qw( /usr/sausalito/perl );
use CCE;
umask(022);

my $DEBUG = 0;

# cASP (Chili!Soft)
my $ASP_initdir      = "/etc/rc.d/init.d";
my $ASP_servicename  = "asp-apache-3000";
my $ASP_adminname    = "asp-admin-5100";
my $ASP_home         = '/home/chiliasp';
my $ASP_vhostctrl    = "$ASP_home/INSTALL/vhostctl";
my $ASP_appconf      = '.aspconf';
my $ASP_pwfile       = "$ASP_home/admin/conf/service.pwd";
my $ASP_htpasswd     = "/usr/bin/htpasswd";
my $ASP_perms        = '0400';
my $Adminuser        = 'admin';

# system("$ASP_htpasswd -b $ASP_pwfile $Adminuser $newpasswd > /dev/null 2>&1; /bin/chmod 400 $ASP_pwfile > /dev/null 2>&1") if (-x $ASP_htpasswd);

# connect:
my $cce = new CCE;
$cce->connectfd();

# retrieve info
my $oid = $cce->event_oid();
my $old = $cce->event_old(); # old values
my $new = $cce->event_new(); # recently changed values only
my $obj = $cce->event_object(); # composite "new" object.

# verify this is $Adminuser
# perhaps this check can be done with UI permissions?
if ($old->{name} =~ /^$Adminuser$/) {
	my $newpasswd = $new->{password};
	$newpasswd =~ s/([^\\])([\n\;])/$1\\$2/g;
	
	if ((-x $ASP_htpasswd) && $newpasswd) {
		Sauce::Util::modifyfile($ASP_pwfile);
		system("$ASP_htpasswd -b $ASP_pwfile $old->{name} \"$newpasswd\" > /dev/null 2>&1");
		Sauce::Util::chmodfile($ASP_perms, $ASP_pwfile);
	}
}

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
