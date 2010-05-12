#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: validate_alias.pl 840 2006-08-06 20:20:34Z bsmith $
#
# check for alias collision with other aliases and with system accounts
# this doesn't resolve conflicts with aliases in /etc/mail/aliases, because
# it assumes that if the local_alias property is set that whoever is doing
# it knows what they are doing
#

use CCE;
use POSIX qw(isalpha);
use Email;

my $DEBUG = 0;

# reserved system accounts to check for
my %reserved = map { $_ => 1 } qw(
mailer-daemon
bin
daemon
games
ingres
system
toor
uucp
dumper
decode
nobody
root
);

# Removed the following:
#abuse
#postmaster
#operator
#manager


my $cce = new CCE('Domain' => 'base-email');
$cce->connectfd();

my $obj = $cce->event_object();
my $new = $cce->event_new();

my $lcname = $obj->{alias}; 
$lcname =~ tr/A-Z/a-z/;

if (exists($new->{alias}) || exists($new->{fqdn})) {
	#verify uniquness
	my $fail = 0;
	my $find_criteria = { 
				'alias' => &build_regi($obj->{alias}),
				'fqdn' => &build_regi($obj->{fqdn})
			    };

	my @oids = $cce->findx('EmailAlias', {}, $find_criteria);
	my @poids = $cce->findx('ProtectedEmailAlias', {}, $find_criteria);

	#
	# find returns the object being modified, so if the sum
	# is more than one there's a problem
	#
	if ((scalar(@oids) + scalar(@poids)) > 1) { 
		$DEBUG && print STDERR "oids: @oids\n";
		$DEBUG && print STDERR "poids: @poids\n";
		$fail = 1; 
	}
  
	# ignore the reserved names if this is a ProtectedEmailAlias
	if ($obj->{CLASS} eq 'EmailAlias' && $reserved{$lcname}) {
		$fail = 2; 
	}
	if ($fail) {
		&fail($cce, $obj->{alias}, $fail);
	}
}

#
# get the system's fqdn, since there can be a vsite with the same fqdn
# as the system
#
my ($soid) = $cce->find('System');
my ($ok, $sys) = $cce->get($soid);
if (!$ok) {
	$cce->bye('FAIL', '[[base-email.cantReadSystem]]');
	exit(1);
}

my $sys_fqdn = $sys->{hostname} . '.' . $sys->{domainname};

# make sure there aren't any conflicts involving a vsite with the same
# fqdn as the system if the alias and action are not the same
my @conflicts = ();
if ($obj->{alias} ne $obj->{action}) {
	if (!$obj->{fqdn}) {
		my $find_criteria = {
					'alias' => &build_regi($obj->{alias}),
					'fqdn' => &build_regi($sys_fqdn)
				    };
							
		@conflicts = $cce->findx('EmailAlias', {}, $find_criteria);
		push @conflicts, $cce->findx('ProtectedEmailAlias', {},
					     $find_criteria);
	
		if (scalar(@conflicts)) {
			&fail($cce, $obj->{alias}, 2);
		}
	} elsif ($obj->{fqdn} eq $sys_fqdn) {
		my $find_criteria = {
					'alias' => &build_regi($obj->{alias}),
				    };
	
		@conflicts = $cce->findx('EmailAlias', { 'fqdn' => '' }, 
					 $find_criteria);
		push @conflicts, $cce->findx('ProtectedEmailAlias', 
					     { 'fqdn' => '' }, $find_criteria);
	
		if (scalar(@conflicts)) {
			&fail($cce, $obj->{alias}, 2);
		}
	}
}

# if the alias and action are not the same, make sure there is
# not another user in CCE whose name is equal to the alias
if ($obj->{alias} ne $obj->{action}) {
	my $regex_criteria = { 
				'name' => &build_regi($obj->{alias})
			     };
	
	# if this isn't a local alias, restrict the search to the same site.
	my $exact_criteria = {};
	if (!$obj->{local_alias}) {
		$exact_criteria->{'site'} = $obj->{site};
	}

	@conflicts = $cce->findx('User', $exact_criteria, $regex_criteria);

	# if the fqdn of the alias is equal to the system fqdn, check for
	# system users
	if ($obj->{fqdn} eq $sys_fqdn) {
		$exact_criteria->{'site'} = '';
		push @conflicts, $cce->findx('User', $exact_criteria,
					     $regex_criteria);
	}

	if (scalar(@conflicts)) {
		&fail($cce, $obj->{alias}, 2);
	}
}

$cce->bye('SUCCESS');
exit(0);

sub fail
{
	my ($cce, $alias, $code) = @_;
	$cce->warn('aliasInUse', { 'name' => $alias, 'code' => $code });
	$cce->bye('FAIL');
	exit(1);
}

#
# build a case insensitive posix regex to pass to findx
# this is stuff that must pass through smtp, so we assume ascii since
# that is all that is generally accepted for smtp still (see RFC 2821)
#
sub build_regi
{
	my $string = shift;
	
	my $regex = '';
	for (my $i = 0; $i < length($string); $i++) {
		my $char = substr($string, $i, 1);
		
		if (!isalpha($char)) {
			# not an alphbetic char, see if it should be escaped
			$char =~ s/([\^\\\+\-\.\?\{\}\(\)\$])/\\$1/;
			$regex .= $char;
		} else {
			# alphabetical, add lower and upper case
			$regex .= '[' . lc($char) . uc($char) . ']';
		}
	}

	# always want exact matching here
	return "^$regex\$";
}
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
