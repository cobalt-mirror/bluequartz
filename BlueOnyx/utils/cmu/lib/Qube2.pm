# $Id: Qube2.pm 922 2003-07-17 15:22:40Z will $ 
# Copyright (c) 2000 Cobalt Networks, Inc.
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

package Qube2;

######################
# migrateGetGroups() #
#
sub migrateGetGroups
{
	require Cobalt::Group;
	import Cobalt::Group qw(group_list);

	my @groups = Cobalt::Group::group_list();
	return @groups;
}

#####################
# migrateGetUsers() #
#
sub migrateGetUsers
{
	require Cobalt::User;	
	import Cobalt::User qw(user_list);

	my @users;
	my @lusers = Cobalt::User::user_list();
	while( my $u = pop(@lusers) ) {
		next if($u eq "admin");
		push @users, $u;
	}
	return @users;
}

#########################
# migrateGetMailLists() #
#
sub migrateGetMailLists
{
	require Cobalt::List;
	import Cobalt::List qw(list_lists);
	my @lists = Cobalt::List::list_lists();
	
	return @lists;
}

#####################
# migrateRetrieve() #
# syntax:	$epoch = Qube2migrate::migrateRetrieve();
#
sub migrateRetrieve
{
	my $self = shift;

	$self->{mgrEpoch} =  time();	
	$self->{mgrVersion} = $self->version;
 	return;		
}

###################
# groupRetrieve() #
#
sub groupRetrieve 
{
	use Cobalt::Group;

	my $groupName = shift;
	return unless($groupName);

	my $ret = {};
      
	my $DB_dir = "/var/cobalt/uidb";

	my $quota = (group_get_quota($groupName))[1];
	$quota*=1024/1048576;
	$ret->{quota} = $quota;

	my %UIDB;
	tie(%UIDB,'DB_File',$DB_dir,O_RDWR|O_CREAT,0600) 
		|| die "Cannot open database: $!";
	untie(%UIDB);

	my @groupUsers = Cobalt::Group::group_show($groupName);
	@{ $ret->{members}->{member} } = @groupUsers if(scalar @groupUsers);

	return $ret;
}

######################
# mailListRetrieve() #
#
sub mailListRetrieve
{
	require Cobalt::List;

	my $list = shift;

	my $ret = {};
        my @members = Cobalt::List::list_get_mem($list);
	my (@intlist,@extlist);
	foreach my $mem (@members) {
		if ($mem =~ /\@/) {
			push @extlist, $mem;
		} else {
			push @intlist, $mem;
		}
	}
	if(getgrnam($list)) { $ret->{group} = $list }
	@{ $ret->{local_recips}->{recip} } = @intlist if(scalar @intlist);
	@{ $ret->{remote_recips}->{recip} } = @extlist if(scalar @extlist);
	return $ret;
}

#################
# userRetrieve() #
#
sub userRetrieve 
{
	use Cobalt::User;
	use Cobalt::List;
	use Cobalt::Vacation;
	use Cobalt::Email;

	my $userName = shift;
	return unless ($userName);
	
	my $ret = {};
	
	my ($fullName, $quota, $shell) = (user_show($userName,0))[1,2,4];

	$ret->{fullName} = $fullName;

	#warn "fullname: $fullName , quota: $quota , shell: $shell\n";

	$quota*=1024/1048576;
	$ret->{quota} = $quota;

	if (my @for = alias_get_vacationless($userName)) {
		$ret->{forwardEnable} = 'true';
		@{ $ret->{forwardEmails}->{forward} } = @for;
	}

	my $vac = vacation_get_on($userName);
	$ret->{vacationOn} = "true" if ($vac =~ /^(t|true|yes|on|1)$/oi);
	
	BEGIN { $SIG{'__WARN__'} = sub { warn $_[0] if $DOWARN} }
	
	$DOWARN = 0;
	my $vacMsg = vacation_get_message($userName);
	$DOWARN = 1;
     
	$vacMsg =~ s/\n/ /g;
	$ret->{vacationMsg} = $vacMsg;
	
	my @alii = alias_get_bytarget($userName);
	if(scalar @alii) { @{ $ret->{aliases}->{alias} } = @alii }
	# get the password
	my $pwFile = "/etc/passwd";
	my $line = `grep ^$userName: $pwFile`;
	if($line) {
		my $passwd = (split(":",$line))[1];	
		$ret->{crypt_password} = $passwd;
	}	
	$line = `grep ^$userName: /etc/smbpasswd`;
	if($line) {
		my @passwds = (split(":",$line))[2,3];
		$ret->{smb_password} = join(":",@passwds);
	}
	return $ret;
}

1;

__END__


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
