#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: Arkeia.pm,v 1.4.2.1 2002/03/15 02:51:52 uzi Exp $
#
# Copyright 2001 Sun Microsystems, Inc., All rights reserved.
#

package Arkeia;

use Data::Dumper;
use Sauce::Util;
use Sauce::Service;
use Socket qw(AF_INET PF_INET SOCK_STREAM);

my $arkeia_service	= 'cobalt-arkeia';
my $arkeia_serverconfig	= '/usr/knox/nlp/admin.cfg';
my $arkeia_netconfig	= '/usr/knox/nlp/nlp.cfg';

sub arkeia_checkparameters
{
	my ($cce, $obj) = @_;

	my $DEBUG = 0;

	# Check the parameters and make sure they are valid

	#
	# Make sure the server parameter exists and has a valid host name
	#
	if (! defined($obj->{'server'}) || length($obj->{'server'}) == 0) {
		#
		# This is invalid if the module is enabled.  Otherwise we allow
		# an empty server field.
		#
		if ($obj->{'enabled'} == 1) {
			$DEBUG && warn 'Server ' . $obj->{'server'} .
			    ' is empty.' . "\n";
			$cce->baddata($oid, 'server',
			    '[[knox-arkeia.server_empty]]');
			return 0;
		}
	} else {
		# Make sure the server are set in DNS correctly
		if (Arkeia::arkeia_dns_verifyhost($obj->{'server'}) == 0) {
			# The server is invalid.
			$DEBUG && warn 'Server ' . $obj->{'server'} .
			    ' is invalid due to DNS.' . "\n";
			$cce->baddata($oid, 'server',
			    '[[knox-arkeia.server_invalid]]');
			return 0;
		}
	}

	# Make sure the port number is an integer from 0-65535
	if (! defined($obj->{'port'}) || length($obj->{'port'}) == 0) {
		# An empty port number is always invalid
		$cce->baddata($oid, 'port', '[[knox-arkeia.port_empty]]');
		return 0;
	} elsif (($obj->{'port'} !~ /^\s*[0-9]{1,5}\s*$/) ||
	         (($obj->{'port'} < 0) || ($obj->{'port'} > 65535))) {
		 # Invalid port number
		$DEBUG && warn 'Port ' . $obj->{'port'} . ' is invalid. ' .
		    "\n";
		$cce->baddata($oid, 'port', '[[knox-arkeia.port_invalid]]');
		return 0;
	}

	return 1;
}


# Description: convert the given ip address into a list of hostnames
# Input: ip address string in dot separated notation (e.g. 127.0.0.1)
# Output: hostname
sub arkeia_dns_gethostbyaddr
{
	my ($ipstr) = @_;
	my ($ip, $name);

	# Convert ip string to a packed network address and convert
	$ip = pack('C4', split /\./, $ipstr);
	$name = gethostbyaddr($ip, AF_INET);

	# Return the name, regardless.  It will be empty if there was no result
	return ($name);
}


# Description: convert the given argument into a list of IP addresses
# Input: host
# Output: list containing:
#       $h_name                The official name of the host
#       $h_aliases        A list of alternative names for the host
#       $h_addrtype        The type of address; AF_INET
#       $h_length        The length of the address in bytes
#       @h_addrlist        The list of network addresses for the host
#
sub arkeia_dns_gethostbyname
{
	my ($host) = @_;
	my ($ip, $ipstr, @ipaddrs);
	my ($name, $aliases, $addrtype, $length, @addrs) =
	    gethostbyname($host);
	if (length($host) == 0) {
		return 0;
	}

	foreach $ip (@addrs) {
		# Convert the binary ip addresses to dot separated notation
		$ipstr = join ".", unpack('C4', $ip);
		push @ipaddrs, ($ipstr);
	}

	return ($name, $aliases, $addrtype, $length, @ipaddrs);
}


