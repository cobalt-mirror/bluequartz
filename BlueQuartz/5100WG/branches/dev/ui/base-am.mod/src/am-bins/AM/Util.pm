#!/usr/bin/perl -w
# $Id: Util.pm 201 2003-07-18 19:11:07Z will $
# author: thockin@cobalt.com

package AM::Util;

use strict;
use IO::Socket;
require Exporter;

use vars qw(@ISA @EXPORT);
@ISA = qw(Exporter);
@EXPORT = qw(
	am_get_statecodes 
	get_tcp_socket 
	get_udp_socket 
	get_sockaddr 
	my_system
);

# Read all the AM statecodes into a hash
# return the hash
sub am_get_statecodes
{
	# get the swatch statecodes
	open(STATES, "/usr/sausalito/swatch/statecodes") 
		|| die "can't open statecodes";

	my %am_states;
	while (defined($_ = <STATES>)) {
		if (/(AM_STATE_[A-Z]*)\s*=\s*(.*)/) {
			$am_states{$1} = $2;
		}
	}
	close(STATES);

	return %am_states;
}

# Return a handle to a socket
# Arguments:  host, port
sub get_tcp_socket
{
	my $host = shift || "localhost";
	my $port = shift || 80;

	my $sock;

	if ($port =~ /\D/) {
		$port = getservbyname($port, 'tcp');
	}

	$sock = new IO::Socket::INET->new(PeerAddr => $host,
		PeerPort => $port, Proto => 'tcp');

	return $sock;
}

# Fork and exec a process, closing file handles along the way
# Argument: the command to exec
sub my_system
{
	my $cmd = shift || "true";
	my $pid; 

	if ($pid = fork) {
		# parent
		wait;
		return $?
	} else {
		# child
		close(STDIN);	
		close(STDOUT);	

		open(STDIN, "</dev/null") || die "open: $!";
		open(STDOUT, ">/dev/null") || die "open: $!";

		exec($cmd);
		exit(42);
	}
}


# Return a handle to a socket
# Arguments: host, port
sub get_udp_socket
{
	my $host = shift || 'localhost';
	my $port = shift || 67;

	my $type = getprotobyname('udp');

	socket(SOCK, PF_INET, SOCK_DGRAM, $type);

	return *SOCK;
}

# Return a sockaddr_in
# Args: host, port
sub get_sockaddr
{
	my $host = shift || 'localhost';
	my $port = shift || 67;

	my $address = gethostbyname($host);
	my $sockaddr = sockaddr_in($port, $address);

	return $sockaddr;
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
