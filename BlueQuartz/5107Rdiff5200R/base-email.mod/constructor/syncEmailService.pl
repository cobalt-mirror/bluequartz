#!/usr/bin/perl -I. -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: syncEmailService.pl 1459 2010-04-18 15:24:54Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use Sauce::Util;
use Sauce::Config;
use Sauce::Service;
use CCE;
use Email;

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

# stop xinetd mail services
my $pop = Sauce::Service::service_get_xinetd('pop3');
my $imap = Sauce::Service::service_get_xinetd('imap');
if ($pop) {
    Sauce::Service::service_set_xinetd('pop3', 'off');
    system('rm -f /etc/xinetd.d/pop3.backup.*');
}
if ($imap) {
    Sauce::Service::service_set_xinetd('imap', 'off');
    system('rm -f /etc/xinetd.d/imap.backup.*');
}


# make certs file
system("cat /etc/admserv/certs/key /etc/admserv/certs/certificate > /etc/pki/tls/certs/postfix.pem");
chmod 0600, "/etc/pki/tls/certs/postfix.pem";

system("/bin/cp /etc/admserv/certs/key /etc/pki/dovecot/private/dovecot.pem");
system("/bin/cp /etc/admserv/certs/certificate /etc/pki/dovecot/certs/dovecot.pem");
 

# dovecot settings first
Sauce::Util::editfile('/etc/dovecot.conf', *make_dovecot_conf, $obj );
system('rm -f /etc/dovecot.conf.backup.*');

Sauce::Service::service_toggle_init('dovecot', 1);

# sync postfix settings
my $run = 0;
if ($obj->{enableSMTP} || $obj->{enableSMTPS} || $obj->{enableSubmissionPort}) {    $run = 1;
}

# settings smtp, smtps and submission port
Sauce::Util::editfile(Email::PostfixMainCF, *make_main_cf, $obj );
system('rm -f /etc/postfix/main.cf.backup.*');
Sauce::Util::editfile(Email::PostfixMasterCF, *make_master_cf, $obj );
system('rm -f /etc/postfix/master.cf.backup.*');

# Always running postfix for local deliver.
Sauce::Service::service_toggle_init('postfix', 1);
if ($run) {
    my $enableAuth = ($obj->{enableSMTP} && $obj->{enableSMTP_Auth}) ||
	($obj->{enableSMTPS} && $obj->{enableSMTPS_Auth}) ||
	($obj->{enableSubmissionPort} && $obj->{enableSubmission_Auth});
    Sauce::Service::service_toggle_init('saslauthd', $enableAuth);
} else {
    Sauce::Service::service_toggle_init('saslauthd', 0);
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

sub make_main_cf
{
    my $in  = shift;
    my $out = shift;

    my $obj = shift;

    my $interface;
    my $smtpsPort;
    my $checkClient;
    my $recipient;
    my $smtpauth;

    # local delivery only
    if (!$obj->{enableSMTP} && !$obj->{enableSMTPS} &&
        !$obj->{enableSubmissionPort}) {
        $interface = "inet_interfaces = localhost\n";
    } else {
        $interface = "inet_interfaces = all\n";
    }

    # TLS
    if ($obj->{enableTLS}) {
        $tls =<<END;
smtpd_tls_security_level = may
END
    } else {
        $tls =<<END;
#smtpd_tls_security_level = may
END
    }

    # smtps port
    if ($obj->{enableSMTPS}) {
        $smtpsPort =<<END;
smtpd_tls_cert_file = /etc/pki/tls/certs/postfix.pem
smtpd_tls_key_file = /etc/pki/tls/certs/postfix.pem
smtpd_tls_session_cache_database = btree:/etc/postfix/smtpd_scache
END
    } else {
        $smtpsPort =<<END;
#smtpd_tls_cert_file = /etc/pki/tls/certs/postfix.pem
#smtpd_tls_key_file = /etc/pki/tls/certs/postfix.pem
#smtpd_tls_session_cache_database = btree:/etc/postfix/smtpd_scache
END
    }

    # poprelay
    if ($obj->{popRelay}) {
        $checkClient = "check_client_access hash:/etc/poprelay/popip,";
    }
    
    # recipient
    $recipient = "smtpd_recipient_restrictions = check_recipient_access hash:/etc/postfix/access, $checkClient permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination\n";

    # smtpauth
    if (($obj->{enableSMTP} && $obj->{enableSMTP_Auth}) ||
	($obj->{enableSMTPS} && $obj->{enableSMTPS_Auth}) ||
	($obj->{enableSubmissionPort} && $obj->{enableSubmission_Auth})) {
        $smtpauth =<<END;
broken_sasl_auth_clients = yes
END
    } else {
        $smtpauth =<<END;
#broken_sasl_auth_clients = yes
END
    }

    select $out;
    while (<$in>) {
        if (/^# Add configuration for BlueQuartz by init script./o) {
            $found = 1;
        } elsif (!$found) {
            print $_;
        }

        if ($found) {
            if (/smtpd_tls_cert_file = /o) {
                print $smtpsPort;
            } elsif (/smtpd_recipient_restrictions = /o) {
                print $recipient;
            } elsif (/broken_sasl_auth_clients = /o) {
                print $smtpauth;
            } elsif (/inet_interfaces = /o) {
                print $interface;
            } elsif (/smtpd_tls_security_level = /o) {
                print $tls;
            } elsif (/smtpd_tls_key_file = /o ||
                /smtpd_tls_session_cache_database = /o ||
                /broken_sasl_auth_clients = /o) {
                next;
            } else {
                print $_;
            }
        }
    }
    return 1;
}

sub make_master_cf
{
    my $in  = shift;
    my $out = shift;

    my $obj = shift;

    if (!$obj->{enableSMTP}) {
        # smtp port : always running
        $smtpPort = "smtp inet n - n - - smtpd\n";
    } else {
        my $option;
        # Check Auth
        if ($obj->{enableSMTP_Auth}) {
            $option .= "-o smtpd_sasl_auth_enable=yes ";
        }
        $smtpPort = "smtp inet n - n - - smtpd $option\n";
    }

    # smtps port
    if ($obj->{enableSMTPS}) {
        my $option;
        # Check Auth
        if ($obj->{enableSMTPS_Auth}) {            $option .= "-o smtpd_sasl_auth_enable=yes ";
        }
        $smtpsPort = "smtps inet n - n - - smtpd -o smtpd_tls_wrappermode=yes $option\n";
    } else {
        $smtpsPort = "#smtps inet n - n - - smtpd -o smtpd_tls_wrappermode=yes\n
";
    }

    # submission(587) port
    if ($obj->{enableSubmissionPort}) {
        my $option;
        # Check Auth
        if ($obj->{enableSubmission_Auth}) {
            $option .= "-o smtpd_sasl_auth_enable=yes ";
        }
        $submissionPort = "submission inet n - n - - smtpd $option\n";
    } else {
        $submissionPort = "#submission inet n - n - - smtpd\n";
    }

    select $out;
    while (<$in>) {
        if (/smtp (.*)inet /o) {
            print $smtpPort;
        } elsif (/smtps(.*)inet /o) {
            print $smtpsPort;
        } elsif (/submission(.*)inet /o) {
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
