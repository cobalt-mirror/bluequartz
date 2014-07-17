# $Id: Shell.pm,v 1.1 2001/08/20 19:42:55 jeffb Exp $
# Copyright (c) 1999,2000,2001 Cobalt Networks, Inc. 
# Sun Microsystems, http://www.sun.com
# written by: Jeff Bilicki

package Shell;

require CCE;
use vars qw( @ISA );
@ISA = qw( CCE );

1;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = new CCE;
	bless($self, $class);
	return $self;
}

sub create
{
	my $self = shift;
	my $type = shift;
	my $ref = shift;
	my ($ok, $badkeys, @info);

	($ok, $badkeys, @info) = $self->SUPER::create($type, $ref);
	unless($ok) {
		$self->printReturn($badkeys, @info);
		$self->bye("game over man");
		exit 1;
	}
	return($ok);
}

sub set 
{
	my $self = shift;
	my $oid	= shift;
	my $type = shift;
	my $ref = shift;
	my ($ok, $badkeys, @info);

	($ok, $badkeys, @info) = $self->SUPER::set($oid, $type, $ref);
	unless($ok) { $self->printReturn($badkeys, @info); }
	return($ok);
}

sub printReturn
# take error and warning output from CCE and print it
{
	require I18n;
	my $self = shift;
	my $badkeys = shift;
	my @info = @_;
	
	# set the locale 
	my $i18n = new I18n();
    $i18n->setLocale(I18n::i18n_getSystemLocale($self));

	if ($badkeys) {
		foreach my $m (keys %{ $badkeys }) {
		foreach my $k (keys %{ $badkeys->{$m} }) {
			warn $i18n->get($badkeys->{$m}->{$k}), "\n";
		}
		}
	}
	if (scalar(@info)) { foreach my $m (@info) { warn $i18n->get($m) } }
	return;	
}

sub scalar_to_scalar
# Turns a comma or colon seperated list into an CEE scalar
{
	my $self = shift;
	my $scalar = shift;

	return($self->array_to_scalar(split(/\s*[,:]\s*/, $scalar)));	
}

sub getNames
# returns a ref to a  hash of the name attribute => oid
# my $unames = $cce->getNames("User");
# if(exists($unames->{$user})) { ($ok, $obj) = $cce->get($unames->{$user}) }
{
	my $self = shift;
	my $type = shift || return;

	my $hash = {};
	my @oids = $self->find($type);
	foreach my $oid (@oids) {
		my ($ok, $obj) = $self->get($oid);
		if($ok) {
			$hash->{$obj->{name}} = $oid;
		}
	}
	return $hash;
}

sub addMember
{
	my $self = shift;
	my $oid = shift || return;
	my $mem = shift || return;

	my ($ok, $obj) = $self->get($oid);
	return unless($ok);
	($obj->{members}) ? 
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

sub validateUsers
# this makes sure members are local users
{
	my $self = shift;
	my $string = shift || return;

	my @arr;
	foreach my $user (split /\s*[,:]\s*/, $string) {
		if(getpwnam($user)) {
			push @arr, $user;
		} else {
			warn "Not a valid user ", $user, ", dropping...\n";
		}
	}
	$string = $self->array_to_scalar(@arr);
	return $string;
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
