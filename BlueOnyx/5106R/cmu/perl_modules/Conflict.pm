# $Id: Conflict.pm 1065 2008-01-27 09:19:54Z shibuya $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# Written by:
#	Jeff Bilicki <jeff.bilicki@sun.com>


package Conflict;
use strict;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK $IM $EX);
@ISA	= qw(Exporter);	
@EXPORT	= qw($EX $IM);
@EXPORT_OK = qw();
	
# globals for import and export tree
$EX = 'ex';
$IM = 'im';

require TreeXml;
require Archive;
require Resolve;

1;

sub new
# Creates a new conflict object you need a export tree, import tree, 
# and the config data
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = {};
	bless ($self, $class);
	
	# current Tree
	$self->{$EX} = shift || die "You must provide an export tree\n";
	# importing Tree
	$self->{$IM} = shift || die "You must provide an import tree\n";
	$self->{glbConf} = shift || die "You must provide a global conf hash\n";
	return $self;
}

# build the accessors
sub getImport { return($_[0]->{$IM}) }
sub getExport { return($_[0]->{$EX}) }
sub getConfig { return($_[0]->{glbConf}->{$_[1]} ) }
sub getConflict { return($_[0]->{glbConf}->{conflict}->{$_[1]}) }

# for the most part anything ending with s returns the whole group
# while the singular verison returns a specific item
# I was smoking the perl crack pipe when I did this

# user accessors
sub getUsers { 
	my $self = shift; my $scope = shift;
	if(defined $self->{$scope}->{user}) { return $self->{$scope}->{user} }
	else { return \%_ }
}
sub getUser { return($_[0]->{$_[1]}->{user}->{$_[2]}) }
sub getUserAttr { 
	return($_[0]->{$_[1]}->{user}->{$_[2]}->{$_[3]}) 
}

sub getMailLists { 
	my $self = shift; my $scope = shift;
	if(defined $self->{$scope}->{list}) { return $self->{$scope}->{list} }
	else { return \%_ }
}
sub getMailList { return($_[0]->{$_[1]}->{list}->{$_[2]}) }
sub getMailListAttr { 
	return($_[0]->{$_[1]}->{list}->{$_[2]}->{$_[3]}) 
}


# this checks only the keys for the class
# arugments: scope(im|ex), class name, value
sub check {
	my $self = shift;
	my $scope = shift;
	my $class = shift;
	my $key = shift || return 0;

	(defined $self->{$scope}->{$class}->{$key}) ? (return 1) : (return 0);
}

sub checkUser { return($_[0]->check($_[1], 'user', $_[2])) }
sub checkMailList { return($_[0]->check($_[1], 'list', $_[2])) }


# Base function for detect dupilcate user name
sub detectUserName
{
	my $self = shift;
	my $user = shift || return 0;
	my $scope = shift || $EX;

	if($self->checkUser($scope, $user) == 1) {
		my $reslv = Resolve->new(%{ $self->getConflict('userName') });
		$reslv->text("User $user already exists\n");
		$reslv->key($user);
		return($reslv);
	} else { return 1 }
}

# This will apply the Reslove object to the import tree
# The result member must be set in the reslove object
sub runResult
{
	my $self = shift;
	my $reslv = shift || return 0;
	my $func = $reslv->result();
	
	if($func) {
		return($self->$func($reslv));
	} else {
		warn "No result in Reslove object\n";
		return 0;
	}
	return 1;
}

# When Resolve result is Quit this method is run
sub quit { die "User Aborted program\n"; }

sub changeClass
{	
	my $self = shift;
	my $reslv = shift || return 0;
	my $old = $reslv->key();
	my $new = $reslv->resultVal();
	my $type = $reslv->class();

	if($type eq 'user') { $self->remapUser($old, $new) } 
	elsif ($type eq 'alias') { $self->remapAlias($old,$new) } 
	elsif ($type eq 'group') { $self->remapGroup($old, $new); } 
	elsif ($type eq 'vsite') { $self->remapVsite($old, $new); }
	elsif ($type eq 'list') {
		TreeXml::renameNode($old, $new, $self->{$IM}->{$type});
	} else {
		warn "ERROR Unknown conflict resolution type: $type\n";
	}
	return 1;
}


# When Resolve result is Drop this method is run
sub dropClass
{
	my $self = shift;
	my $reslv = shift || return 0;
	my $name = $reslv->key();
	my $type = $reslv->class();
	
	if($type eq 'user') {
		$self->removeUser($name);
		TreeXml::deleteNode($name, $self->{$IM}->{$type});
	} elsif($type eq 'group') {
		TreeXml::deleteNode($name, $self->{$IM}->{$type});
		TreeXml::deleteNode($name, $self->{$IM}->{list});
	} elsif($type eq 'vsite') {
		$self->removeVsite($name);
		TreeXml::deleteNode($name, $self->{$IM}->{$type});
	} elsif($type eq 'list') {
		TreeXml::deleteNode($name, $self->{$IM}->{$type});
	} elsif($type eq 'alias') {
		my $user = $self->{$IM}->{aliases}->{$name};
		TreeXml::removeItem($name,$self->{$IM}->{user}->{$user}->{Email}->{aliases});
	}
	return 1;
}

