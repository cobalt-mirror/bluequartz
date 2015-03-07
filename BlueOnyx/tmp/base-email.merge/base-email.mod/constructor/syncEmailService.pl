#!/usr/bin/perl -I. -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: syncEmailService.pl Sat 10 Apr 2010 07:30:55 AM CEST mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Copyright 2008-2014 Team BlueOnyx, All rights reserved.

use Sauce::Util;
use Sauce::Config;
use Sauce::Service;
use CCE;
use Email;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
    $cce->bye('FAIL');
    exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'Email');
unless ($ok and $obj) {
    $cce->bye('FAIL');
    exit 1;
}

# make certs file
if (! -d "/usr/share/ssl") {
    system("mkdir /usr/share/ssl");
}
if (! -d "/usr/share/ssl/certs") {
    system("mkdir /usr/share/ssl/certs");
}
system("/bin/cp /etc/pki/tls/certs/ca-bundle.crt /usr/share/ssl/certs/");
system("echo \"\" > /etc/admserv/certs/blank.txt"); 
system("cat /etc/admserv/certs/key /etc/admserv/certs/blank.txt /etc/admserv/certs/certificate > /usr/share/ssl/certs/sendmail.pem");
system("chmod 0600 /usr/share/ssl/certs/sendmail.pem");

system("/bin/cp /etc/admserv/certs/key /etc/pki/dovecot/private/dovecot.pem");
system("/bin/cp /etc/admserv/certs/certificate /etc/pki/dovecot/certs/dovecot.pem");

