#!/usr/bin/perl

package Sauce::Service;
use vars qw(@ISA @EXPORT @EXPORT_OK);
require Exporter;
@ISA =    qw(Exporter);
@EXPORT = qw(service_get_init  service_set_init  service_run_init 
         service_toggle_init
         service_get_inetd service_set_inetd service_restart_inetd
         service_get_multi_inetd service_set_multi_inetd
         service_get_xinetd service_set_xinetd service_restart_xinetd
         service_get_multi_xinetd service_set_multi_xinetd
         service_send_signal 
        );

use lib '/usr/sausalito/perl';
use Sauce::Util;
use Sauce::Service::Client;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

1;

# PLEASE NOTE:
#
# For some reason we cannot use system("/sbin/service $service $arg") for
# actions on EL6. But we can use system("systemctl ...") on EL7. 
# Fun and games!

sub inetd_conf { return '/etc/inetd.conf' };
sub inetd_perm { return 0644; };

sub xinetd_conf { return '/etc/xinetd.conf' };
sub xinetd_conf_dir { return '/etc/xinetd.d' };
sub xinetd_perm { return 0644; };

sub service_run_init
# changes the init state. it does this in the background by default
# arguments: file, arguments ('start', 'stop', or 'restart') and
# optionally 'nobg' as third parameter to not shoot the call into 
# the background.
{
    my ($service, $arg, $options) = @_;
    my $pid;

    #
    # only do this for httpd for now.  I know this is a hack, but
    # better this than breaking every service restart
    #
    # check httpd is running $arg is reload
    #
    $pidHttpd = `pidof httpd|wc -l`;
    chomp($pidHttpd);
    if (($service eq 'httpd') && ($arg eq 'reload')) {
        &debug_msg("Special case: $service $arg"); 
        my $ssc = new Sauce::Service::Client;
        if (!$ssc->connect()) {
            return(0);
        }
        if (!$ssc->register_event($service, $arg)) {
            return(0);
        }
        if (!$ssc->bye()) {
            # this can actually never fail currently
            return(0);
        }
        return(1);
    }

    #
    ### Special case AV-SPAM restart:
    #
    if (($service eq 'avspam') && (-f "/usr/sausalito/sbin/avspam_init.pl") && ($arg eq 'restart')) {
        &debug_msg("Special case: $service $arg"); 
        my $ssc = new Sauce::Service::Client;
        if (!$ssc->connect()) {
            return(0);
        }
        if (!$ssc->register_event($service, $arg)) {
            return(0);
        }
        if (!$ssc->bye()) {
            # this can actually never fail currently
            return(0);
        }
        return(1);
    }

    if ($service eq 'crond') {
        `killall -9 crond`;
        # Restarts Service:
        if (-f "/usr/bin/systemctl") { 
            # Got Systemd: 
            system("systemctl $arg $service.service --no-block"); 
        } 
        else { 
            # Thank God, no Systemd: 
            $wtf = `/sbin/service $service $arg`;
            chomp($wtf);
            &debug_msg("Result: $wtf"); 
        }
        return(0);
    }
    unless ($options =~ /\bnobg\b/) {
    
        if ($pid = fork()) {
            waitpid($pid, 0);
            # Success, whether it really worked or not...
            return 1;
        }
        close(STDIN);
        close(STDOUT);
        close(STDERR);
        exit 0 if fork();
    }
        
    if ($options =~ /\bnobg\b/)
    {
        # Restarts Service:
        if (-f "/usr/bin/systemctl") { 
            # Got Systemd: 
            &debug_msg("Running: systemctl $arg $service.service"); 
            system("systemctl $arg $service.service"); 
        } 
        else { 
            # Thank God, no Systemd: 
            &debug_msg("Running: /sbin/service $service $arg"); 
            $wtf = `/sbin/service $service $arg`;
            chomp($wtf);
            &debug_msg("Result: $wtf"); 
        }
        # Return 1 on success instead of the standard unix command 0
        if ($? == 0) {
            return 1;
        }
        return 0;
    }
    else
    {
        # Restarts Service:
        if (-f "/usr/bin/systemctl") { 
            # Got Systemd: 
            &debug_msg("Running: systemctl $arg $service.service --no-block"); 
            system("systemctl $arg $service.service --no-block");
        } 
        else { 
            # Thank God, no Systemd: 
            &debug_msg("Running: /sbin/service $service $arg"); 
            $wtf = `/sbin/service $service $arg`;
            chomp($wtf);
            &debug_msg("Result: $wtf"); 
        }
    }
    
    exit 0 unless ($options =~ /\bnobg\b/);
}

