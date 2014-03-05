#!/usr/bin/perl
# $Id: pperld.pl
# A persistant perl process to amortize the overhead of many perl 
# scripts listens on UNIX domain socket
# jmayer, hacked by thockin

$0 = "pperld";

# use common libs here
use lib qw( /usr/sausalito/perl );
use lib qw( . );
use strict;		# turned on for development
use warnings;		# turned on for development
use IO::Socket;
use CCE;
use Sauce::Util;
use Sauce::Service;
use FileHandle;
use DirHandle;
use POSIX;
use Fcntl;
use Data::Dumper;

# enable or disable debugging messages
my $debug = 0;

my $magic_file_name = "pperl.socket";
my $alarmtime = 300;

#
# main
#

# get out of the way
#chdir("/tmp");
setpgrp();
close(STDIN);
close(STDOUT);
open(STDIN, "</dev/null");
open(STDOUT, ">/dev/null");

# handle signals
$SIG{CHLD} = 'IGNORE'; # perl will auto-reap if SIGCHLD is IGNORE'd
$SIG{INT} = \&KILLHANDLE;
$SIG{TERM} = \&KILLHANDLE;
$SIG{QUIT} = \&KILLHANDLE;
$SIG{ALRM} = \&ALARM;

# get the UNIX domain socket
unlink($magic_file_name);
my $server = new IO::Socket::UNIX (
	Local => $magic_file_name,
	Type => SOCK_STREAM,
	Listen => 5) || die "$!";

# set the alarm 
alarm($alarmtime);

# loop for connections
my $pid;
while (my $connection = $server->accept()) {
	$debug && print STDERR "DEBUG $$: got a connection\n";

	# reset the alarm - 5 minutes
	alarm($alarmtime);

	$pid = fork();
	if ($pid == 0) {
		# child - become owned by init
		if (fork()) {
			exit(0);
		}
		$server->close();
		$SIG{CHLD} = 'DEFAULT';
		$SIG{INT} = 'DEFAULT';
		$SIG{TERM} = 'DEFAULT';
		$SIG{QUIT} = 'DEFAULT';
		$SIG{ALRM} = 'DEFAULT';
		alarm(0);
		handle_connection($connection);
		# never returns...
	} elsif ($pid < 0) {
		# error
		print STDERR "$0 fork: $!\n";
	}
	waitpid($pid, 0);
	$connection->close();
}
exit 0;

sub handle_connection
{
	my $con = shift;

	# detach
	setpgrp();

	# get peer credentials
	my $cred = $con->sockopt(17);
	my ($other_pid, $other_uid, $other_gid) = unpack("ISS",$cred);
	$debug && print STDERR "DEBUG $$: connection uid=$other_uid\n";
  
	$|=1; # set output to non-buffered
	dup2($con->fileno, 0) || die "$!";
	dup2($con->fileno, 1) || die "$!";
	$con->close();

	# test access rights
	if ($other_uid != $< && $other_uid != $> && $other_uid != 0) {
		print STDOUT "Access denied\n";
		exit(1);
	}

	my $fname = <STDIN>;
	chomp($fname);
	$debug && print STDERR "DEBUG $$: running $fname\n";

	# add the handler's dir to @INC
	my $dirname;
	{
		my @parts = split(/\/+/, $fname);
		pop(@parts);
		$dirname = join("/", @parts);
	}
	if (-d $dirname) { 
		push(@INC, $dirname); 
	}

	# run it
	$0 = "pperld $fname";
	my $r = do $fname;
	print STDERR "$@\n" if $@;
	print STDERR "$!\n" unless defined($r);
	$debug && print STDERR "DEBUG $$: done running\n";

	exit((defined($r) ? $r : 1));
}

sub KILLHANDLE {
	$debug && print STDERR "DEBUG $$: caught SIG(TERM,INT,QUIT)\n";
	unlink($magic_file_name);
	exit(0);
}

sub ALARM {
	$debug && print STDERR "DEBUG $$: caught SIGALRM\n";
	unlink($magic_file_name);
	exit(0);
}

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
