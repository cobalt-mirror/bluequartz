#!/usr/bin/perl -w
# $Id: Validators.pm 432 2004-12-16 13:49:21Z shibuya $
# author: harris@cobalt.com

# This package holds validators for use with CCE->validate

package Sauce::Validators;

use Sauce::Util;

use Exporter();
@ISA = qw(Exporter);

# Put any validators you want exported here.
@EXPORT = qw(
	username
	password
	array_of
	hash_of
	hostname
	uint
	boolean
	ipaddr
	emailaddr
	alters
);

# Test an array scalar to see if each 
# element matches the tester.

sub array_of {
	my $data = shift;
	my $cce = shift;
	my $tester = shift;
	my @args = @_;

	my @entries = $cce->scalar_to_array($data);

	foreach my $entry ( @entries ) {
		if ( ! &{$tester}($entry, @args) ) {
			return 0;
		}
	}

	return 1;
}

# Tests a hash scalar to see that each key and value matches the
# associated tester.
sub hash_of {
	my $data = shift;
	my $cce = shift;
	my $key_tester = shift;
	my $val_tester = shift;

	my %vals = $cce->scalar_to_array($data);

	while( my($key, $val) = each %vals ) {
		unless ( &{$key_tester}($key)  && &{$val_tester}($val) ) {
			return 0;
		}
	}

	return 1;
}

sub macaddr {
	return $_[0] =~ /^
		([0-9A-F]{2}\:){5} # Five blocks of AF:
		 [0-9A-F] # And a final block with no trailing ':'
		 $/xi; # Allow quoting and make case insensitive.
}

sub emailaddr {
	my $emailAddr = shift;

	my $userName;
	my $domainName;

	if( $emailAddr =~ /([^\@]+)\@([^\@]+)/ ) {
		$userName = $1;
		$domainName = $2;
	} else {
		$userName = $emailAddr;
	}

	if( ! username($userName) ) {
		return 0;
	}

	if( ! $domainName ) {
		return 1;
	} else {
		return domainname($domainName);
	}
}

sub username {
	return $_[0] =~ /^
		[A-Z] # Begins with a charachters.
		[\w\-\.]+ # Then at least one alphanums or hyphens or dots
		[A-Z0-9] # Then we end with a letter or num, no underscores.
		/xi; # Case insensitive commentables.
}

sub groupname {
	return username($_[0]);
}

# Tests a domain name or IP address.
sub netaddr {
	if( $_[0] =~ /^[0-9\.]*$/ ) {
		return ipaddr($_[0]);
	} else {
		return domainname($_[0]);
	}
}

sub domainname {
	my @parts = split(/\./, $_[0] );

	# Blank is invalid.
	if (scalar(@parts) == 0) {
		return 0;
	}

	if ( ! hostname($parts[0]) ) {
		return 0;
	}

	return 1;
}

sub url {
  my $url = shift;
  my $domain;

  # need http:// - rest can be ip or alpha, no whitespace  
  if ($url =~ /^http:\/\/(.*)/) {
    if ($1 =~ /(\s)+/) {
      return 0;
    }
    return 1;
  }
  else {
    return 0;
  }
    
}

sub hostname {
	return $_[0] =~ /^
		[A-Z] # Hostname but begin with a charachter.
		[\w\-]+ # Then followed by at least 1 other word char or hyphern.
		\w # And must then end with a alphanum, no hyphens.
	/xi; # Commented an insensitive, just like me.
}

sub ipaddr {
	my @numbers = split(/\./, $_[0]);

	foreach my $num ( @numbers ) {
		# Make sure the block is three numbers.
		if( $num !~ /^\d{1,3}$/o ) {
			return 0;
		}

		if( $num > 255 ) {
			return 0;
		}
	}

	return 1;
}

sub network_in_network {
	#FIXME: Will write this one later.
}

# This function actually pings the host to check if it's up.
sub ping {
	my $ip = shift;
	my $timeout = 3;
	
	if( ! netaddr($_[0]) ) {
		return 0;
	}

	eval 'use Net::Ping';
	my $pinger = Net::Ping->new("icmp");
	my $status = $pinger->ping($ip,$timeout);
	$pinger->close();

	return $status;
}

sub uint {
	my $data = shift;

	return $data =~ /^\d+$/;
}

# If it's empty it's false, it there's anythign it's true.. What's the problem?
sub boolean {
	return 1;
}

sub alters {
	my $data = shift;
	my @alters = @_;

	# Checks if data exists in the args passed.

	return grep {$_ eq $data} @_;
}

sub password {
	return username($_[0]);
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
