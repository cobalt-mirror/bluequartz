# $Id: cmuCCE.pm 922 2003-07-17 15:22:40Z will $
# Copyright (c) 1999,2000,2001 Cobalt Networks, Inc. 
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# written by: Jeff Bilicki
# 
#
# This package is used as wrapper based on CCE.pm that when
# using the create and set methods for a Cmu object it will
# automatically prints out the localized error mesosage to STDERR 
# my $obj = CMU->new();
# my $ok = $obj->create("User", $userRef);

# any function not listed in this module will run the method 
# in CCE.pm

package cmuCCE;
use strict;

require CCE;
use vars qw(@ISA %classKey);
@ISA = qw(CCE);

use lib "/usr/sausalito/perl";

my %classKey = (
	Vsite => 'fqdn',
	User => 'name',
	MailList => 'name',
	Group => 'name',
	Network => 'device',
);

1;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = new CCE;
	bless($self, $class);
	return $self;
}

sub printReturn
# take error and warning output from CCE and print it
{
	require I18n;
	use Data::Dumper;
	my $self = shift;
	my $badkeys = shift;
	my @info = @_;
	
	# set the locale 
	my $i18n = new I18n();
    $i18n->setLocale(I18n::i18n_getSystemLocale($self));

	if ($badkeys) {
		foreach my $m (keys %{ $badkeys }) {
		foreach my $k (keys %{ $badkeys->{$m} }) {
			warn "Error:", $i18n->get($badkeys->{$m}->{$k}), "\n";
		}
		}
	}
	if (scalar(@info)) { 
		foreach my $m (@info) { 
			warn "Error2:", Dumper($m), "\n"; 
		} 
	}
	return 1;	
}

sub scalar_to_scalar
# Turns a comma or colon seperated list into an CEE scalar
{
	my $self = shift;
	my $scalar = shift;

	return($self->array_to_scalar(split(/\s*[,:]\s*/, $scalar)));	
}

sub mapCCE
# maps values to CCE notation from arrays 
# this is to take care of the cmu data structure hash or array problem
{
	my $self = shift;
	my $hash = shift;

	my $ret;

	my @keys = keys %{ $hash };
	foreach my $key (@keys) {
	(ref($hash->{$key}) eq "ARRAY") ?
		($ret = $self->array_to_scalar(@{ $hash->{$key} })) :
		($ret = $self->array_to_scalar(split /\s/, $hash->{$key}));
	}
	return ($ret);
}

sub findMember
# this expands on the CCE.pm findx method, but returns the value of a 
# data member instead of the oid, if no member name is given whole object
# returned
# example usage:
# get me all the vsite data:
# @arr = $cce->findMember("Vsite");
# list all current IP addresses for vsites:
# @arr = $cce->findMember("Vsite", undef, undef, 'ipaddr')
# return the fqdn when you know the vsite name:
# @arr = $cce->findMember("Vsite", { name => $site }, undef, 'fqdn')
{
	my $self = shift;
	my $class = shift;
	my $crit = shift || {};
	my $recrit = shift || {};
	my $member = shift; 


	my @oids = $self->findx($class, $crit, $recrit);
	return unless(scalar @oids);
	my ($ok, $obj, @values);
	foreach my $oid (@oids) {
		($ok, $obj) = $self->get($oid);
		next if($ok == 0);
		if($member && defined $obj->{$member}) {
			push @values, $obj->{$member};
		} else { push @values, $obj; }
	}
	return (@values); 
}

sub addMember
{
	my $self = shift;
	my $oid = shift || return;
	my $mem = shift || return;

	my ($ok, $obj) = $self->get($oid);
	return unless($ok);
	(defined($obj->{members})) ? 
		($obj->{members} .= $mem."&") : ($obj->{members} = "&".$mem."&");
	$self->set($oid, "", { members => $obj->{members} });
	
	return;
}