# Description: verify that forward and reverse DNS lookups work for a host
# Input: ip address
# Output: 0 == failure, 1 == success
sub arkeia_dns_verifyhost
{
	my ($hostname) = @_;

	# If we have an IP addr, convert it to a hostname first
	$hostname = arkeia_dns_gethostbyaddr($hostname) if
		$hostname =~ /\s*\d+\.\d+\.\d+\.\d+\s*/;

	# Perform forward lookups for this host
	my ($name, $aliases, $addrtype, $length, @addrs) =
	    Arkeia::arkeia_dns_gethostbyname($hostname);
	if (length($name) == 0) {
		return 0;
	}

	# Verify that each IP address converts to either the hostname or
	# one of it's aliases
	foreach $ipaddr (@addrs) {
		($host) = Arkeia::arkeia_dns_gethostbyaddr($ipaddr);
		if (length($host) == 0) {
			# Get host by addr failed
			return 0;
		}
		if (($host ne $name) && (index($aliases, $host) < 0)) {
			# Reverse lookup failed
			return 0;
		}
	}

	return 1;
}

sub arkeia_saveconfig
{
	my ($cce, $obj) = @_;
	my $state;
	my $status;
	my $errstr;
	my $ok;
	my $DEBUG = 0;

	$DEBUG && warn 'Storing the object data: ' . Dumper($obj) . "\n";

	# Stop the service before doing anything else.
	$ok = service_run_init($arkeia_service, 'stop', '');
	if (! $ok) {
		$DEBUG && warn 'Arkeia run state change failed.' . "\n";
		$cce->bye('FAIL', '[[knox-arkeia.stop_daemon_failed]]');
		return $ok;
	}

	# Write the backup server to the server configuration file
	$DEBUG && warn 'Modifying ' . $arkeia_serverconfig . "\n";
	$ok = Sauce::Util::editfile($arkeia_serverconfig,
	    *Arkeia::arkeia_editserver, $obj->{'server'});
	if (! $ok) {
		$cce->bye('FAIL', '[[knox-arkeia.set_server_failed]]');
		return $ok;
	}

	# Editing worked.  Now save the new port information
	$DEBUG && warn 'Modifying ' . $arkeia_netconfig . "\n";
	$ok = Sauce::Util::editfile($arkeia_netconfig,
	    *Arkeia::arkeia_editport, $obj->{'port'});
	if (! $ok) {
		$cce->bye('FAIL', '[[knox-arkeia.set_port_failed]]');
		return $ok;
	}

	# That worked, too.  Now set the new service state
	if ($obj->{'enabled'} == 1) {
		$state = 'on';
		$status = 'start';
		$errstr = '[[knox-arkeia.start_daemon_failed]]';
	} else {
		$state = 'off';
		$status = 'stop';
		$errstr = '[[knox-arkeia.stop_daemon_failed]]';
	}

	$ok = service_set_init($arkeia_service, $state, (3));
	if (! $ok) {
		#
		# service_set_init returns 0 on success and 1 on failure (the
		# result of calling chkconfig)
		#
		$DEBUG && warn 'Arkeia init state change failed.' . "\n";
		$cce->bye('FAIL', $errstr);
		return $ok;
	}

	$ok = service_run_init($arkeia_service, $status, '');
	if (! $ok) {
		$DEBUG && warn 'Arkeia run state change failed.' . "\n";
		$cce->bye('FAIL', $errstr);
		return $ok;
	}


	return $ok
}


sub arkeia_editport
{
	my ($fin, $fout, $port) = @_;
	my $result = 1;
	my $DEBUG = 0;
	$DEBUG && warn 'arkeia_editport using parameters ' . Dumper(@_) . "\n";
	while (<$fin>) {
		# Echo the input to the output
		if (/PORT_NUMBER/) {
			print $fout "PORT_NUMBER \"$port\"\n";
			$done = 1;
		} else {
			print $fout $_;
		}
	}

	unless ($done) {
		$result = print $fout "PORT_NUMBER \"$port\"\n";
	}
	return ($result);
}

sub arkeia_editserver
{
	my ($fin, $fout, $hostname) = @_;
	my $DEBUG = 0;
	$DEBUG && warn 'arkeia_editserver using parameters ' . Dumper(@_) .
	    "\n";

	my $result = print $fout "$hostname\n";
	return ($result);
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
