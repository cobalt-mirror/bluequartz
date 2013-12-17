#
# $Id: Daemon.pm,v 1.1.2.1 2002/04/05 09:48:49 pbaltz Exp $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# client and server definition for an init script daemon to ensure
# init script runs don't overlap and end up killing off a service
# when trying to restart/reload the service many times during the same
# transaction.
#
# Designed with httpd in mind, but could work for any service provided
# the init script supports the status target
# (ie /etc/rc.d/init.d/service status)
# 

package Sauce::Service::Daemon;

use POSIX qw(setsid);
use Socket;
use IO::Socket;

my $SOCKET = '/usr/sausalito/init_daemon.socket';
my $TIMEOUT = 120;
# how long should children attempt an event before giving up in seconds
my $CHILD_TIMEOUT = 10;
my $QUEUE_CHECK_INTERVAL = 5;
my $INIT_DIR = '/etc/rc.d/init.d';
my $last_event_time;

my $client;
my $event_queue;
my $children;
my $child_map;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = bless({}, $class);
	$self->init(@_);
	return $self;
}

sub init
{
	my ($self, @args) = @_;

	unlink($SOCKET);
	my $sock = new IO::Socket::UNIX('Type' => SOCK_STREAM,
					'Local' => $SOCKET,
					'Listen' => SOMAXCONN,
					'Reuse' => 1);
	if (!$sock) {
		die("Can't create socket: $!\n");
	}
	
	# set options
	$sock->sockopt(16, 1);

	$self->{rdsock} = $sock;
	$self->{wrsock} = $sock;

	$event_queue = {};
	$child_map = {};
	$children = 0;
}

sub run
{
	my ($self, @args) = @_;

	chmod(0600, $SOCKET);
	
	my $ret = 0;
	$self->_daemonize();

	# set the path
	$ENV{PATH} = "$INIT_DIR";

	# setup signals
	$SIG{USR2} = \&CHLD_HANDLER;
	$SIG{ALRM} = \&ALRM_HANDLER;
	$SIG{INT} = \&KILL_HANDLER;
	$SIG{TERM} = \&KILL_HANDLER;
	$SIG{QUIT} = \&KILL_HANDLER;


	alarm($TIMEOUT);

	&_logmsg("${0}[${$}] ready to accept requests");

	my $events_running = 0;

	while (1) {
		$client = $self->{rdsock}->accept();
		if ($client) {
			my $data = <$client>;
			print $client "OK\n";
			
			# handle the request
			chomp($data);
			my ($service, $action) = split(/\s+/, $data);
			$self->_queue_event($service, $action);
		}

		if (!$events_running) {
			$events_running = 1;
			&_check_queue();

			# set the queue check alarm
			alarm($QUEUE_CHECK_INTERVAL);
		}
		$client->close();
	}

	exit(0);
}

sub _logmsg
{
	my $msg = shift;
	print LOG "[", time(), "] $msg\n";
}

sub _daemonize
{
	my ($self) = @_;

	my $pid = fork();
	if (!defined($pid)) {
		die("Fork failed: $!\n");
	} elsif ($pid != 0) {
		# parent can just
		exit(0);
	}

	# start a new session
	setpgrp();

	# change names, so we can find this
	$0 = 'sauce_serviced';

	# close file handles
	close(STDIN);
	close(STDERR);
	close(STDOUT);
	open(LOG, ">/dev/null");
	my $oldfh = select(LOG);
	$| = 1;
	select($oldfh);

	chdir('/');
}

