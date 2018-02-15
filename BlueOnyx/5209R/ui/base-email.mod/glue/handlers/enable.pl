#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email/
# $Id: enable.pl

use Sauce::Service;
use CCE;
use Email;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE( Namespace => 'Email', Domain => 'base-email' );

$cce->connectfd();

# Get 'System' Object:
my @oids = $cce->find('System');
if (not @oids) {
    $cce->bye('FAIL');
    exit 1;
}
my ($ok, $System) = $cce->get($oids[0]);
unless ($ok and $System) {
    $cce->bye('FAIL');
    exit 1;
}

# Get 'System' . 'Email' Object/Namespace:
my ($ok, $obj) = $cce->get($oids[0], 'Email');
unless ($ok and $obj) {
    $cce->bye('FAIL');
    exit 1;
}

&debug_msg("Running email/enable.pl\n");

# dovecot settings first
Sauce::Util::editfile('/etc/dovecot/dovecot.conf', *make_dovecot_conf, $obj, $System );

if(!Sauce::Util::replaceblock('/etc/dovecot/conf.d/10-master.conf',
    'service imap-login {',
    &make_imap_block($obj),
    '  # Number of connections to handle before starting a new process. Typically')
    ) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => '/etc/dovecot/conf.d/10-master.conf' });
    $cce->bye('FAIL');
    exit(1);
}

if(!Sauce::Util::replaceblock('/etc/dovecot/conf.d/10-master.conf',
    'service pop3-login {',
    &make_pop3_block($obj),
    'service lmtp {')
    ) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => '/etc/dovecot/conf.d/10-master.conf' });
    $cce->bye('FAIL');
    exit(1);
}

# Stop Dovecot if all related services are turned off:
if (($obj->{'enablePop'} eq '0') && ($obj->{'enablePops'} eq '0') && ($obj->{'enableImap'} eq '0') && ($obj->{'enableImaps'} eq '0')) {
    system("/sbin/service dovecot stop");
    system("/sbin/chkconfig --del dovecot");
}
else {
    # Enable Dovecot:
    system("/sbin/chkconfig --add dovecot");
    Sauce::Service::service_toggle_init('dovecot', 1);
}

# settings smtp, smtps and submission port
Sauce::Util::editfile(Email::SendmailMC, *make_sendmail_mc, $obj, $System );

# need to start sendmail?
my $run = 0;
if ($obj->{enableSMTP} || $obj->{enableSMTPS} || $obj->{enableSubmissionPort}) {
    $run = 1;
}
if ($run) {
    Sauce::Service::service_toggle_init('sendmail', 1);
    Sauce::Service::service_toggle_init('saslauthd', $obj->{enableSMTPAuth});
} else {
    Sauce::Service::service_toggle_init('sendmail', 0);
    Sauce::Service::service_toggle_init('saslauthd', 0);
}


# pop-before-smtp relaying
my $popRelay = Sauce::Service::service_get_init('poprelayd') ? 'on' : 'off';
my $newpopRelay = $obj->{popRelay} ? 'on' : 'off';

&debug_msg("Think poprelayd is running? $popRelay - Should be? $newpopRelay\n");

Sauce::Service::service_toggle_init('poprelayd', $obj->{popRelay}); 

if($newpopRelay eq 'on') {
    &debug_msg("Linking custodiat into place\n");
    Sauce::Util::linkfile('/usr/local/sbin/poprelayd.custodiat', '/etc/cron.quarter-daily/poprelayd.custodiat');
} else {
    &debug_msg("Unlinking custodiat\n");
    Sauce::Util::unlinkfile('/etc/cron.quarter-daily/poprelayd.custodiat');
}

Sauce::Service::service_restart_xinetd();
# running swatch
#system('/usr/sbin/swatch -c /etc/swatch.conf &');

$cce->bye("SUCCESS");
exit(0);


