#!/usr/bin/perl -w
# $Id: Validators.pm
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
# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 