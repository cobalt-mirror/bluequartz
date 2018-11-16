#
# $Id: Client.pm
#
# client API for the Sauce::Service::Daemon just allows you to talk to
# the daemon programmatically
#

package Sauce::Service::Client;

# Debugging switch:
$DEBUG = "1";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

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

    my $sock = new IO::Socket::UNIX('Type' => SOCK_STREAM, 'Peer' => $SOCKET);
    if (!$sock) {
        # can't connect, try to start up the daemon
        my $pid = fork();
        if (!defined($pid)) {
            die("Can't fork: $!\n");
        }
        elsif ($pid == 0) {
            $SIG{CHLD} = 'DEFAULT';
            my $daemon = new Sauce::Service::Daemon();
            # this never returns
            $daemon->run();
        }
        else {
            waitpid($pid, 0);
        }

        # try to connect again
        $sock = new IO::Socket::UNIX('Type' => SOCK_STREAM, 'Peer' => $SOCKET);
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

    &debug_msg("Event registered: $service $event \n");

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

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

1;

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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