#
# $Id: Client.pm,v 1.1.2.1 2002/04/05 09:48:49 pbaltz Exp $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# client API for the Sauce::Service::Daemon just allows you to talk to
# the daemon programmatically
#

package Sauce::Service::Client;

use lib qw(/usr/sausalito/perl);
use Socket;
use IO::Socket::UNIX;
use Sauce::Service::Daemon;

my $SOCKET = '/usr/sausalito/init_daemon.socket';

sub new
{
	my $self = shift;
	my $class = ref($self) || $self;
	$self = bless({}, $class);
	$self->init(@_);
	return $self;
}

sub init
{
	my ($self, @args) = @_;

	$self->{connected} = 0;
}

sub connect
{
	my ($self, @args) = @_;

	my $sock = new IO::Socket::UNIX('Type' => SOCK_STREAM,
					'Peer' => $SOCKET);
	if (!$sock) {
		# can't connect, try to start up the daemon
		my $pid = fork();
		if (!defined($pid)) {
			die("Can't fork: $!\n");
		} elsif ($pid == 0) {
			$SIG{CHLD} = 'DEFAULT';
			my $daemon = new Sauce::Service::Daemon();
			# this never returns
			$daemon->run();
		} else {
			waitpid($pid, 0);
		}

		# try to connect again
		$sock = new IO::Socket::UNIX('Type' => SOCK_STREAM,
					     'Peer' => $SOCKET);
		if (!$sock) {
			# nothing else we can do
			return(0);
		}
	}

	$self->{connected} = 1;
	$self->{rdsock} = $sock;
	$self->{wrsock} = $sock;

	return(1);
}

sub register_event
{
	my ($self, $service, $event) = @_;
	
	if ($service eq '' || $event eq '') {
		# what the heck am I supposed to do with that
		return(0);
	}

	my $wrsock = $self->{wrsock};
	my $rdsock = $self->{rdsock};

	print $wrsock "$service $event\n";

	my $response = <$rdsock>;
	if ($response =~ /OK$/) {
		return(1);
	}

	return(0);
}

# bye is only here for future expansion
sub bye
{
	my ($self, @args) = @_;

	close($self->{rdsock});

	# nothing important to do, so just return true
	return(1);
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