# When Resolve result is Deactivate this method is run
sub deactVal
{
	my $self = shift;
	my $reslv = shift || return 0;
	
	my $class = $reslv->class();
	my $name = $reslv->key();
	my $attr = $reslv->attr();

	if($self->getConfig('product') eq 'RaQ3') {
		$self->{$IM}->{$class}->{$name}->{$attr} = 'off';
	} elsif($self->getConfig('product') =~ /(RaQ550|5100R|5200R|TLAS1HE|Qube3|5106R)/) {
		$self->{$IM}->{$class}->{$name}->{$attr} = '0';
	} else { $self->{$IM}->{$class}->{$name}->{$attr} = 'f' }
	return 1;
}

# When Resolve result is Discard this method is run
sub discardVal
{
	my $self = shift;
	my $reslv = shift || return 0;
	my $name = $reslv->key();
	my $type = $reslv->class();
	my $attr = $reslv->attr();
	
	my $tree = $self->{$IM}->{$type}->{$name}->{$attr};
	TreeXml::removeItem($reslv->attrValue(), $tree);
	return 1;
}

sub mergeClass
{
	my $self = shift;
	my $reslv = shift;
	my $type = $reslv->class();
	my $name = $reslv->key();
	
	if($type eq 'user') {
		$self->{$IM}->{user}->{$name}->{merge} = 't';
	} elsif($type eq 'vsite') {
		$self->{$IM}->{vsite}->{$name}->{merge} = 't';
	} elsif($type eq 'group') {
		$self->{$IM}->{group}->{$name}->{merge} = 't';
	}
	return 1;
}

sub remapAlias
{
	my $self = shift;
	my $old = shift;
	my $new = shift || return 0;

	# this is pretty harmless
	my $user = $self->{$IM}->{aliases}->{$old};

	my $alii = $self->{$IM}->{user}->{$user}->{aliases};	
	my $newAlii = TreeXml::remapItem($old, $new, $alii);
	$self->{$IM}->{user}->{$user}->{aliases} = $newAlii if($newAlii);
	return 1;
}

sub removeAlias
{
	my $self = shift;
	my $alias = shift || return 0;
	my $user = $self->{$IM}->{aliases}->{$alias};
	
	if($self->{$IM}->{user}->{$user}->{aliases}) {
	TreeXml::removeItem($alias, $self->{$IM}->{user}->{$user}->{aliases});
	}
	return 1;
}

sub remapAll
# this remap any given attribute, if it exists
# arguments: class, name of attribute, new value
# $flict->remapAll('vsite','ipaddr','172.16.1.1')
# should make this able to update namespaces also
{
	my $self = shift;
	my $class = shift;
	my $type = shift;
	my $value = shift || return 0;

	my $tree = $self->{$IM}->{$class};
	foreach my $item (keys %{ $tree }) {
		if(defined($tree->{$item}->{$type})) { 
			$tree->{$item}->{$type} = $value;
		}
	}	
	return 1;
}

sub removeEmpty
# this will check the tree for empty values and delete them
{
	my $self = shift;
	my $scope = shift || $IM;	

	my @keys = keys %{ $self->{$scope} };
	foreach my $key (@keys) {
		next unless(ref $self->{$scope}->{$key} eq 'HASH');
		unless(keys %{ $self->{$scope}->{$key} }) {
			delete $self->{$scope}->{$key};
		}
	}
	return 1;
}

sub removeListMember
{
	my $self = shift;
	my $fqdn = shift;
	my $user = shift;
	my $param = shift || 'intRecips';

	return unless(defined $self->{$IM}->{list});
	my $mTree;
	# remove any mail list memberships
	
	my @keys = keys %{ $self->getMailLists($IM) };
	foreach my $id (@keys) {
		if($self->{$IM}->{list}->{$id}->{fqdn} eq $fqdn) {
			$mTree = $self->getMailList($IM, $id);
			TreeXml::removeItem($user, $mTree->{$param});
		}
	}
	return 1;
}

sub remapListMember
{
	my $self = shift;
	my $fqdn = shift;
	my $old = shift;
	my $new = shift;
	my $param = shift || 'intRecips';

	return unless(defined $self->{$IM}->{list});
	
	my $mTree;
	my @keys = keys %{ $self->getMailLists($IM) };
	foreach my $id (@keys) {
		if($self->{$IM}->{list}->{$id}->{fqdn} eq $fqdn) {
			$mTree = $self->getMailList($IM, $id);
			TreeXml::remapItem($old, $new, $mTree->{$param});
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
