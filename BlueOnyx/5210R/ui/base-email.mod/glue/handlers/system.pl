#!/usr/bin/perl -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: system.pl 

use CCE;
use Email;
use Sauce::Util;
use Sauce::Config;

# debugging flag, set to 1 to turn on logging to STDERR
my $DEBUG = 0;
if ($DEBUG) 
{ 
    use Data::Dumper; 
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

# Globals.
my $Sendmail_mc = Email::SendmailMC;
my $Sendmail_sysconfig_file = "/etc/sysconfig/sendmail";

# These should be globals..

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );

$cce->connectfd();

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

# make sure masqAddress is not set to localhost
if ($obj->{masqAddress} =~ /^localhost$/i) {
    $cce->baddata($cce->event_oid(), 
          'masqAddress', 'masqAddressCantBeLocalhost');
    $cce->bye('FAIL');
    exit(1);
}

my $sys_obj = ( $cce->get( ($cce->find("System"))[0] ) )[1];

# Get primary IP:
my (@network_oids) = $cce->findx('Network', { 'enabled' => 1, 'real' => 1 });
my $primaryIP = '127.0.0.1';
&debug_msg("Primary IP: $primaryIP");
foreach my $oid (@network_oids) {
    my ($ok, $net) = $cce->get($oid);
    &debug_msg("OID: $oid");
    if ($ok) {
        &debug_msg("Device: " . $net->{device});
        if (($net->{device} ne "venet0") && ($primaryIP eq '127.0.0.1')) {
            $primaryIP = $net->{ipaddr};
        }
    }
    &debug_msg("Using primary IP: $primaryIP");
}

#
## Update sendmail.mc:
#

Sauce::Util::editfile($Sendmail_mc, *make_sendmail_mc, $obj );

#
## Get RBL config (if present) and add it to sendmail.mc:
#

@rbloids = $cce->find('dnsbl');
foreach $rbloid (@rbloids) {
    &debug_msg("Found RBL-OID $rbloid \n");
    ($ok, $RBLSettings) = $cce->get($rbloid, '');

    # Fiddle the RBL settings into sendmail.mc:
    $ret = Sauce::Util::editfile(Email::SendmailMC, *make_sendmail_mc_rbl, $RBLSettings);
    if(!$ret) {
        $cce->bye('FAIL', 'cantEditFile', {'file' => Email::SendmailMC});
        exit(0);
    } 
}

# add rollback to recreate virtusertable.db
Sauce::Util::addrollbackcommand("/usr/bin/makemap hash $Email::VIRTUSER < $Email::VIRTUSER >/dev/null 2>&1");

system("/usr/bin/newaliases");

if (!Sauce::Util::replaceblock($Email::VIRTUSER,
                   '# Cobalt System Section Begin',
                   &make_virtuser_system($obj),
                   '# Cobalt System Section End')
    ) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => $Email::VIRTUSER });
    $cce->bye('FAIL');
}

my $ret = Sauce::Util::editfile($Sendmail_sysconfig_file, *set_queue_time,
                $obj, $cce);
if(! $ret ) {
    $cce->bye('FAIL', 'cantEditFile', {'file' => $Sendmail_sysconfig_file});
}
else {
    $cce->bye("SUCCESS");
}

exit 0;

