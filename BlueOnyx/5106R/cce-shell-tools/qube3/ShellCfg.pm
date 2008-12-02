# $Id: ShellCfg.pm,v 1.1 2001/08/20 19:42:55 jeffb Exp $
# Copyright (c) 1999,2000,2001 Cobalt Networks, Inc. 
# Sun Microsystems, http://www.sun.com
# written by: Jeff Bilicki

package ShellCfg;

use vars qw($anyDel $userAdd $groupAdd $mailListadd);
$anyDel = { n	=> 'name' };
$userAdd = {
	n	=> 	'name',
	f	=>	'fullName',
	p	=> 	'password',
	r	=>	'description',
	q	=>	{ Disk	=> 'quota' },
	g	=>	{ Group => 'groups' },
	w	=>	{ Email => 'forwardEmail' },
	a	=>	{ Email => 'aliases' }
};
$groupAdd = {
	n	=>	'name',
	q	=>	{ Disk	=> 'quota' },
	r	=>	'description',
	u	=>	'members'
};
$mailListAdd = {
	n	=>	'name',
	u	=>	'local_recips',
	s	=>	'remote_recips',	
	r	=>	'description',
	m	=>	'moderator',
	p	=>	'apassword',	
	a	=>	{ Archive => 'keep_for_days' },
	l	=>	'maxlength',
	e	=>	'replyToList',
	b	=> 	'subPolicy',
	o	=>	'postPolicy',
	q	=>	'maxlength'
};

1;

sub mapOpts
{
	my $type = shift;
	my $oHash = shift;
	my $mapping;

	
	if ($type eq 'userAdd') { $mapping = $userAdd }		
	elsif ($type eq 'delete') { $mapping = $anyDel } 
	elsif ($type eq 'groupAdd') { $mapping = $groupAdd }
	elsif ($type eq 'mailListAdd') { $mapping = $mailListAdd }
	else { die "ShellCfg: Cannot map $type\n" }
	
	my $objRef = {};
	foreach my $o (keys %{ $oHash }) {
		unless($mapping->{$o}) {
			warn "Invalid option: -$o ", $mapping->{$o}, "\n";
			next;
		}
		my @arr = ();
		if(ref($mapping->{$o}) eq "HASH") {
			@arr = keys %{ $mapping->{$o} };
			$objRef->{$arr[0]}->{ $mapping->{$o}->{$arr[0]} } = $oHash->{$o};
		} else {
			$objRef->{ $mapping->{$o} } = $oHash->{$o};
		}
	}
	return($objRef);
}

sub stripRef
# let's get it on!
{
	my $ref = shift;
	my $newRef = {};
	foreach my $key (keys %{ $ref }) {
		next if(ref($ref->{$key}) eq 'HASH');
		$newRef->{$key} = $ref->{$key};
	} 
	return $newRef;	
}

sub convertByte
{
	my $num = shift;
	
	# write this later;
	return("51200");
}
	
1;
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
