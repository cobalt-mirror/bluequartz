#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: deluser.pl,v 1.10.2.1 2002/02/08 00:04:46 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# 
# User._DESTROY handler
# author: Jonathan Mayer <jmayer@cobalt.com>

use Sauce::Config;
use Sauce::Util;
use CCE;
use Base::User qw(user_kill_processes userdel);
use Base::HomeDir qw(homedir_create_user_link);

my $cce = new CCE;
$cce->connectfd();

# retreive user object data:
my $oid = $cce->event_oid();

my $old = $cce->event_old();

# verify that we're really deleting a user
if (!$oid ) {
	$cce->warn('[[base-user.Invalid-object]]');
	$cce->bye("FAIL");
}

# verify that the user exists
my @user_info = getpwnam($old->{name});
if (!@user_info) {
	$cce->warn("[[base-user.Already-Destroyed,name=".$old->{name}."]]");
	# already destroyed?
	$cce->bye("SUCCESS");
    exit(0);
}

# clean the smbpasswd file
# FIXME

# clean the netatalk passwd file
# FIXME

# clean the htpasswd file
# FIXME

# clean the alias list and vacation stuff
# FIXME

# kill all of this user's currently running processes:
user_kill_processes($old->{name});

# get rid of friendly symlink
my ($user_link, $link_target) = homedir_create_user_link($old->{name}, 
                                    $old->{site}, $old->{volume});
unlink($user_link);
Sauce::Util::addrollbackcommand("umask 000; /bin/ln -sf \"$link_target\" \"$user_link\"");

# clean the password file
if (!(userdel(1, $old->{name}))[0])
{
    $cce->bye('FAIL', '[[base-user.cantDeleteUser]]');
    exit(1);
}

# update workgroups
update_workgroups();

sub update_workgroups
{
	my @oids = $cce->find("Workgroup", { 'members' => $old->{name} });
	foreach my $oid (@oids) {
		my ($ok, $obj) = $cce->get($oid);
		my (@members) = $cce->scalar_to_array($obj->{members});
		@members = grep {$_ ne $old->{name}} @members;
		$cce->set($oid, "", { 'members' =>
			$cce->array_to_scalar(@members) } );
	}
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
