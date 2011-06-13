#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: unique.pl,v 1.13.2.2 2002/03/06 22:17:55 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# verify that the fqdn, web aliases, and mail aliases are unique for a vsite
#

use CCE;
use POSIX qw(isalpha);

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-vsite');

$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

my @oids= $cce->find("System");
my ($ok, $system) = $cce->get($oids[0]);

# don't allow system FQDN as the vsite FQDN
my $system_fqdn = lc($system->{hostname} . "." . $system->{domainname});
my $vsite_fqdn = lc($vsite_new->{fqdn});
if ($system_fqdn eq $vsite_fqdn) {
	$cce->bye('FAIL', "[[base-vsite.systemFqdnNotAllowed,fqdn='$vsite_new->{fqdn}']]");
	exit(1);
}

# don't allow localhost as the hostname
if ($vsite_new->{hostname} =~ /localhost/i) {
	$cce->bye('FAIL', '[[base-vsite.localhostNotAllowed]]');
	exit(1);
}

if ($vsite_new->{fqdn}) {
	# verify that no other site is using this fqdn
	my @oids = $cce->findx("Vsite", {},
			       { 
			       	'fqdn' => &build_scalar_regi($vsite_new->{fqdn})
			       });

	# there should be no oids found
	if (scalar(@oids) > 1) {
		$cce->bye('FAIL', 
			  "[[base-vsite.fqdnInUse,fqdn='$vsite_new->{fqdn}']]");
		exit(1);
	}
}

# fqdn must be less than or equal to 255
if ((length($vsite->{fqdn}) > 255) || 
    (length("$vsite->{hostname}.$vsite->{domain}") > 255)) {

    $cce->bye('FAIL', '[[base-vsite.fqdnTooLong]]');
    exit(1);
}

# prefix must be no longer than five characters:
if (length($vsite_new->{prefix}) > 5) {
    $cce->bye('FAIL', '[[base-vsite.prefixTooLong]]');
    exit(1);
}

# Make sure our prefix (if given) isn't used by any other vsite:
if ($vsite_new->{prefix}) {
    if (length($vsite_new->{prefix}) > 0) {
	# Prefix given. Now verify that no other site is using this prefix:
	my @oids = $cce->findx("Vsite", {},
			       { 
			       	'prefix' => &build_scalar_regi($vsite_new->{prefix})
			       });

	# there should be no oids found
	if (scalar(@oids) > 1) {
		$cce->bye('FAIL', 
			  "[[base-vsite.prefixInUse,fqdn='$vsite_new->{prefix}']]");
		exit(1);
	}
    }
}

# Make sure prefix is alphanumerical:
if (length($vsite_new->{prefix}) > 0) {
    if ($vsite_new->{prefix} =~ /^[a-zA-Z0-9]+$/) {
	# OK
    }
    else {
	$cce->bye('FAIL', '[[base-vsite.prefixInvalidChars]]');
    }
}

#
# should we even verify uniqueness of aliases?  this only really matters for
# auto dns, and that will verify uniqueness when creating dns records provided
# the conflicting site is also has auto dns enabled or the records were created
# manually on this server
# oh well, do it anyways, it can always be yanked 
my (%old_aliases, %new_aliases, @used_web_aliases, @used_mail_aliases);

if ($vsite_new->{webAliases}) {
	# only verify newly entered aliases since old aliases will already
	# be with this object if it is being modified
	%old_aliases = map { $_ => 1 } $cce->scalar_to_array($vsite_old->{webAliases});
	%new_aliases = map { $_ => 1 } $cce->scalar_to_array($vsite_new->{webAliases});
	
	&find_aliases_to_verify(\%old_aliases, \%new_aliases);

	# now verify the remaining aliases in %new_aliases
	for my $alias (keys %new_aliases) {
		my $search_regex = &build_array_regi($alias);

		my @oids = $cce->findx("Vsite", {},
				       { 
				       	'webAliases' => $search_regex
				       });
		if (scalar(@oids) > 1) {
			push @used_web_aliases, $alias;
		}
	}

	# okay, yes, non-unique web aliases are fatal
	if (scalar(@used_web_aliases)) {
		$cce->warn("[[base-vsite.usedWebAliases,aliases='" . join(', ', @used_web_aliases) . "']]");
		$cce->bye('FAIL');
		exit(1);
	}
}

if ($vsite_new->{mailAliases}) 
{
	# same as web aliases only verify the new ones
	%old_aliases = map { $_ => 1 } $cce->scalar_to_array($vsite_old->{mailAliases});
	%new_aliases = map { $_ => 1 } $cce->scalar_to_array($vsite_new->{mailAliases});
	
	&find_aliases_to_verify(\%old_aliases, \%new_aliases);

	for my $alias (keys %new_aliases) {
		my $search_regex = &build_array_regi($alias);
		my @oids = $cce->findx("Vsite", {},
				       { 
				       	'mailAliases' => $search_regex
				       });

		if (scalar(@oids) > 1) {
			push @used_mail_aliases, $alias;
		}
	}

	# mail aliases must be unique
	if (scalar(@used_mail_aliases))
	{
		$cce->bye('FAIL', "[[base-vsite.usedMailAliases,aliases='" . join(', ', @used_mail_aliases) . "']]");
		exit(1);
	}
}

$cce->bye('SUCCESS');
exit(0);

sub find_aliases_to_verify
{
	my $old = shift;
	my $new = shift;

	for my $alias (keys %$new) {
		if ($old->{$alias}) {
			delete($new->{$alias});
		}
	}
}

#
# warning, the build_*_regi functions may not work with multibyte character
# encodings.
#

#
# returns a case insensitive regular expression to use when searching
# an array property
#
sub build_array_regi
{
	my $string = shift;

	return('&' . _build_regi($string) . '&');
}

#
# returns a case insensitive regular expression to use when searching
# a scalar property
#
sub build_scalar_regi
{
	my $string = shift;

	return('^' . _build_regi($string) . '$');
}

#
# private function that actually constructs case-insensitive regexs, 
# as per RFC 2396 (sec. 3.2.2), RFC 2616 (sec 3.2), and RFC 2821 hostnames for
# http and smtp must be only ascii characters (no multibyte).  no special case
# for japanese to make life simpler
#
sub _build_regi
{
	my $string = shift;

	my $regex = '';
	for (my $i = 0; $i < length($string); $i++) {
		my $char = substr($string, $i, 1);
			
		if (!isalpha($char)) {
			# not an alphabetic char, see if it should be escaped
			$char =~ s/([\^\\\+\-\.\?\{\}\(\)\$])/\\$1/;
			$regex .= $char;
		} else {
			# alphabetical, add lower and upper case
			$regex .= '[' . lc($char) . uc($char) . ']';
		}
	}

	# caller handles any boundary additions
	return $regex;
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
