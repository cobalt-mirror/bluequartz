#!/usr/bin/perl -I. -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: syncEmailService.pl

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
if (-f '/etc/admserv/certs/ca-certs') {
    # If AdmServ has an intermediate, then copy it to the ca-bundle as well:
    system("cat /etc/admserv/certs/ca-certs >> /usr/share/ssl/certs/ca-bundle.crt")
}

#
## Handle TLS oddity on 5106R:
#
# read build date
my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($build, $model, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

# Create 2048 bit Diffie-Hellman file:
if (! -e "/usr/share/ssl/certs/sendmail-2048.dh") {
    system("/usr/bin/openssl dhparam -out /usr/share/ssl/certs/sendmail-2048.dh 2048");
    system("chmod 0600 /usr/share/ssl/certs/sendmail-2048.dh");
}

# dovecot settings first

$md5_orig = `cat /etc/dovecot/dovecot.conf /etc/dovecot/conf.d/* | md5sum`;
chomp($md5_orig);

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

$md5_new = `cat /etc/dovecot/dovecot.conf /etc/dovecot/conf.d/* | md5sum`;
chomp($md5_new);

# Stop mail services if they are disabled. Which do not use xinetd.
if (($obj->{'enablePop'} eq '0') && ($obj->{'enablePops'} eq '0') && ($obj->{'enableImap'} eq '0') && ($obj->{'enableImaps'} eq '0')) {
    Sauce::Service::service_run_init('dovecot', 'stop');
    Sauce::Service::service_set_init('dovecot', 0);
}
else {
    Sauce::Service::service_set_init('dovecot', 1);
    if ($md5_orig ne $md5_new) {
        Sauce::Service::service_toggle_init('dovecot', 1);
    }
}

# sync sendmail settings

$md5_sm_orig = `cat /etc/mail/sendmail.mc|md5sum`;

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

# Check if the config has changed:
$md5_sm_new = `cat /etc/mail/sendmail.mc|md5sum`;

# need to start sendmail?
if ($run) {
    if ($md5_sm_orig ne $md5_sm_new) {
        # Config has changed, toggle service:
        Sauce::Service::service_toggle_init('sendmail', 1);
        Sauce::Service::service_toggle_init('saslauthd', $obj->{enableSMTPAuth});
    }
}
else {
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


sub make_dovecot_conf {
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
        }
        else {
            print $_;
        }
    }
    return 1;
}