sub _spawn_child
{
	my ($service, $action, $command) = @_;

	my $pid = fork();
	if (!defined($pid)) {
		die "fork failed!!!";
	} elsif ($pid != 0) {
		# started the child fine, update our scoreboard
		$children++;
		$child_map->{$pid} = $service;
		return $pid;
	}

	# clean up signals
	$SIG{USR2} = 'DEFAULT';
	$SIG{CHLD} = 'DEFAULT';
	$SIG{ALRM} = 'DEFAULT';
	alarm(0);

	# check credentials of person connecting
	my $cred = $client->sockopt(17);
	my ($other_pid, $other_uid, $other_gid) = unpack('ISS', $cred);
	if ($other_uid != $< && $other_uid != $> && $other_uid != 0) {
		# access denied
		&_logmsg("Access denied in child $$");
		kill 'USR2', getppid();
		exit(1);
	}
	$client->close();

	# check if this action needs to be upgraded to a start
	if (($action ne 'stop') && ($action ne 'start') &&
	    (system("/etc/rc.d/init.d/$service", 'status') != 0)) {
		#
		# not currently running, upgrade to a start 
		#
		&_logmsg("child, $$, upgrading $action to start");
		$action = 'start';
	}

	system("/etc/rc.d/init.d/$service", $action);

	for (my $i = 0;; $i++) {
		if (($action eq 'stop') &&
		    (system("/etc/rc.d/init.d/$service", 'status') != 0)) {
			last;
		} elsif (($action ne 'stop') &&
			 (system("/etc/rc.d/init.d/$service", 'status') == 0)) {
			last;
		}
		sleep(1);

		# check for timeout
		if ($i >= $CHILD_TIMEOUT) {
			&_logmsg("child $$ unable to complete event $service ".
				 "$action, exiting");
			# notify parent
			kill 'USR2', getppid();
			exit(1);
		}
	}
	
	&_logmsg("child $$ finished $service $action");
	kill 'USR2', getppid();
	exit(0);
}

sub _queue_event
{
	my ($self, $service, $event) = @_;

	# store the time of the last event	
	$last_event_time = time();

	my $pending = $event_queue->{$service}->{events}->[0]; 
	#
	# see if we keep the current event or use the new one
	# everything overrides stop
	# reload can only override stop
	#
	if (($pending ne '') && ($pending ne 'stop') && ($event eq 'reload')) {
		# reload can't override anything but stop
		&_logmsg("$event cannot override $pending");
		$event = $pending;
	} else {
		&_logmsg("$event overrides $pending");
	}

	# most recent event is the one to use
	$event_queue->{$service}->{events}->[0] = $event;
	&_logmsg("got event $event");
}

sub _check_queue
{
	for my $key (keys(%{ $event_queue })) {
		if (!$event_queue->{$key}->{busy} &&
		    (scalar(@{ $event_queue->{$key}->{events} }) > 0)) {
			$event_queue->{$key}->{busy} = 1;
			my $event = shift(@{ $event_queue->{$key}->{events} });
			
			#
			# update the state for this service
			# 1 for running, 0 for stopped
			# this state should match reality after all the
			# events have been processed.
			#
			if ($event eq 'stop') {
				$event_queue->{$key}->{state} = 0;
			} else {
				$event_queue->{$key}->{state} = 1;
			}

			my $pid = &_spawn_child($key, $event);

			&_logmsg("spawned child $pid for $key $event");

		} elsif (!$event_queue->{$key}->{busy} &&
			 exists($event_queue->{$key}->{state})) {
			# verify that the service is in the correct state
			if ($event_queue->{$key}->{state} &&
			    (system("/etc/rc.d/init.d/$key", 'status') != 0)) {

				# should be running, but isn't
				my $pid = &_spawn_child($key, 'start');
				&_logmsg("Inconsistent state.  spawned child " .
					 "$pid for $key start");

			} elsif (!$event_queue->{$key}->{state} &&
				 (system("/etc/rc.d/init.d/$key", 'status') == 0)) {
				# should not be running, but is
				my $pid = &_spawn_child($key, 'stop');
				&_logmsg("Inconsistent state.  spawned child " .
					 "$pid for $key stop");
			}
		}
	}
}

sub CHLD_HANDLER
{
	my $waited_pid = wait;

	&_logmsg("received SIGUSR2 for $waited_pid");

	my $service = $child_map->{$waited_pid};
	$event_queue->{$service}->{busy} = 0;
	$SIG{USR2} = \&CHLD_HANDLER;
	delete($child_map->{$waited_pid});
	$children--;
}

sub ALRM_HANDLER
{
	$SIG{ALRM} = \&ALRM_HANDLER;
	&_check_queue();
	alarm($QUEUE_CHECK_INTERVAL);
	if ($children == 0) {
		&terminate(0);
	}
}

sub KILL_HANDLER
{
	# terminate and really die
	&terminate(1);
}

sub terminate
{
	my $force_die = shift;

	if ($force_die || (time() >= ($last_event_time + $TIMEOUT))) {
		unlink($SOCKET);
		&_logmsg("${0}[${$}] exiting.");
		exit(0);
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