sub service_toggle_init
# toggle the service
# arguments: $service, $newstate
{
    my ($service, $new, $options) = @_;
    if (($new eq "1") || ($new eq "on")) {
        &debug_msg("Running: service_set_init($service, 'on')"); 
        service_set_init($service, 'on');
        &debug_msg("Running: service_run_init($service, 'restart', $options)"); 
        service_run_init($service, 'restart', $options);
    } else {
        &debug_msg("Running: service_set_init($service, 'off')"); 
        service_set_init($service, 'off');
        &debug_msg("Running: service_run_init($service, 'stop', $options)"); 
        service_run_init($service, 'stop', $options);
    }
}


sub service_get_init
# get the state of the service in the given runlevel
# arguments: state, runlevel (defaults to 3 if not specified)
{
    my ($service, $state) = @_;
    $state ||= 3;
    my $return = -1;

    if (-f "/usr/bin/systemctl") {
        # Got Systemd:
        my $status = `/usr/bin/systemctl is-enabled $service`;
        if ($status) {
            $return = ($status =~ /^enabled/) ? 1 : 0;
        }
    }
    else {
        # Thank God, no Systemd:
        my $status = `/sbin/chkconfig --list $service`;
        if ($status) {
            $return = ($status =~ /\b$state:on\b/) ? 1 : 0;
        }
    }
    return $return;
}

sub service_set_init
# set the given service state to on or off
# arguments: service name, state, list of runlevels
{
    my ($service, $state, @runlevels) = @_;
    my $level;

    if ($state eq "1") {
        $state = 'on';
    }
    if ($state eq "0") {
        $state = 'off';
    }

    if (@runlevels) {
        $level = ' --level ';
        $level .= join('',@runlevels);
        $state = 'off' unless $state eq 'on';

        # Define Systemd state:
        my $SystemdState = 'disable';
        if ($state eq "on") {
            $SystemdState = 'enable';
        }

        # Set state:
        if (-f "/usr/bin/systemctl") {
            &debug_msg("1. Running: /usr/bin/systemctl $SystemdState $service.service"); 
            `/usr/bin/systemctl $SystemdState $service.service`;
        }
        else {
            &debug_msg("1. Running: /sbin/chkconfig $level $service $state"); 
            `/sbin/chkconfig $level $service $state`;
        }
    } else {
        if (service_get_init($service) == -1) {
            # Set state:
            if (-f "/usr/bin/systemctl") {
                &debug_msg("2. Running: /usr/bin/systemctl enable $service.service"); 
                `/usr/bin/systemctl enable $service.service`;
            }
            else {
                &debug_msg("2. Running: /sbin/chkconfig --add $service");
                `/sbin/chkconfig --add $service`;
            }
        }

        # Define Systemd state:
        my $SystemdXState = 'disable';
        my $cmd = 'off';
        if ($state eq "on") {
            $SystemdXState = 'enable';
            $cmd = 'on';
        }

        # Set state:
        if (-f "/usr/bin/systemctl") {
            &debug_msg("3. Running: /usr/bin/systemctl $SystemdXState $service.service");
            `/usr/bin/systemctl $SystemdXState $service.service`;
        }
        else {
            &debug_msg("3. Running: /sbin/chkconfig $service $cmd");
            `/sbin/chkconfig $service $cmd`;
        }
    }

    #
    # chkconfig returns 0 on success, while this routine should return
    # 1 (the perl standard)
    #
    if ($? == 0) {
        return 1;
    }
    return 0;
}