sub delMember
{
	my $self = shift;
	my $oid = shift || return;
	my $mem = shift || return;
	
	my ($ok, $obj) = $self->get($oid);
	return unless($ok);
	
	my (@members) = $self->scalar_to_array($obj->{members});
	@members = grep {$_ ne $mem} @members;
	$self->set($oid, "", { 'members' => $self->array_to_scalar(@members) } );

	return;
}

sub loadHash
{
	require MIME::Base64;
	import MIME::Base64 qw(encode_base64);

	my $self = shift;
	my $curObj = shift;
	my $hash = {};

	foreach my $attr (keys %{ $curObj }) {
		next unless($curObj->{$attr});
		if(exists($self->{_arrayVal}->{$attr})) {
			# to make more this readable
			my $iVal = $self->{_arrayVal}->{$attr};
			@{ $hash->{$attr}->{$iVal} } = $self->scalar_to_array($curObj->{$attr});
		} elsif(exists($self->{_encodeAttr}->{$attr})) {
			$hash->{$attr} = encode_base64($curObj->{$attr}, "");
		} else { $hash->{$attr} = $curObj->{$attr} }
	}
	# remove stuff we don't need (better than next if stuff in for loop)
	delete $hash->{NAMESPACE} if(exists $hash->{NAMESPACE});
	delete $hash->{CLASSVER} if(exists $hash->{CLASSVER});
	delete $hash->{uiRights} if(exists $hash->{uiRights});
	delete $hash->{OID};

	return $hash;
}

sub unLoadHash
# returns the data stucture that you can pass to CCE, with array
# values mapped and namespaces (if defined are removed)
{
	require MIME::Base64;
	import MIME::Base64 qw(decode_base64);

	my $self = shift;
	my $data = shift;
	my $ref = {};

	foreach my $attr (keys %{ $data }) {
		#warn "$attr => ", $data->{$attr}, "\n";
		next if(ref($data->{$attr}) eq 'ARRAY');
		if(ref($data->{$attr}) eq 'HASH') {
			next if($attr eq 'archives');
			next if(!defined($self->{_arrayVal}->{$attr}));
			$ref->{$attr} = $self->mapCCE($data->{$attr});
		} elsif(defined($self->{_encodeAttr}->{$attr})) {
			$ref->{$attr} = decode_base64($data->{$attr});
		} else { $ref->{$attr} = $data->{$attr} }

	}
	return $ref;
}

sub loadNamespace
{
	my $self = shift;
	my $hash = shift;
	my $oid = shift;

	my ($ok, @namespace) = $self->names($oid);
	my $nObj;
	foreach my $name (@namespace) {
		($ok, $nObj) = $self->get($oid, $name);
		$hash->{$name} = $self->loadHash($nObj);

        # delete the empty ones, null as false pisses me off
        if (scalar(keys %{$hash->{$name}}) == 0) { delete $hash->{$name}; }
	}
	return $hash;
}

sub unLoadNamespace
{
	my $self = shift;
	my $data = shift;
	my $oid = shift;

	my ($bad, @info);
	my ($ok, @namespace) = $self->names($oid);
	return 0 if($ok == 0);
	foreach my $name (@namespace) {
		my $ref = {};
		if(defined($data->{$name})) {
			$ref = $self->unLoadHash($data->{$name});
			($ok, $bad, @info) = $self->set($oid, $name, $ref);
			if($ok == 0) { $self->printReturn($ok, $bad, @info) } 
			else { warn "Setting namescape $name ok=$ok\n" }
		 }

	}
	return 1;
}

sub exportClass
{
	my $self = shift;
	my $tree = shift;
	my $class = shift || return;
	my ($ok, $cceObj, $classData);

	my @oids = $self->find($class);
	#warn "$class oids are: ", @oids, "\n";
	foreach my $oid (@oids) {
		($ok, $cceObj) = $self->get($oid);
		next if($ok == 0);
		my $data = $self->loadHash($cceObj);
		$self->loadNamespace($data, $oid);
		foreach my $item (@{ $self->{_classes}->{$class} }) {
			delete $data->{$item};
		}
		if($class eq 'MailList') { 
			$tree->{migrate}->{$class}->{ $cceObj->{name} } = $data;		
		} elsif($class eq 'Network') {
			next if($data->{device} =~ /eth\d:\d+/);
			$tree->{migrate}->{$class}->{ $cceObj->{device} } = $data;
		} else { $tree->{migrate}->{$class} = $data; }

	}
	return $tree;
}