sub make_dovecot_conf
{
    my $in  = shift;
    my $out = shift;

    my $obj = shift;
    my $System = shift;

    my $protocols;
    # Assume safe defaults: IPv4 only.
    my $listen = 'listen = *';

    if (($System->{gateway} ne "") && ($System->{gateway_IPv6} ne "")) {
        # Dual Stack:
        $listen = 'listen = *,[::]';
    }
    elsif (($System->{gateway} eq "") && ($System->{gateway_IPv6} ne "")) {
        # IPv6 only:
        $listen = 'listen = [::]';
    }
    else {
        # IPv4 only:
        $listen = 'listen = *';
    }

    if (($obj->{enableImap}) || ($obj->{enableImaps})) {
        $protocols .= " imap";
    }

    if (($obj->{enablePop}) || ($obj->{enablePops})) {
        $protocols .= " pop3";
    }

    select $out;
    while (<$in>) {
        if (/protocols =/o) {
            print "protocols =$protocols\n";
        }
        elsif (/^listen/) {
            print $listen . "\n";
        }
        else {
            print $_;
        }
    }
    return 1;
}

sub make_imap_block
{
    my $obj = shift;

    $imap_out = "  inet_listener imap {\n";
    if ($obj->{enableImap}) {
        $imap_out .= "    port = 143\n";
    }
    else {
        $imap_out .= "    port = 0\n";
    }
    $imap_out .= "  }\n  inet_listener imaps {\n";
    if ($obj->{enableImaps}) {
        $imap_out .= "    port = 993\n    ssl = yes\n  }\n";
    }
    else {
        $imap_out .= "    port = 0\n    ssl = no\n  }\n";
    }
    return $imap_out;
}

sub make_pop3_block
{
    my $obj = shift;

    $pop3_out = "  inet_listener pop3 {\n";
    if ($obj->{enablePop}) {
        $pop3_out .= "    port = 110\n";
    }
    else {
        $pop3_out .= "    port = 0\n";
    }
    $pop3_out .= "  }\n  inet_listener pop3s {\n";
    if ($obj->{enablePops}) {
        $pop3_out .= "    port = 995\n    ssl = yes\n  }\n";
    }
    else {
        $pop3_out .= "    port = 0\n    ssl = no\n  }\n";
    }
    $pop3_out .= "}\n";
    return $pop3_out;
}

