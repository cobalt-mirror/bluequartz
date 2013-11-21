#!/usr/bin/perl -w -I/usr/sausalito/perl

use Sauce::Service;
use CCE;

my $cce = new CCE( Namespace => 'Email',
                      Domain => 'base-email' );

$cce->connectfd();

# for smtps
system("cat /etc/admserv/certs/key /etc/admserv/certs/certificate > /etc/pki/tls/certs/postfix.pem");
chmod 0600, "/etc/pki/tls/certs/postfix.pem";
Sauce::Service::service_run_init('postfix', 'restart') if $obj->{enableSMTP} || $obj->{enableSMTPS} || $obj->{enableSMTPAuth} || $obj->{enableSubmissionPort};


# for dovecot
system("/bin/cp /etc/admserv/certs/key /etc/pki/dovecot/private/dovecot.pem");
system("/bin/cp /etc/admserv/certs/certificate /etc/pki/dovecot/certs/dovecot.pem");

Sauce::Service::service_run_init('dovecot', 'restart') if $obj->{enableIMAPS} || $obj->{enablePOPS};

$cce->bye("SUCCESS");
exit(0);

