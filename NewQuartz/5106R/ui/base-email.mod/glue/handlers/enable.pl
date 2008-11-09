#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email/
# $Id: enable.pl 771 2006-06-10 15:27:00Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

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

Sauce::Service::service_toggle_init('dovecot', 1);

# settings smtp, smtps and submission port
Sauce::Util::editfile(Email::SendmailCF, *make_sendmail_cf, $obj );

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

sub make_sendmail_cf
{
    my $in  = shift;
    my $out = shift;

    my $obj = shift;

    # smtp port
    if ($obj->{enableSMTP}) {
        $smtpPort = "O DaemonPortOptions=Port=smtp, Name=MTA\n";
    } else {
        $smtpPort = "#O DaemonPortOptions=Port=smtp, Name=MTA\n";
    }

    # smtps port
    if ($obj->{enableSMTPS}) {
        $smtpsPort = "O DaemonPortOptions=Port=smtps, Name=TLSMTA, M=s\n";
    } else {
        $smtpsPort = "#O DaemonPortOptions=Port=smtps, Name=TLSMTA, M=s\n";
    }

    # submission(587) port
    if ($obj->{enableSubmissionPort}) {
        $submissionPort = "O DaemonPortOptions=Port=submission, Name=MSA, M=Ea\n";
    } else {
        $submissionPort = "#O DaemonPortOptions=Port=submission, Name=MSA, M=Ea\n";
    }

    select $out;
    while (<$in>) {
        if (/O DaemonPortOptions=Port=smtp,/o) {
            print $smtpPort;
        } elsif (/O DaemonPortOptions=Port=smtps,/o) {
            print $smtpsPort;
        } elsif (/O DaemonPortOptions=Port=submission,/o) {
            print $submissionPort;
        } else {
            print $_;
        }
    }
    return 1;
}

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