#
## Handle TLS oddity on 5106R:
#
# read build date
my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($build, $model, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

if ($model eq "5106R") {
    # Create Diffie-Hellman file:
    if (! -e "/usr/share/ssl/certs/sendmail.dh") {
    system("/usr/bin/openssl dhparam -out /usr/share/ssl/certs/sendmail.dh 1024");
    system("chmod 0600 /usr/share/ssl/certs/sendmail.dh");
    }
}

# dovecot settings first
Sauce::Util::editfile('/etc/dovecot.conf', *make_dovecot_conf, $obj );
system('rm -f /etc/dovecot.conf.backup.*');

# Handle Dovecot intermediate cert:
if (-f "/etc/admserv/certs/ca-certs") {
    system("/bin/cp /etc/admserv/certs/ca-certs /etc/pki/dovecot/certs/ca.pem");
}
else {
    system("touch /etc/pki/dovecot/certs/ca.pem");
}
chmod 0600, "/etc/pki/dovecot/certs/ca.pem";

# Edit /etc/dovecot/conf.d/10-ssl.conf:
&edit_dovecot_intermediate;

# Stop mail services if they are disabled. Which do not use xinetd.
if (($obj->{'enablePop'} eq '0') && ($obj->{'enablePops'} eq '0') && ($obj->{'enableImap'} eq '0') && ($obj->{'enableImaps'} eq '0')) {
    Sauce::Service::service_run_init('dovecot', 'stop');
    Sauce::Service::service_set_init('dovecot', 0);
}
else {
    Sauce::Service::service_set_init('dovecot', 1);
    Sauce::Service::service_toggle_init('dovecot', 1);
}

# sync sendmail settings
# submission port
my $run = 0;
if ($obj->{enableSMTP} || $obj->{enableSMTPS} || $obj->{enableSubmissionPort}) {
    $run = 1;
}

# settings smtp, smtps and submission port
my $maxMessageSize = $obj->{maxMessageSize};
my $maxRecipientsPerMessage = $obj->{maxRecipientsPerMessage};

#
## Remove existing RBLs from sendmail.mc:
#
system("/bin/cat /etc/mail/sendmail.mc|/bin/grep -v '^FEATURE(dnsbl' > /etc/mail/sendmail.mc.norbl");
system("/bin/mv /etc/mail/sendmail.mc.norbl /etc/mail/sendmail.mc");

#
## Get RBL config (if present):
#
@rbloids = $cce->find('dnsbl');
foreach $rbloid (@rbloids) {
    &debug_msg("Found RBL-OID $rbloid \n");
    ($ok, $RBLSettings) = $cce->get($rbloid, '');

    # Fiddle the active RBL settings back into sendmail.mc:
    $ret = Sauce::Util::editfile(Email::SendmailMC, *make_sendmail_mc_rbl, $RBLSettings);
    if (!$ret) {
        $cce->bye('FAIL', 'cantEditFile', {'file' => Email::SendmailMC});
        exit(0);
    } 
}

# Edit the rest of the Sendmail settings back in:
Sauce::Util::editfile(Email::SendmailMC, *make_sendmail_mc, $obj );

# Cleanup:
system('/bin/rm -f /etc/mail/sendmail.mc.backup.*');

# Rebuilding sendmail.cf:
&debug_msg("Rebuilding sendmail.cf");
system("m4 /usr/share/sendmail-cf/m4/cf.m4 /etc/mail/sendmail.mc > /etc/mail/sendmail.cf");

# Fix EL7 related Systemd fuck-up (thank you, RedHat!):
if (-f "/lib/systemd/system/sendmail.service") {
    system("/bin/sed -i -e 's#^PIDFile=/run/sendmail.pid#PIDFile=/var/run/sendmail.pid#' /lib/systemd/system/sendmail.service");
    system("/usr/bin/systemctl daemon-reload");
}

# need to start sendmail?
if ($run) {
    Sauce::Service::service_toggle_init('sendmail', 1);
    Sauce::Service::service_toggle_init('saslauthd', $obj->{enableSMTPAuth});
} else {
    Sauce::Service::service_toggle_init('sendmail', 0);
    Sauce::Service::service_toggle_init('saslauthd', 0);
}

# Handle activation / deactivation of Z-Push:
if ($obj->{enableZpush}) {
    system("/bin/rm -f /usr/sausalito/ui/web/z-push/.disabled");
}
else {
    system("/bin/touch /usr/sausalito/ui/web/z-push/.disabled");
}

$cce->bye('SUCCESS');
exit 0;


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

    # MaxMessageSize
    if ($obj->{maxMessageSize}) {
        $maxMessageSize_out = "define(`confMAX_MESSAGE_SIZE'," . $obj->{maxMessageSize}*1024 . ")dnl\n";
    }
    else {
        $maxMessageSize_out = "define(`confMAX_MESSAGE_SIZE',0)dnl\n";
    }

    # 5106R Diffie-Hellmann File:
    if ($model eq "5106R") {
       $DiffieHellmann = "define(`confDH_PARAMETERS',`/usr/share/ssl/certs/sendmail.dh')\n";
    }
    else {
       $DiffieHellmann = "";
    }

    # MaxRecipientsPerMessage
        if( $obj->{maxRecipientsPerMessage} ) {
            # Maximum number of recipients per SMTP envelope:
            $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',". $obj->{maxRecipientsPerMessage} .")\n";
        } else {
            $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',0)\n";
        }


    select $out;
    $Dh_found = "0";
    while (<$in>) {
        if (/^dnl DAEMON_OPTIONS\(\`Port=smtp, Name=MTA/o || /^DAEMON_OPTIONS\(\`Port=smtp, Name=MTA/o ) {
            print $smtpPort;
        }
        elsif (/^dnl DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA/o || /^DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA/o ) {
            print $smtpsPort;
        }
        elsif (/^dnl DAEMON_OPTIONS\(\`Port=submission, Name=MSA/o || /^DAEMON_OPTIONS\(\`Port=submission, Name=MSA/o ) {
            print $submissionPort;
        }
        elsif (/^define\(\`confMAX_MESSAGE_SIZE/o ) { # `
            print $maxMessageSize_out;
        }
        elsif ( /^define\(`confMAX_RCPTS_PER_MESSAGE'/o || /^dnl define\(`confMAX_RCPTS_PER_MESSAGE'/o ) { 
            print $maxRecipientsPerMessage_line;
    }
    elsif ( /^define\(\`confDH_PARAMETERS/o ) { 
        # Do nothing and remove this line.
    }
    elsif ( /^MAILER\(procmail\)dnl/o ) {
            print $_;
        if (($model eq "5106R") && ($Dh_found == "0")) {
        # Add the Diffie-Hellmann line:
        print $DiffieHellmann;
        $Dh_found = "1";
        }
    }
        else {
            print $_;
        }
    }
    return 1;
}

sub edit_dovecot_intermediate {

    # Build output hash:
    $server_dovecot_settings_writeoff = { 
        'ssl_ca' => "</etc/pki/dovecot/certs/ca.pem"
    };

    # Write changes using Sauce::Util::hash_edit_function:

    $ok = Sauce::Util::editfile(
        "/etc/dovecot/conf.d/10-ssl.conf",
        *Sauce::Util::hash_edit_function,
        '#',
        { 're' => '=', 'val' => ' = ' },
        $server_dovecot_settings_writeoff);

    system('/bin/rm -f /etc/dovecot/conf.d/10-ssl.conf.backup.*');

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing /etc/dovecot/conf.d/10-ssl.conf!");
        exit(1);
    }
}

sub make_sendmail_mc_rbl {
    my $in  = shift;
    my $out = shift;
    my $obj = shift;

    my $blacklistHost;
    my $deferTemporary;
    my $active;
    my $prefix;
    my $defer;
    my $searchString;
    my %Printed_line = ( blacklistHost => 0);
    my $mailer_lines = 0;
    my @Mailer_line = ();

    if ($obj->{active} ) {
        $prefix = "";
    }
    else {
        $prefix = "dnl ";
    }
    if ($obj->{deferTemporary} ) {
        $defer = "`t'";
    }
    else {
        $defer = "";
    }
    if ($obj->{blacklistHost} ) {
        $blacklistHost = $prefix . "FEATURE(dnsbl, `". $obj->{blacklistHost} ."',,$defer)\n";
    }
    else {
        $blacklistHost = "";
    }
    
    select $out;
    while( <$in> ) {
        if ( /^MAILER\(/o ) {
            $Mailer_line[$mailer_lines] = $_;
            $mailer_lines++;
        }
        else {
            print $_;
        }
    }

    foreach my $key (keys %Printed_line ) {
        if ($Printed_line{$key} != 1) {
            print $blacklistHost;
        }
    }
    
    if ($mailer_lines) {
        foreach my $line (@Mailer_line) {
            print $line;
        }
    }
    
    return 1;
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