sub make_sendmail_mc
{
    my $in  = shift;
    my $out = shift;

    my $obj = shift;
    my $System = shift;

    # Are we an OpenVZ mastern node?
    if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
        # Yes, we are.
        $device = 'venet0:0';
    }
    else {
        # No, we are not.
        $device = 'eth0';
    }

    $ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
    chomp($ipv6_ip);

    #
    ## Cheat Sheet:
    #
    # IPv4 only:
    # DAEMON_OPTIONS(`Port=smtp, Name=MTA')
    # DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')
    # DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')
    # 
    # IPv6 only:
    # DAEMON_OPTIONS(`Familiy=inet6, Port=smtp, Name=MTA, Modifier=O')
    # DAEMON_OPTIONS(`Familiy=inet6, Port=submission, Name=MSA, M=Ea, Modifier=O')
    # DAEMON_OPTIONS(`Familiy=inet6, Port=smtps, Name=TLSMTA, M=s, Modifier=O')
    # 
    # IPv6-Part in Dual Stack. Requires IPv4 part as well. And yes: We can only bind to the primary IPv6 IP, not all of them:
    # DAEMON_OPTIONS(`Familiy=inet6, port=smtp, Name=MTA-v6, Modifier=O, Addr=2001:470:1f0e:7ee::30')
    # DAEMON_OPTIONS(`Familiy=inet6, Port=submission, Name=MSA-v6, M=Ea, Modifier=O, Addr=2001:470:1f0e:7ee::30')
    # DAEMON_OPTIONS(`Familiy=inet6, Port=smtps, Name=TLSMTA-v6, M=s, Modifier=O, Addr=2001:470:1f0e:7ee::30')

    # Start with empty IPv6 related lines:
    my $ipv6_part = '';

    if (($System->{gateway} ne "") && ($System->{gateway_IPv6} ne "")) {
        #
        ### Dual Stack:
        #

        # smtp port
        if ($obj->{enableSMTP}) {
            $smtpPort = "DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
            $ipv6_part .= "DAEMON_OPTIONS(`Familiy=inet6, port=smtp, Name=MTA-v6, Modifier=O, Addr=$ipv6_ip')\n";
        }
        else {
            $smtpPort = "dnl DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
        }

        # smtps port
        if ($obj->{enableSMTPS}) {
            $smtpsPort = "DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
            $ipv6_part .= "DAEMON_OPTIONS(`Familiy=inet6, Port=smtps, Name=TLSMTA-v6, M=s, Modifier=O, Addr=$ipv6_ip')\n";
        }
        else {
            $smtpsPort = "dnl DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
        }

        # submission(587) port
        if ($obj->{enableSubmissionPort}) {
            $submissionPort = "DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
            $ipv6_part .= "DAEMON_OPTIONS(`Familiy=inet6, Port=submission, Name=MSA-v6, M=Ea, Modifier=O, Addr=$ipv6_ip')\n";
        }
        else {
            $submissionPort = "dnl DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
        }
    }
    elsif (($System->{gateway} eq "") && ($System->{gateway_IPv6} ne "")) {
        #
        ### IPv6 only:
        #
        # smtp port
        if ($obj->{enableSMTP}) {
            $smtpPort = "dnl DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
            $ipv6_part .= "DAEMON_OPTIONS(`Familiy=inet6, port=smtp, Name=MTA-v6, Modifier=O')\n";
        }
        else {
            $smtpPort = "dnl DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
        }

        # smtps port
        if ($obj->{enableSMTPS}) {
            $smtpsPort = "dnl DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
            $ipv6_part .= "DAEMON_OPTIONS(`Familiy=inet6, Port=smtps, Name=TLSMTA-v6, M=s, Modifier=O')\n";
        }
        else {
            $smtpsPort = "dnl DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
        }

        # submission(587) port
        if ($obj->{enableSubmissionPort}) {
            $submissionPort = "dnl DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
            $ipv6_part .= "DAEMON_OPTIONS(`Familiy=inet6, Port=submission, Name=MSA-v6, M=Ea, Modifier=O')\n";
        }
        else {
            $submissionPort = "dnl DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
        }
    }
    else {
        #
        ### IPv4 only:
        #
        # smtp port
        if ($obj->{enableSMTP}) {
            $smtpPort = "DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
        }
        else {
            $smtpPort = "dnl DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
        }

        # smtps port
        if ($obj->{enableSMTPS}) {
            $smtpsPort = "DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
        }
        else {
            $smtpsPort = "dnl DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
        }

        # submission(587) port
        if ($obj->{enableSubmissionPort}) {
            $submissionPort = "DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
        }
        else {
            $submissionPort = "dnl DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
        }
    }

    select $out;
    while (<$in>) {
        if (/^dnl DAEMON_OPTIONS\(\`Port=smtp, Name=MTA'/o || /^DAEMON_OPTIONS\(\`Port=smtp, Name=MTA'/o ) {
            print $smtpPort;
        } elsif (/^dnl DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA,/o || /^DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA,/o ) {
            print $smtpsPort;
        } elsif (/^dnl DAEMON_OPTIONS\(\`Port=submission, Name=MSA,/o || /^DAEMON_OPTIONS\(\`Port=submission, Name=MSA,/o ) {
            print $submissionPort;
        } elsif (/^DAEMON_OPTIONS\(\`Familiy=inet6/) {
            # We remove any existing IPv6 DAEMON_OPTIONS lines first (and add the correct ones later).
            next;
        } elsif (/^dnl DAEMON_OPTIONS\(\`port=smtp,Addr=::1, Name=MTA-v6, Family=inet6'\)dnl/) {
            # Insert IPv6 related lines below this marker:
            print "dnl DAEMON_OPTIONS(`port=smtp,Addr=::1, Name=MTA-v6, Family=inet6')dnl\n";
            # Insert IPv6 related lines (if there are any to insert):
            print $ipv6_part;
        } else {
            print $_;
        }
    }
    return 1;
}

sub debug_msg {
    if ($DEBUG) {
    my $msg = shift;
    $DEBUG && print STDERR "$ARGV[0]: ", $msg, "\n";

    $user = $ENV{'USER'};
    setlogsock('unix');
    openlog($0,'','user');
    syslog('info', "$ARGV[0]: $msg");
    closelog;
    }
}

# 
# Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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