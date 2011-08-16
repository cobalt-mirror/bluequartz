# $Id: QubeConflict.pm 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

package QubeConflict;
use strict;

use Conflict;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA	= qw(Conflict);	
@EXPORT = qw($EX $IM);
@EXPORT_OK = qw();
	
require Archive;

1;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
    
	my $expt = shift || die "You must provide an export tree\n";
	my $impt = shift || die "You must provide an import tree\n";
	my $glbConf = shift || die "You must provide config info\n";

	my $self = Conflict->new($expt, $impt, $glbConf);
	bless ($self, $class);
	return $self;
}

sub getGroups {
	my $self = shift; my $scope = shift;
	if(defined $self->{$scope}->{group}) { return $self->{$scope}->{group} }
	else { return \%_ }
}
sub getGroup { return($_[0]->{$_[1]}->{group}->{$_[2]}) }
sub getGroupAttr { 
	return($_[0]->{$_[1]}->{group}->{$_[2]}->{$_[3]}) 
}

sub checkGroup { return($_[0]->check($_[1], 'group', $_[2])) }

sub checkAll 
{
	my $self = shift;
	my $scope = shift;
    my $value = shift;

    return ($self->{$scope}->{group}->{$value} ||
	    $self->{$scope}->{user}->{$value} ||
	    $self->{$scope}->{list}->{$value} ||
	    $self->{$scope}->{aliases}->{$value}
    );
}

sub raiseAlii 
{
    my $self = shift;
	my $scope = shift || $EX;
    my $aliasRef = {};
	my @keys;

	if(defined $self->{$scope}->{user}) {
	my @keys = keys %{ $self->getUsers($scope) };
    foreach my $user (@keys) {
		next unless(defined $self->{$scope}->{user}->{$user}->{Email}->{aliases});
		my $alii = $self->{$scope}->{user}->{$user}->{Email}->{aliases}->{alias};
        if (ref($alii) eq 'ARRAY') {
            foreach my $alias (@{ $alii }){ $aliasRef->{$alias} = $user; }
        }
        elsif ($alii) { $aliasRef->{$alii} = $user; }
    }
	}
    return $aliasRef;
}


sub detectUserName
# used to detect user conflicts
# arguments: user name
# returns: reslove object if one found
{
	my $self = shift;
	my $user = shift;
	my $scope = shift || $EX;

	my $ret = $self->checkUser($scope, $user);
	if($ret) {
		my $reslv = Resolve->new(%{ $self->getConflict('userName') });
		$reslv->text("User $user already exists\n");
		# merge flag checking still needs to be put in Qube3scanin.pl
		#$reslv->{actions}->{Merge} = "mergeClass";
		$reslv->key($user);
		return($reslv);
	}
	$ret = $self->checkAll($scope, $user);
	if($ret) {
		my $reslv = Resolve->new(%{ $self->getConflict('userName') });
		$reslv->text("User name $user is already in use\n");
		$reslv->key($user);
		return($reslv);
	} else { return 1 }
}

sub detectUserNameNumber
{
	my $self = shift;
	my $user = shift;
	my $scope = shift || $EX;

	if($user =~ /^\d/) {
		my $reslv = Resolve->new(%{ $self->getConflict('userNameNumber') });
		$reslv->text("User names cannot begin with a number: $user\n");
		$reslv->key($user);
		return($reslv);
	} else { return 1 }
}

sub detectUserNameLength
{
	my $self = shift;
	my $user = shift;
	my $scope = shift || $EX;

	if(length($user) > 12) {
		my $reslv = Resolve->new(%{ $self->getConflict('userNameLength') });
		$reslv->text("User names cannot be longer than 12 characters: $user\n");
		$reslv->key($user);
		return($reslv);
	} else { return 1 }
}

sub detectGroupName
# used to detect group conflicts
# arguments: group name
# returns: reslove object if one found
{
	my $self = shift;
	my $group = shift;
	my $scope = shift || $EX;

	my $ret = $self->checkGroup($scope, $group);
	if($ret) {
		my $reslv = Resolve->new(%{ $self->getConflict('groupName') });
		$reslv->text("Group $group already exists\n");
		$reslv->{actions}->{Merge} = "mergeClass";
		$reslv->key($group);
		return($reslv);
	}
	$ret = $self->checkAll($scope, $group);
	if($ret) {
		my $reslv = Resolve->new(%{ $self->getConflict('groupName') });
		$reslv->text("Group name $group is already in use\n");
		$reslv->key($group);
		return($reslv);
	} else { return 1 }
}

sub detectGroupNameNumber
{
	my $self = shift;
	my $group = shift;
	my $scope = shift || $EX;

	if($group =~ /^\d/) {
		my $reslv = Resolve->new(%{ $self->getConflict('groupNameNumber') });
		$reslv->text("Group names cannot begin with a number: $group\n");
		$reslv->key($group);
		return($reslv);
	} else { return 1 }
}


sub detectMailList
{
	my $self = shift;
	my $list = shift;
	my $scope = shift || $EX;

	my $ret = $self->checkAll($scope, $list);
	if($ret) {
		my $reslv = Resolve->new(%{ $self->getConflict('listName') });
		$reslv->text("Mailing list name $list is already in use\n");
		$reslv->key($list);
		return($reslv);
	} else { return 1 }
}

