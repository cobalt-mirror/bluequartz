#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#$Id: deluser.pl 3 2003-07-17 15:19:15Z will $
# 
# User._DESTROY handler
# author: Jonathan Mayer <jmayer@cobalt.com>

use strict;
use Sauce::Config;
use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

# retreive user object data:
my $oid = $cce->event_oid();

my $old = $cce->event_old();

# verify that we're really deleting a user
if (!$oid ) {
	$cce->warn('[[base-user.Invalid-object]]');
	$cce->bye("FAIL");
}

# verify that the user exists
if (!getpwnam($old->{name})) {
	$cce->warn("[[base-user.Already-Destroyed,name=".$old->{name}."]]");
	# already destroyed?
	$cce->bye("SUCCESS");
}

# delete this user's EmailAlias object
Sauce::Util::editfile( '/etc/mail/aliases',
	\&Sauce::Util::replace_unique_entries,
	$oid, {} );

# clean the smbpasswd file
# FIXME

# clean the netatalk passwd file
# FIXME

# clean the htpasswd file
# FIXME

# clean the alias list and vacation stuff
# FIXME

# kill all of this user's currently running processes:
my @pids;
chomp (@pids = `/bin/ps --user $old->{name} -ho pid`);
if (@pids) {
	kill 1, @pids;
	sleep(1);
	my $cnt = 0;
	while (chomp(@pids = `/bin/ps --user $old->{name} -ho pid`)) {
		$cnt++;
		if ($cnt > 5) {
			print STDERR "$0: Couldn't kill processes of $old->{name}: @pids\n";
			last;
		}
		kill 9, @pids;
		sleep(1);
	}
}

# clean the password file
system("/usr/sbin/userdel", "-r", $old->{name});

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