sub make_sendmail_mc {
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

    # SmartRelay:
    if ($obj->{smartRelay}) {
        $smartRelay_out = "define(`SMART_HOST', `" . $obj->{smartRelay} . "')\n";
    }
    else {
        $smartRelay_out = "dnl define(`SMART_HOST', `')dnl\n";
    }

    # hideHeaders:
    if ($obj->{hideHeaders} eq "1") {
        $hideHeaders_out = "define(`confRECEIVED_HEADER',`by \$j \$?r with \$r\$. id \$i; \$b')dnl\n";
    }
    else {
        $hideHeaders_out = "dnl define(`confRECEIVED_HEADER',`by \$j \$?r with \$r\$. id \$i; \$b')dnl\n";
    }

    # delayChecks:
    if ($obj->{delayChecks} eq "1") {
        $delayChecks_out = "FEATURE(delay_checks)dnl\n";
    }
    else {
        $delayChecks_out = "dnl FEATURE(delay_checks)dnl\n";
    }

    # masqAddress:
    if ($obj->{masqAddress}) {
        $masqAddress_out = "MASQUERADE_AS(`" . $obj->{masqAddress} . "')\n";
    }
    else {
        $masqAddress = "MASQUERADE_AS()dnl\n";
    }

    # maxRecipientsPerMessage:
    if ($obj->{maxRecipientsPerMessage}) {
        $maxRecipientsPerMessage_out = "define(`confMAX_RCPTS_PER_MESSAGE'," . $obj->{maxRecipientsPerMessage} . ")\n";
    }
    else {
        $maxRecipientsPerMessage_out = "define(`confMAX_RCPTS_PER_MESSAGE',0)\n";
    }

    # Diffie-Hellmann File:
    $DiffieHellmann = "define(`confDH_PARAMETERS',`/usr/share/ssl/certs/sendmail-2048.dh')\n";

    # Configure LOCAL_CONFIG:
    # 
    # Special note here for the CipherList:
    #
    # Ideally we only want secure cipgers. So we would enable HIGH and turn off all the rest.
    # Bad idea. As crappy as TLS_RSA_WITH_RC4_128_SHA or TLS_RSA_WITH_RC4_128_MD5 are, we do
    # need to allow them at a minimum as that is a fallback level which even the most decrepid
    # mailservers ought to understand. So we enable HIGH, MEDIUM and LOW and then explicitly
    # forbid anything that we consider too weak. The net result of this is that we (at the bare
    # minimum) end up with something like this on a 5106R:
    #
    # TLS_DHE_RSA_WITH_AES_128_CBC_SHA - strong
    # TLS_DHE_RSA_WITH_AES_256_CBC_SHA - strong
    # TLS_RSA_WITH_AES_128_CBC_SHA - strong
    # TLS_RSA_WITH_AES_256_CBC_SHA - strong
    # TLS_RSA_WITH_RC4_128_MD5 - semi-strong
    # TLS_RSA_WITH_RC4_128_SHA - semi-strong
    #
    # On other platforms (5107R, 5108R, 5207R, 5208R and 5209R) we end up with 15-18 unique 
    # ciphers in total. None of them really bad and both TLS_RSA_WITH_RC4_128_SHA and
    # TLS_RSA_WITH_RC4_128_MD5 are present to represent the bottom end of the spectrum.
    #
    # 5107R, 5108R, 5207R and 5208R:
    #
    # TLS_DHE_RSA_WITH_AES_128_CBC_SHA256 - strong
    # TLS_DHE_RSA_WITH_AES_128_GCM_SHA256 - strong
    # TLS_DHE_RSA_WITH_AES_256_CBC_SHA256 - strong
    # TLS_DHE_RSA_WITH_AES_256_CBC_SHA - strong
    # TLS_DHE_RSA_WITH_AES_256_GCM_SHA384 - strong
    # TLS_DHE_RSA_WITH_CAMELLIA_128_CBC_SHA - strong
    # TLS_DHE_RSA_WITH_CAMELLIA_256_CBC_SHA - strong
    # TLS_RSA_WITH_AES_128_CBC_SHA - strong
    # TLS_RSA_WITH_AES_256_CBC_SHA256 - strong
    # TLS_RSA_WITH_AES_256_CBC_SHA - strong
    # TLS_RSA_WITH_AES_256_GCM_SHA384 - strong
    # TLS_RSA_WITH_CAMELLIA_128_CBC_SHA - strong
    # TLS_RSA_WITH_CAMELLIA_256_CBC_SHA - strong
    # TLS_RSA_WITH_RC4_128_MD5 - semi-strong
    # TLS_RSA_WITH_RC4_128_SHA - semi-strong
    #
    # 5209R:
    #
    # TLS_DHE_RSA_WITH_AES_128_CBC_SHA256 - strong
    # TLS_DHE_RSA_WITH_AES_128_CBC_SHA - strong
    # TLS_DHE_RSA_WITH_AES_128_GCM_SHA256 - strong
    # TLS_DHE_RSA_WITH_AES_256_CBC_SHA256 - strong
    # TLS_DHE_RSA_WITH_AES_256_CBC_SHA - strong
    # TLS_DHE_RSA_WITH_AES_256_GCM_SHA384 - strong
    # TLS_DHE_RSA_WITH_CAMELLIA_128_CBC_SHA - strong
    # TLS_DHE_RSA_WITH_CAMELLIA_256_CBC_SHA - strong
    # TLS_RSA_WITH_AES_128_CBC_SHA256 - strong
    # TLS_RSA_WITH_AES_128_CBC_SHA - strong
    # TLS_RSA_WITH_AES_128_GCM_SHA256 - strong
    # TLS_RSA_WITH_AES_256_CBC_SHA256 - strong
    # TLS_RSA_WITH_AES_256_CBC_SHA - strong
    # TLS_RSA_WITH_AES_256_GCM_SHA384 - strong
    # TLS_RSA_WITH_CAMELLIA_128_CBC_SHA - strong
    # TLS_RSA_WITH_CAMELLIA_256_CBC_SHA - strong
    # TLS_RSA_WITH_RC4_128_MD5 - semi-strong
    # TLS_RSA_WITH_RC4_128_SHA - semi-strong

    $local_config = 'LOCAL_CONFIG' . "\n";
    $local_config .= 'O CipherList=HIGH:MEDIUM:LOW:!aNULL:!eNULL:!3DES:!EXP:!PSK:!DSS:!SEED:!DES:!IDEA' . "\n";
    $local_config .= 'O ServerSSLOptions=+SSL_OP_NO_SSLv2 +SSL_OP_NO_SSLv3 +SSL_OP_CIPHER_SERVER_PREFERENCE' . "\n";
    $local_config .= 'O ClientSSLOptions=+SSL_OP_NO_SSLv2 +SSL_OP_NO_SSLv3' . "\n";

    # MaxRecipientsPerMessage
    if ($obj->{maxRecipientsPerMessage} ) {
        # Maximum number of recipients per SMTP envelope:
        $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',". $obj->{maxRecipientsPerMessage} .")\n";
    }
    else {
        $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',0)\n";
    }


    select $out;
    $Dh_found = "0";
    $LC_found = "0";
    $mms_found = "0";
    $delayChecks_found = "0";
    $smartHost_found = "0";
    $hide_headers_found = "0";
    $maxReceipients_found = "0";
    $masquerade_found = "0";
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
            $mms_found = "1";
        }
        elsif ( /^define\(\`confDH_PARAMETERS/o ) { 
            # Do nothing and remove this line.
        }
        elsif ( /define\(\`SMART_HOST/ ) { 
            print $smartRelay_out;
            $smartHost_found = "1";
        }
        elsif (( /^define\(\`confRECEIVED_HEADER(.*)$/ ) || ( /^dnl define\(\`confRECEIVED_HEADER(.*)$/ )) { 
            print $hideHeaders_out;
            $hide_headers_found = "1";
        }
        elsif (( /^FEATURE\(delay_checks\)dnl$/) || ( /^dnl FEATURE\(delay_checks\)dnl$/ )) { 
            print $delayChecks_out;
            $delayChecks_found = "1";
        }
        elsif ( /^MASQUERADE_AS(.*)$/ ) { 
            print $masqAddress;
            $masquerade_found = "1";
        }
        elsif (( /^define\(`confMAX_RCPTS_PER_MESSAGE(.*)$/o ) || ( /^dnl define\(`confMAX_RCPTS_PER_MESSAGE(.*)$/o )) { 
            print $maxRecipientsPerMessage_out;
            $maxReceipients_found = "1";
        }
        elsif ( /^MAILER\(procmail\)dnl/o ) {
            print $_;
            if ($Dh_found eq "0") {
                # Add the Diffie-Hellmann line:
                print $DiffieHellmann;
                $Dh_found = "1";
            }
            if ($LC_found eq "0") {
                # Add the LOCAL_CONFIG line:
                print $local_config;
                $LC_found = "1";
            }
        }
        elsif (( /^LOCAL_CONFIG$/o ) || (/^O CipherList(.*)$/o) || (/^O ServerSSLOptions(.*)$/o) || (/^O ClientSSLOptions(.*)$/o)) {
            # If the information was already in sendmail.mc, then we ignore it as we're printing it again anyway.
            next;
        }
        else {
            print $_;
        }
    }
    # If we not found certain critical elements, then we add them at the bottom:
    if ($mms_found eq "0") {
        print $maxMessageSize_out;
    }
    if ($delayChecks_found eq "0") {
        print $delayChecks_out;
    }
    if ($smartHost_found eq "0") {
        print $smartRelay_out;
    }    
    if ($hide_headers_found eq "0") {
        print $hideHeaders_out;
    }
    if ($masquerade_found eq "0") {
        print $masqAddress;
    }
    if ($maxReceipients_found eq "0") {
        print $maxRecipientsPerMessage_out;
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
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
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