sub detectMailListNumber
{
	my $self = shift;
	my $list = shift;
	my $scope = shift || $EX;

	if($list =~ /^\d/) {
		my $reslv = Resolve->new(%{ $self->getConflict('listNameNumber') });
		$reslv->text("Mailing list names cannot begin with a number: $list\n");
		$reslv->key($list);
		return($reslv);
	} else { return 1 }
}

sub detectAliases
{
	my $self = shift;
	my $scope = shift || $EX;
	my $alii = shift;

	my $ret = $self->checkAll($scope, $alii);
	if($ret) {
		my $reslv = Resolve->new(%{ $self->getConflict('userAlias') });
		$reslv->text("Alias name $alii is already in use\n");
		$reslv->key($alii);
		return($reslv);
	} else { return 1 }
}

sub remapUser
{
	my $self = shift;
	my $old = shift;
	my $new = shift; 
	my (@keys, $arch);

	if($self->getConfig('confOnly') eq 'f') {
		$arch = Archive->new(type => 'users',
			destDir => $self->getConfig('destDir'),
			sessID => $self->{sessID},
			archives => $self->getUserAttr($IM, $old, 'archives')
		);	
		$arch->xmlAttrConvert($old, $new, 'uid');
	}
	# remap any group memberships
	if(defined $self->{$IM}->{group} ) {
	@keys = keys %{ $self->getGroups($IM) };
	foreach my $grp (@keys) {
		my $mem = $self->{$IM}->{group}->{$grp}->{members};
		my $newMem = TreeXml::remapItem($old, $new, $mem);
		next if(!$newMem);

		$self->{$IM}->{group}->{$grp}->{members} = $newMem;
		if($self->getConfig('confOnly') eq 'f') {
			$arch = Archive->new(type => 'groups',
				destDir => $self->getConfig('destDir'),
				sessID => $self->{sessID},
				archives => $self->getGroupAttr($IM, $grp, 'archives')
			);	
			$arch->xmlAttrConvert($old, $new, 'uid');
		}
	}
	}
	
	# remap any mail list memberships
	if(defined $self->{$IM}->{list}) {
	@keys = keys %{ $self->getMailLists($IM) }; 
	foreach my $lst (@keys) {
		my $list = $self->{$IM}->{list}->{$lst}->{local_recips};
		my $newList = TreeXml::remapItem($old, $new, $list);
		$self->{$IM}->{list}->{$lst}->{local_recips} = $newList 
			if($newList); 
	}
	}
	TreeXml::renameNode($old, $new, $self->{$IM}->{user});
	return 1;
}

sub remapAlias
{
	my $self = shift;
	my $old = shift;
	my $new = shift;

	# this is pretty harmless
	my $user = $self->{$IM}->{aliases}->{$old};

	my $alii = $self->{$IM}->{user}->{$user}->{Email}->{aliases};	
	my $newAlii = TreeXml::remapItem($old, $new, $alii);
	$self->{$IM}->{user}->{$user}->{Email}->{aliases} = $newAlii if($newAlii);
	return 1;
}

sub remapGroup
{
	my $self = shift;
	my $old = shift;
	my $new = shift; 
	my @keys;

	if($self->getConfig('confOnly') eq 'f') {
		my $arch = Archive->new(type => 'groups',
			destDir => $self->getConfig('destDir'),
			sessID => $self->{sessID},
			archives => $self->getGroupAttr($IM, $old, 'archives')
		);	
		$arch->xmlAttrConvert($old, $new, 'gid');
	}

	if(defined $self->{$IM}->{list}) {
	@keys = keys %{ $self->getMailLists($IM) };
	foreach my $lst (@keys) {
		if($self->getMailListAttr($IM, $lst, "group") eq $old) {
			$self->{$IM}->{list}->{$lst}->{group} = $new;
			TreeXml::renameNode($old, $new, $self->{$IM}->{list});
		}
	}
	}
	TreeXml::renameNode($old, $new, $self->{$IM}->{group});
	return 1;
}

sub removeUser
{
	my $self = shift;
	my $user = shift || return;
	my @keys;

	# remove any group memberships
	if(defined $self->{$IM}->{group}) {
	@keys = keys %{ $self->getGroups($IM) };	
	foreach my $grp (@keys) {
		next unless(defined($self->{$IM}->{group}->{$grp}->{members}));
		my $ret = TreeXml::removeItem($user, $self->{$IM}->{group}->{$grp}->{members});
		if($ret && $self->getConfig('confOnly') eq 'f') {
			my $arch = Archive->new(type => 'groups',
				destDir => $self->getConfig('destDir'),
				sessID => $self->{sessID},
				archives => $self->getGroupAttr($IM, $grp, 'archives')
			);	
			$arch->xmlAttrConvert($user, 'admin', 'uid');
		}
	}
	}
	# remap any mail list memberships
	if(defined $self->{$IM}->{list} ) {
	@keys = keys %{ $self->getMailLists($IM) };
	foreach my $lst (@keys) {
		next unless(defined($self->{$IM}->{list}->{$lst}->{local_recips}));
		TreeXml::removeItem($user, $self->{$IM}->{list}->{$lst}->{local_recips});
	}
	}
	return 1;
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