sub make_sendmail_mc {
    my $in  = shift;
    my $out = shift;
    my $obj = shift;

    my $privacy_line;
    my $maxMessageSize_line;
    my $maxRecipientsPerMessage_line;
    my $smartRelay_line;
    my $hideHeaders_line;
    my $masqDomain_line;
    my $deliveryMode_line;
    my $delayChecks_line;

    my %Printed_line = ( 
        enableSMTP => 0,
        enableSMTPS => 0,
        enableSubmissionPort => 0,
        maxMessageSize => 0,
        smartRelay => 0,
        privacy => 0,
        queueTime => 0,
        maxRecipientsPerMessage => 0,
        masqAddress => 0,
        delayChecks => 0,
        hideHeaders => 0,
        popRelay => 0,
        Dh_found => 0,
        LC_found => 0 );

    my @Mailer_line = ();

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

    if ($obj->{smartRelay} ) {
        $smartRelay_line = "define(`SMART_HOST', `". $obj->{smartRelay} . "')\n";
    }
    else {
        $smartRelay_line = "define(`SMART_HOST', `')\n";
    }

    if ($obj->{maxMessageSize} ) {
        # Max message size is in kilos. Sendmail needs bytes.
        $maxMessageSize_line = "define(`confMAX_MESSAGE_SIZE',". $obj->{maxMessageSize}*1024 .")\n";
    }
    else {
        $maxMessageSize_line = "define(`confMAX_MESSAGE_SIZE',0)\n";
    }

    # SmartRelay:
    if ($obj->{smartRelay}) {
        $smartRelay_out = "define(`SMART_HOST', `" . $obj->{smartRelay} . "')\n";
    }
    else {
        $smartRelay_out = "dnl define(`SMART_HOST', `')dnl\n";
    }

    if ($obj->{privacy} ) {
        $privacy_line = "define(`confPRIVACY_FLAGS', `noexpn noexpn authwarnings')\n";
        
    }
    else {
        $privacy_line = "define(`confPRIVACY_FLAGS', `authwarnings')\n";
    }

    if ($obj->{queueTime} eq 'immediate') {
        $deliveryMode_line = "define(`confDELIVERY_MODE', `background')\n";
    }
    else {
        $deliveryMode_line = "define(`confDELIVERY_MODE', `deferred')\n";
    }

    if ($obj->{maxRecipientsPerMessage} ) {
        # Maximum number of recipients per SMTP envelope:
        $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',". $obj->{maxRecipientsPerMessage} .")\n";
    }
    else {
        $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',0)\n";
    }

    if ($obj->{masqAddress} ) {
        $masqDomain_line = "MASQUERADE_AS(`". $obj->{masqAddress} ."')\n"
    }
    else {
        $masqDomain_line = "MASQUERADE_AS(`')\n";
    }

    if ($obj->{delayChecks} ) {
        $delayChecks_line = "FEATURE(delay_checks)dnl\n";
    }
    else {
        $delayChecks_line = "dnl FEATURE(delay_checks)dnl\n";
    }

    if ($obj->{hideHeaders} ) {
        #$hideHeaders_line = "define(`confRECEIVED_HEADER',`by \$j \$?r with \$r\$. id \$i; \$b')dnl\n";
        $hideHeaders_line = "define(`confRECEIVED_HEADER',`\$?{auth_type}from \${auth_authen} (\$j [$primaryIP]) \$|_REC_HDR_\$.\n\t_REC_BY_\n\t_REC_TLS_\n\t_REC_END_')\n";
    }
    else {
        $hideHeaders_line = "dnl define(`confRECEIVED_HEADER',`by \$j \$?r with \$r\$. id \$i; \$b')dnl\n";
    }

    if ($obj->{popRelay} ) {
        $popRelay_line = "HACK(popauth)dnl\n";
    }
    else {
        $popRelay_line = "";
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

    #
    ### Process changes:
    #

    my $mailer_lines = 0;
    my @Mailer_line = ();    
    select $out;
    while (<$in> ) {

        if (/^dnl DAEMON_OPTIONS\(\`Port=smtp, Name=MTA/o || /^DAEMON_OPTIONS\(\`Port=smtp, Name=MTA/o ) {
            $Printed_line{'enableSMTP'}++;
            print $smtpPort;
        }
        elsif (/^dnl DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA/o || /^DAEMON_OPTIONS\(\`Port=smtps, Name=TLSMTA/o ) {
            $Printed_line{'enableSMTPS'}++;
            print $smtpsPort;
        }
        elsif (/^dnl DAEMON_OPTIONS\(\`Port=submission, Name=MSA/o || /^DAEMON_OPTIONS\(\`Port=submission, Name=MSA/o ) {
            $Printed_line{'enableSubmissionPort'}++;
            print $submissionPort;
        }
        elsif ( /^define\(`confMAX_MESSAGE_SIZE'/o || /^dnl define\(`confMAX_MESSAGE_SIZE'/o ) { #`
            $Printed_line{'maxMessageSize'}++;
            print $maxMessageSize_line;
        }
        elsif ( /^define\(`SMART_HOST'/o || /^dnl define\(`SMART_HOST'/o ) { #`
            $Printed_line{'smartRelay'}++;
            print $smartRelay_line;
        }
        elsif ((/^define\(`confPRIVACY_FLAGS'/o || /^dnl define\(`confPRIVACY_FLAGS'/o ) && ! $Printed_line{'privacy'} ) {
            $Printed_line{'privacy'}++;
            print $privacy_line;
        }
        elsif ( /^define\(`confDELIVERY_MODE'/o || /dnl ^define\(`confDELIVERY_MODE'/o ) { #`
            $Printed_line{'queueTime'}++;
            print $deliveryMode_line;
        }
        elsif (( /^define\(`confMAX_RCPTS_PER_MESSAGE'/o || /^dnl define\(`confMAX_RCPTS_PER_MESSAGE'/o ) && ! $Printed_line{'maxRecipientsPerMessage'}) {
            $Printed_line{'maxRecipientsPerMessage'}++;
            print $maxRecipientsPerMessage_line;
        }
        elsif ( /^MASQUERADE_AS/o || /^dnl MASQUERADE_AS/o ) {
            $Printed_line{'masqAddress'}++;
            print $masqDomain_line;
        }
        elsif ( /^FEATURE\(delay_checks/o || /^dnl FEATURE\(delay_checks/o ) {
            $Printed_line{'delayChecks'}++;
            print $delayChecks_line;
        }
        elsif( ( /^define\(`confRECEIVED_HEADER/ || /_REC_BY_/ || /_REC_TLS_/ || /_REC_END_/ || /^dnl define\(`confRECEIVED_HEADER/ ) && ! $Printed_line{'hideHeaders'} ) {
            # If we find any of the above regexp, then we ignore them. The 'hideheaders' line will be added further below.
        }
        elsif ( /^HACK\(popauth\)dnl$/o  ) {
            &debug_msg("Found POPAUTH line!");
            $Printed_line{'popRelay'}++;
            print $popRelay_line;
        }
        elsif ( /^MAILER\(procmail\)dnl/o ) {
            print $_;
            if ($Printed_line{'Dh_found'} eq "0") {
                # Add the Diffie-Hellmann line:
                print $DiffieHellmann;
                $Printed_line{'Dh_found'} = "1";
            }
            if ($Printed_line{'LC_found'} eq "0") {
                # Add the LOCAL_CONFIG line:
                print $local_config;
                $Printed_line{'LC_found'} = "1";
            }
        }        
        elsif (( /^LOCAL_CONFIG$/o ) || (/^O CipherList(.*)$/o) || (/^O ServerSSLOptions(.*)$/o) || (/^O ClientSSLOptions(.*)$/o)) {
            # If the information was already in sendmail.mc, then we ignore it as we're printing it again anyway.
            next;
        }
        elsif ( /^define\(\`confDH_PARAMETERS/o ) { 
            # Do nothing and remove this line.
        }
        elsif ( /^MAILER\(/o ) {
            $Mailer_line[$mailer_lines] = $_;
            $mailer_lines++;
        }
        elsif( /^FEATURE\(dnsbl/ || /^dnl FEATURE\(dnsbl/ )  {
            # We strip these out, because further down they are added anyway.
        }
        else {
            print $_;
        }
    }

    foreach my $key ( keys %Printed_line ) {
        if ($Printed_line{$key} == 0) {
            if ($key eq 'enableSMTP') {
                print $smtpPort;
            }
            elsif ($key eq 'enableSMTPS') {
                print $smtpsPort;
            }
            elsif ($key eq 'enableSubmissionPort') {
                print $submissionPort;
            }
            elsif ($key eq 'enableSubmissionPort') {
                print $submissionPort;
            }
            elsif ($key eq 'maxMessageSize') {
                print $maxMessageSize_line;
            }
            elsif ($key eq 'smartRelay') {
                print $smartRelay_line;
            }
            elsif ($key eq 'privacy') {
                print $privacy_line;
            }
            elsif ($key eq 'queueTime') {
                print $deliveryMode_line;
            }
            elsif ($key eq 'maxRecipientsPerMessage') {
                print $maxRecipientsPerMessage_line;
            }
            elsif ($key eq 'masqAddress') {
                print $masqDomain_line;
            }
            elsif ($key eq 'masqAddress') {
                print $masqDomain_line;
            }
            elsif ($key eq 'delayChecks') {
                print $delayChecks_line;
            }
            elsif ($key eq 'hideHeaders') {
                print $hideHeaders_line;
                $Printed_line{'hideHeaders'}++;
            }
            elsif ($key eq 'popRelay') {
                print $popRelay_line;
                $Printed_line{'popRelay'}++;
            }
            else {
                $cce->warn("error_writing_sendmail_mc");
                print STDERR "Writing sendmail_mc found $Printed_line{$key} occurences of $key\n";
            }
        }
    }

    if ($mailer_lines) {
        foreach my $line (@Mailer_line) {
            print $line;
        }
    }

    return 1;
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

sub make_virtuser_system {
    my $obj = shift;
    my $out = "";

    my %routes = $cce->scalar_to_array( $obj->{routes} );

    while (my($domain,$target) = each %routes ) {
        $out .= "@".$domain."\t%1@".$target."\n";
    }
    return $out;
}

sub set_queue_time {
    my ($in, $out, $obj, $cce) = @_;
    my $queue;
    my $period = $obj->{queueTime};

    my $period_hash = {
        'immediate' => '15m',
        'quarter-hourly' => '15m',
        'half-hourly' => '30m',
        'hourly' => '1h',
        'quarter-daily' => '6h',
        'daily' => '1d'
    };

    $queue = $period_hash->{$period};

    while (<$in>) {
        # skip the old entry we're searching for
        if (/^QUEUE=/) {
            $_ = "QUEUE=$queue\n";
        }
        print $out $_;
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
# Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
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