sub service_get_inetd
# get the state of the service in inetd.conf
# arguments: service
{
    my $service = shift;

    open(INETD, inetd_conf());
    while (<INETD>) {
        next unless /\s*(\#*)\s*$service\s/;
        close(INETD);
        return $1 =~ /\#/ ? 0 : 'on';
    }
    close(INETD);
}

sub service_get_multi_inetd
# get the state of the service in inetd.conf
# arguments: list of settings
# returns hash of settings/values
{
    my @list = @_;
    my $conf = inetd_conf();
    my $services = ',' . join(',', @list) . ',';
    my ($set, $service, %settings);

    open(INETD, $conf);
    while (<INETD>) {
        next unless /\s*(\#*)\s*(\S+)\s/;
        ($set, $service) = ($1, $2);
            next unless $services =~ /,$service,/;
        $settings{$service} = ($set =~ /\#/ ? 0 : 'on') unless $settings{$service};
    }
    close(INETD);

    return %settings;
}

sub _edit_inetd
{
    my ($input, $output, $service, $enabled, $rate) = @_;
    while (<$input>) {
        if (/^[\s\#]*($service\s.*)/) {
            my $service_record = $1;
            $service_record =~ s/wait(\.*\d*)(\s)/wait\.$rate$2/ if ($rate);

            print $output ($enabled eq 'on') ? $service_record : "# $service_record";
            print $output "\n";
            next;
        }
        print $output $_;
    }
    return 1;
}
    
sub _edit_multi_inetd
{
    my ($input, $output, %settings) = @_;
    my $services = ',' . join(',', keys %settings) . ',';
    my ($service, $rest);
    
    while (<$input>) {
        if (/^[\s\#]*(\S+)(\s.*)/) {
               ($service, $rest) = ($1, $2);
               if ($services =~ /,$service,/) {
               print $output ($settings{$service} eq 'on') ? 
                   "$service$rest" : "# $service$rest";  
               print $output "\n";
               next;
               }
            }
        print $output $_;
    }
    return 1;
}
    
sub service_set_inetd
# sets the state of the service in inetd.conf
# arguments: service, state
{
    my ($service, $state, $rate) = @_;

    my $ret = Sauce::Util::editfile(inetd_conf(), *_edit_inetd, $service, $state, $rate);
    chmod(inetd_perm(), inetd_conf());
    return $ret;
}

sub service_set_multi_inetd
# set the state of the service in inetd.conf
# arguments: hash of services/settings
{
    my $ret = Sauce::Util::editfile(inetd_conf(), *_edit_multi_inetd, @_);
    chmod(inetd_perm(), inetd_conf());
    return $ret;
}


sub service_send_signal
# send a signal to a process
{
    my ($service, $signal) = @_;
    $signal =~ tr/[a-z]/[A-Z]/;
    `killall -$signal $service`;
}

sub service_restart_inetd
# send sighup to inetd so it rereads /etc/inetd.conf
# this is a backwards compatibility routine. 
{
    service_send_signal('inetd', 'HUP');
}


## functions for xinetd
## Auther: Hisao SHIBUYA <shibuya@alpha.or.jp>
# service_get_xinetd, service_get_multi_xinetd, _edit_xinetd
# service_set_xinetd, service_set_multi_xinetd, service_restart_xinetd

sub service_get_xinetd
# get the state of the service in inetd.conf
# arguments: service
{
    my $service = shift;
    my $conf_file = xinetd_conf_dir() . "/$service";
 
    open(SERVICE, $conf_file);
    while (<SERVICE>) {
        next unless /^\s*(disable)\s/;
        close(SERVICE);
        return $_ =~ /yes/ ? 0 : 'on';
    }
    close(SERVICE);
}
 
sub service_get_multi_xinetd
# get the state of the service in xinetd.d/*
# arguments: list of settings
# returns hash of settings/values
{
        my @list = @_;
        my ($service,%settings);
 
        for ($i=0; $i<@list; $i++) {
                $service = $list[$i];
                $settings{$service} = service_get_xinetd($service);
        }
 
        return %settings;
}
 
sub _edit_xinetd
{
        my ($input, $output, $service, $enabled, $rate) = @_;
        my $instances_done = 'false';

        while (<$input>) {
            if (/^(\s*disable\s.*=)\s/) {
                print $output ($enabled eq 'on') ? "$1 no" : "$1 yes";
                print $output "\n";
                next;
            }
            if (/^(\s*instances\s.*=)\s/ && $rate ne '') {
                print $output "$1 $rate";
                print $output "\n";
                $instances_done = 'true';
                next;
            }
            if (/^}$/ && $instances_done eq 'false' && $rate ne '') {
                print $output "\tinstances = $rate";
                print $output "\n}\n";
                next;
            }
            print $output $_;
        }
        return 1;
}
 
sub service_set_xinetd
# set the state of the service in xinetd.d/*
# arguments: service, state
{
        my ($service, $state, $rate) = @_;
        my $conf_file = xinetd_conf_dir() . "/$service";
        my $ret = Sauce::Util::editfile($conf_file, *_edit_xinetd, $service, $state, $rate);
        chmod(xinetd_perm(), $conf_file);
        return $ret;
}
 
sub service_set_multi_xinetd
# set the state of the service in xinetd.d/*
# arguments: hash of services/settings
{
        my %settings = @_;
 
        foreach my $key (keys %settings) {
                my $conf_file = xinetd_conf_dir() . "/$key";
                my $ret = Sauce::Util::editfile($conf_file, *_edit_xinetd, $key, $settings{$key});
                chmod(xinetd_perm(), $conf_file);
        }
        return 0;
}
 
sub service_restart_xinetd
# send sighup to inetd so it rereads /etc/inetd.d/*
# this is a backwards compatibility routine.
{
        service_send_signal('xinetd', 'HUP');
}

# Debug:
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