sub importClass
{
	my $self = shift;
	my $tree = shift;
	my $class = shift;
	my $ref = {};

	warn "Class is $class\n";
	#warn "Ref is: ", $tree->{$class}, "\n";

		
	$ref = $self->unloadHash($tree);
	my ($ok, $bad, @info) = $self->create($class, $ref);
	if($ok == 0) {
		$self->printReturn($ok, $bad, @info);
		return;
	} else { warn "Sucessfully created $class\n" }
	my $oid = $self->oid();
	$self->unLoadNamespace($tree, $oid);
}

sub mergeClass
{
	my $self = shift;
	my $tree = shift;
	my $class = shift;
	my $ref = {};

	warn "Class is $class\n";

		
	$ref = $self->unloadHash($tree);
	my ($ok, $bad, @info) = $self->set($class, $ref);
	if($ok == 0) {
		$self->printReturn($ok, $bad, @info);
		return;
	} else { warn "Sucessfully created $class\n" }
	my $oid = $self->oid();
	$self->unLoadNamespace($tree, $oid);
}


sub suspendAll
{
	my $self = shift;
	my $tree = shift;

	my ($vTree, $uTree);

	# don't think I need to do this for vsites
	my ($ok, $oid, $bad, @info);
	if(defined $tree->{user}) {
	
	foreach my $user (keys %{ $tree->{user} }) {
		$uTree = $tree->{user}->{$user};

		if($uTree->{suspend} ne 1) { next; }
		($oid) = $self->find("User", { name => $user });
		if(!$oid) { 
			warn "ERROR SuspendAll Could not find user $user\n"; 
			next;
		}
		($ok, $bad, @info) = $self->set($oid, '', { enabled => 0, ui_enabled =>
0 });
		if($ok == 0) {
			$self->printReturn($ok, $bad, @info)
		}
	}
	}

	foreach my $vsite (keys %{ $tree->{vsite} }) {
		$vTree = $tree->{vsite}->{$vsite};
		if($vTree->{suspend} ne 1) { next; }
		($oid) = $self->find("Vsite", { fqdn => $vTree->{fqdn} });
		if(!$oid) { 
			warn "ERROR SuspendAll Could not find vsite ", $vTree->{fqdn}, "\n";
			next;
		}
		($ok, $bad, @info) = $self->set($oid, '', { suspend => 0 });
		($ok, $bad, @info) = $self->set($oid, '', { suspend => 1 });
		if($ok == 0) {
			$self->printReturn($ok, $bad, @info)
		}
	}
}

sub importCerts
{
	my $self = shift;
	my $tree = shift;

	my $vTree;	
	foreach my $vsite (keys %{ $tree->{vsite} }) {
		$vTree = $tree->{vsite}->{$vsite};
		if(!defined $vTree->{SSL}->{enabled}) { next; }
		
		my $certFile = '/home/sites/'.$vTree->{fqdn}.'/certs/certificate';
		if(! -f $certFile) {
			warn "ERROR SSL enabled for ", $vTree->{fqdn}, 
				" but not certificate was found at $certFile\n";
			next;
		}

		my $sslCmd = '/usr/sausalito/sbin/ssl_import.pl '.$certFile.
			' --group='.$vTree->{newGroup}.' --type=serverCert';

		my $ret = system($sslCmd." &> /dev/null");
		if($ret != 0) {
			warn "ERROR importing certificate for ", $vTree->{fqdn}, "\n";
		} else {
			warn "INFO: SSL certificate imported for ", $vTree->{fqdn}, "\n";
		}
	}
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
