# $Id: ShellCCE.pm,v 1.8 2002/04/26 21:23:25 jeffb Exp $
# Copyright (c) 1999,2000,2001 Cobalt Networks, Inc. 
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

package ShellCCE;

use lib "/usr/sausalito/perl";
require CCE;
use vars qw(@ISA %classKey);
@ISA = qw(CCE);


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
	use Data::Dumper;
	my $self = shift;
	my $badkeys = shift;
	my @info = @_;
	
	if ($badkeys) {
		foreach my $m (keys %{ $badkeys }) {
		foreach my $k (keys %{ $badkeys->{$m} }) {
			warn "CCE Error: $k => ", $badkeys->{$m}->{$k}, "\n";
		}
		}
	}
	if (scalar(@info)) { 
		foreach my $m (@info) { 
			warn "CCE Error:", $m, "\n"; 
		} 
	}
	return 1;	
}

sub scalarToScalar
# Turns a comma or space seperated list into an CEE scalar
{
	my $self = shift;
	my $scalar = shift;

	return($self->array_to_scalar(split(/[\s,]/, $scalar)));	
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

sub createShell
{
	my $self = shift;
	my $shellRef = shift || return 0;
	my $class = shift || return 0;
	my ($ok, $bad, @info);

	my $ref = $self->unLoad($shellRef);
	#warn "going to create: ", $ref, "\n";
	($ok, $bad, @info) = $self->create($class, $ref);
	if($ok == 0) { 
		$self->printReturn($bad, @info);
		return 0;
	} else { return $self->oid(); }
}


sub setShell
{
	my $self = shift;
	my $oid = shift || return;
	my $shellRef = shift || return;
	my $space = shift || '';
	my ($ok, $bad, @info);

	my $ref = $self->unLoad($shellRef);

	# return if there is nothing to set
	my @keys = keys %{ $ref };
	if(scalar @keys == 0) { return; }

	$ref = $self->applyRef($oid, $ref, $space);

	($ok, $bad, @info) = $self->set($oid, $space, $ref);
	if($ok == 0) { $self->printReturn($bad, @info); }
}

sub setNameSpaces
{
	my $self = shift;
	my $oid = shift;
	my $shellRef = shift;

	my ($ok, @namespace) = $self->names($oid);
	if($ok == 0) { warn "Could not get namespaces for $oid" }
	foreach my $space (@namespace) {
		next if(!defined $shellRef->{$space});
		print "setting namespace $space\n";
		$self->setShell($oid, $shellRef->{$space}, $space);
	}
}


sub unLoad
# returns the data stucture that you can pass to CCE, with array
# values mapped and namespaces (if defined are removed)
{
	my $self = shift;
	my $data = shift;
	my $ref = {};

	foreach $attr (keys %{ $data }) {
		next if(ref $data->{$attr} eq 'HASH');
		#warn "unLoad $attr => ", $data->{$attr}, "\n";
		if(ref $data->{$attr} eq 'ARRAY') {
			my @arr = @{ $data->{$attr} };
			$ref->{$attr} = $self->array_to_scalar(@arr);
		} else { $ref->{$attr} = $data->{$attr} }
	}
	return $ref;
}

sub applyRef
{
	my $self = shift;
	my $oid = shift;
	my $ref = shift;
	my $space = shift || '';

	my $modName;
	if(defined $ref->{add}) { 
		$modName = $ref->{add};
		$ref->{$modName} = $self->addArray(
			$oid, $modName, $ref->{$modName}, $space
		);
		delete $ref->{add};
	} elsif(defined $ref->{del}) { 
		$modName = $ref->{del};
		$ref->{$modName} = $self->delArray(
			$oid, $modName, $ref->{$modName}, $space
		);
		delete $ref->{del};
	} 
	return $ref;
}

sub addArray
{
	my $self = shift;
	my $oid = shift || return;
	my $label = shift || return;
	my $val = shift || return;
	my $space = shift || '';

	my ($ok, $obj, $newVal);

	($ok, $obj) = $self->get($oid, $space);
	if($ok == 0) { warn "setArray: Could not find oid: $oid\n"; return }

	if(defined $obj->{$label}) { 
		# remove the first &
		$val =~ s/^&//;
		$newVal = $obj->{$label}.$val; 
	} else { $newVal = $val }

	return $newVal;
}

sub delArray
{
	my $self = shift;
	my $oid = shift || return;
	my $label = shift || return;
	my $val = shift || return;
	my $space = shift || '';

	my ($ok, $bad, @info);
	my ($obj, $newVal);

	($ok, $obj) = $self->get($oid, $space);
	warn Dumper($obj);
	if($ok == 0) { warn "removeArray: Could not find oid: $oid\n"; return }

	if(defined $obj->{$label}) { 
		my @arr = $self->scalar_to_array($obj->{$label});
		$val =~ s/%40/\@/g;
		$val =~ s/^&//;
		$val =~ s/&$//;
		for(my $i = 0; $i <= $#arr; $i++) {
			if($arr[$i] =~ /$val/) { splice(@arr, $i, 1); }
		}	
		if(scalar @arr == 0) { $newVal = ''; }
		else { $newVal = $self->array_to_scalar(@arr) }
	} else { warn "delArray $label was not set $space\n"; return }

	return $newVal;
}


# used to make sure that the group or fqdn exists
# places site group into $self->{obj}->{site}
sub setParent
{
	my $self = shift;
	my $shell = shift;
	my $site;

	if($shell->isObj('group')) {
		($site) = $self->findx("Vsite", { name => $shell->obj('group') });
		if($site) { $shell->putObj('site', $shell->obj('group')) }
		else { 
			print "Cannot find virtual site for group: ", $shell->obj('group'), "\n";
			$self->bye("later");
			exit 1;
		}
	} elsif($shell->isObj('fqdn')) {
		($site) = $self->findMember("Vsite",
			{ fqdn => $shell->obj('fqdn') }, undef, 'name');
		if($site) { $shell->putObj('site', $site) } 
		else {
			print "Cannont find virtual site for fqdn: ", $shell->obj('fqdn'), "\n";
			$self->bye("later");
			exit 1;
		}
	} else {
		# how the hell did I get here?
		$self->bye("later");
		$shell->usage();
		exit 1;
	}
}

# converts arrays to cce styles arrays
# converts ref tags to label and source|value
sub normalizeObj
{
	my $self = shift;
	my $shell = shift;

	my ($label, $space);
	foreach my $opt (keys %{ $shell->maps() }) {
		$label = $shell->label($opt);
		$space = $shell->nameSpace($opt);
		if($shell->isType($opt) && $shell->type($opt) eq 'array') {
			my $cceArray = $self->scalarToScalar($shell->obj($label, $space));
			$shell->putObj($label, $cceArray, $space)
		}
	}
	$shell->delObj('fqdn') if($shell->isObj('fqdn'));
	$shell->delObj('group') if($shell->isObj('group'));
}

# This method will take a cce oid and option data structure 
# and see if anything needs to be changed.
sub diffCCE
{
	my $self = shift;
	my $data = shift;
	my $oid = shift;
	my $ret = -1;	
		
	my ($ok, $obj) = $self->get($oid);
	if($ok == -1) { warn "Not a valid CCE oid\n"; return $ret }
	
	foreach my $mem (keys %{ $data }) {
		if(defined($obj->{ $data->{$mem} }) ) {	$ret = 1; } 
		elsif ($data->{$mem} eq $obj->{$mem}) { $ret = 1; }
	}
	return $ret
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
