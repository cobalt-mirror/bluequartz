#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email/
# $Id: enable.pl

use Sauce::Service;
use CCE;
use Email;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/email.enable");
$DEBUG && warn `date`;

my $cce = new CCE( Namespace => 'Email',
                      Domain => 'base-email' );

$cce->connectfd();

my $old_obj = $cce->event_old();
my $obj = $cce->event_object();

# dovecot settings first
Sauce::Util::editfile('/etc/dovecot.conf', *make_dovecot_conf, $obj );

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
Sauce::Util::editfile(Email::SendmailMC, *make_sendmail_mc, $obj );

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

$DEBUG && warn "Think poprelayd is running? $popRelay\nShould be? $newpopRelay\n";

Sauce::Service::service_toggle_init('poprelayd', $obj->{popRelay}); 

if($newpopRelay eq 'on') {
	$DEBUG && warn "linking custodiat into place\n";
	Sauce::Util::linkfile('/usr/local/sbin/poprelayd.custodiat', '/etc/cron.quarter-daily/poprelayd.custodiat');
} else {
	$DEBUG && warn "unlinking custodiat\n";
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

    my $protocols;

    if ($obj->{enableImap}) {
        $protocols .= " imap";
    }

    if ($obj->{enableImaps}) {
        $protocols .= " imaps";
    }

    if ($obj->{enablePop}) {
        $protocols .= " pop3";
    }

    if ($obj->{enablePops}) {
        $protocols .= " pop3s";
    }

    select $out;
    while (<$in>) {
        if (/protocols =/o) {
            print "protocols = $protocols\n";
        } else {
            print $_;
        }
    }
    return 1;
}

sub make_sendmail_mc
{
    my $in  = shift;
    my $out = shift;

    my $obj = shift;

    # smtp port
    if ($obj->{enableSMTP}) {
        $smtpPort = "DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
    } else {
        $smtpPort = "dnl DAEMON_OPTIONS(`Port=smtp, Name=MTA')\n";
    }

    # smtps port
    if ($obj->{enableSMTPS}) {
        $smtpsPort = "DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
    } else {
        $smtpsPort = "dnl DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')\n";
    }

    # submission(587) port
    if ($obj->{enableSubmissionPort}) {
        $submissionPort = "DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
    } else {
        $submissionPort = "dnl DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')\n";
    }

    select $out;
    while (<$in>) {
        if (/^dnl DAEMON_OPTIONS\(\`Port=smtp, Name=MTA/o || /^DAEMON_OPTIONS\(\`Port=smtp, Name=MTA/o ) {
            print $smtpPort;
        } elsif (/^dnl DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA/o || /^DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA/o ) {
            print $smtpsPort;
        } elsif (/^dnl DAEMON_OPTIONS\(\`Port=submission, Name=MSA/o || /^DAEMON_OPTIONS\(\`Port=submission, Name=MSA/o ) {
            print $submissionPort;
        } else {
            print $_;
        }
    }
    return 1;
}

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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