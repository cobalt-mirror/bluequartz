#
# $Id: Daemon.pm
#
# Client and server definition for an init script daemon to ensure
# init script runs don't overlap and end up killing off a service
# when trying to restart/reload the service many times during the same
# transaction.
#
# Designed with httpd in mind, but could work for any service provided
# the init script supports the status target.
# 

package Sauce::Service::Daemon;

# Debugging switch:
$DEBUG = "1";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use POSIX qw(setsid);
use Socket;
use IO::Socket;

my $SOCKET = '/usr/sausalito/init_daemon.socket';
my $TIMEOUT = 120;
# how long should children attempt an event before giving up in seconds
my $CHILD_TIMEOUT = 30;
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
            &_logmsg("sub run: Setting up alarm($QUEUE_CHECK_INTERVAL).");
            alarm($QUEUE_CHECK_INTERVAL);
        }
        $client->close();
    }

    exit(0);
}

sub _logmsg
{
    my $msg = shift;
    &debug_msg("$msg \n");
    #print LOG "[", time(), "] $msg\n";
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

sub _check_apache_state {

    # Traditional check polling the Init service:
    my $service = 'httpd';
    my $service_status = '0';
    $service_status = system("/sbin/service $service status") == 0 ? 1 : 0;

    # Early return, because: 'It's dead, Jim!'
    if ($service_status eq '0') {
        &_logmsg("_check_apache_state: Check #1: Init reports Apache is dead");
        return $service_status;
    }

    # Additional check by making a real HTTP request and polling the status code:
    use LWP::UserAgent;
    use HTTP::Request;
    my $ua = LWP::UserAgent->new;
    $ua->agent('BlueOnyx Sauce::Service::Daemon(_check_apache_state) - Apache Check');
    my $req = HTTP::Request->new(GET => 'http://127.0.0.1');
    my $response = $ua->request($req);
    if ($response->code ne '200') {
        # If status code is not '200' we have a problem - regardless of what Init says:
        $service_status = '0';
        # Early return, because: 'It's dead, Jim!'
        &_logmsg("_check_apache_state: Check #2: Cannot connect to Apache:80");
        return $service_status;
    }

    # Check how many Apache processes are currently attached around as 
    # primaries and not as children. There should be only one:
    $apache_state = `$ps axf|$grep /usr/sbin/httpd|$grep -v adm|$grep -v '\_'|$wc -l`;
    chomp($apache_state);

    ## Legend:
    #   0   Apache dead
    #   1   Apache probably running OK
    #  >1   Childs have detached (bad)
    &_logmsg("_check_apache_state: Final return: $apache_state");
    return $apache_state;
}

sub _kill_and_restart_apache {

    # See how Apache behaves currently:
    $xchecker = &_check_apache_state;
    ## Legend:
    #   0   Apache dead
    #   1   Apache probably running OK
    #  >1   Childs have detached (bad)

    &_logmsg("_kill_and_restart_apache: Apache state: $xchecker");

    if ($xchecker gt "1") {
        # Apache-Childs have detached from the master-process. Which is bad.
        # Kill httpd (but not AdmServ!):
        &_logmsg("xchecker reported: Killing $xchecker 'httpd' processes, but not killing admserv.\n");
        `$ps axf|$grep /usr/sbin/httpd|$grep -v adm|$grep -v grep|$grep -v '\_'|$awk -F ' ' '{print \$1}'|/usr/bin/xargs $kill -9 >&/dev/null`;

        # Perform restart action:
        if (-f "/usr/bin/systemctl") { 
            # Got Systemd: 
            # Please note: For httpd we do not use systemctl with the --no-block option to
            # enqueue the call. We issue it directly and wait for the result.
            &_logmsg("_kill_and_restart_apache runs: /usr/bin/systemctl --job-mode=flush restart httpd.service\n");
            `/usr/bin/systemctl --job-mode=flush restart httpd.service`; 
        } 
        else { 
            # Thank God, no Systemd: 
            &_logmsg("_kill_and_restart_apache runs: /sbin/service httpd restart");
            `/sbin/service httpd restart`;
        }
    }
    else {
        if (-f "/usr/bin/systemctl") { 
            $xchecker = &_check_apache_state;
            if ($xchecker eq "1") {
                &_logmsg("_kill_and_restart_apache runs: /usr/bin/pkill -HUP -x -f " . "/usr/sbin/httpd -DFOREGROUND");
                `$pkill -HUP -x -f "/usr/sbin/httpd -DFOREGROUND"`;
            }
            else {
                &_logmsg("_kill_and_restart_apache runs: /usr/bin/systemctl --job-mode=flush restart httpd.service\n");
                `/usr/bin/systemctl --job-mode=flush restart httpd.service`; 
            }
        }
        else {
            $xchecker = &_check_apache_state;
            if ($xchecker eq "1") {
                &_logmsg("_kill_and_restart_apache runs: /usr/bin/pkill -HUP -x -f /usr/sbin/httpd");
                `$pkill -HUP -x -f /usr/sbin/httpd`;
            }
            else {
                &_logmsg("_kill_and_restart_apache runs: /sbin/service httpd restart");
                `/sbin/service httpd restart`;
            }
        }
    }
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

    if (-f "/usr/bin/systemctl") { 
        $awk = '/usr/bin/awk';
        $kill = '/usr/bin/kill';
        $grep = '/usr/bin/grep';
        $ps = '/usr/bin/ps';
        $wc = '/usr/bin/wc';
        $pkill = '/usr/bin/pkill';
    }
    else {
        $awk = '/bin/awk';
        $kill = '/bin/kill';
        $grep = '/bin/grep';
        $ps = '/bin/ps';
        $wc = '/usr/bin/wc';
        $pkill = '/usr/bin/pkill';
    }

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

    &_logmsg("Performing event: $service $action");

    if ($service eq "httpd") {

        &_logmsg("Special case ($service): $action");

        # Kill and restart Apache:
        &_kill_and_restart_apache;

        # Check if that worked:
        $xchecker = &_check_apache_state;
        ## Legend:
        #   0   Apache dead
        #   1   Apache probably running OK
        #  >1   Childs have detached (bad)

        if ($xchecker ne "1") {
            &_logmsg("xchecker reported: $xchecker - Apache restart failed.");
        }
        else {
            &_logmsg("xchecker reported: $xchecker - Apache restart succeeded.");
        }
    }
    else {
        # Perform action:
        if (-f "/usr/bin/systemctl") { 
            # Got Systemd: 
            `/usr/bin/systemctl --job-mode=flush $action $service.service --no-block`;
        } 
        else { 
            # Thank God, no Systemd: 
            `/sbin/service $service $action`;
        }
    }

    for (my $i = 0;; $i++) {

        # Check if service is running:
        if ($service eq "httpd") {
            # See how Apache behaves currently:
            $checker = &_check_apache_state;
        }
        else {
            # Check if service is running:
            $checker = &_check_service($service);
        }
        if ($checker ne "1") {
            # Service is not running! Perform actions again:
            if ($service eq "httpd") {
                # Kill and restart Apache - again:
                &_kill_and_restart_apache;
            }
            else {
                # Perform action again for other services than 'httpd':
                if (-f "/usr/bin/systemctl") { 
                    # Got Systemd: 
                    #`/usr/bin/systemctl --job-mode=flush $action $service.service`;
                    `/usr/bin/systemctl $action $service.service`;
                } 
                else { 
                    # Thank God, no Systemd: 
                    `/sbin/service $service $action`;
                }
            }

            # Now take a final look and see if the service is running:
            if ($service eq "httpd") {
                # See how Apache behaves currently:
                $checker = &_check_apache_state;
            }
            else {
                # Check if service is running:
                $checker = &_check_service($service);
            }
        }

        &_logmsg("2:checker reports: $service " . $checker . "\n");

        if (($action eq 'stop') && ($checker == "0")) {
            last;
        }
        elsif (($action ne 'stop') && ($checker == "1")) {
            last;
        }
        sleep(1);

        # check for timeout
        if ($i >= $CHILD_TIMEOUT) {
            if ($service eq "httpd") {
                &_logmsg("child $$ unable to complete event $service $action. Running am_apache.sh and exiting");
                `/usr/sausalito/swatch/bin/am_apache.sh`;
            }
            elsif ($service eq "avspam") {
                &_logmsg("child $$ unable to complete event $service $action. Running /usr/sausalito/sbin/avspam_init.pl and exiting.");
                `/usr/sausalito/sbin/avspam_init.pl -restart`;
            }
            else {
                &_logmsg("child $$ unable to complete event $service $action. Throwing the towel and exiting.");
            }
            # notify parent
            kill 'USR2', getppid();
            exit(1);
        }
    }

    &_logmsg("child $$ finished $service $action");
    kill 'USR2', getppid();
    exit(0);
}

sub _check_service {
    my ($service) = @_;
    my $service_status = '';
    if (-f "/usr/bin/systemctl") { 
        # Got Systemd: 
        $service_status = `/usr/bin/systemctl status $service|$grep "Active:"|$grep -E "(running|exited)"|$wc -l`;
        chomp($service_status);
    }
    else {
        # Thank God, no Systemd: 
        $service_status = `/sbin/service $service status|$grep running|$wc -l`;
        chomp($service_status);
    }
    return $service_status;
}

sub _queue_event {
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

            # Check if service is running:
            $checker = &_check_service($service);

            if ($event_queue->{$key}->{state} && ($checker eq "0")) {

                # should be running, but isn't
                my $pid = &_spawn_child($key, 'start');
                &_logmsg("Inconsistent state.  spawned child $pid for $key start");

            }
            elsif (!$event_queue->{$key}->{state} && ($checker eq "1")) {
                # should not be running, but is
                my $pid = &_spawn_child($key, 'stop');
                &_logmsg("Inconsistent state.  spawned child $pid for $key stop");
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
        &_logmsg("ALRM_HANDLER: no more children, terminate.");
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
# Copyright (c) 2016-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016-2018 Team BlueOnyx, BLUEONYX.IT
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