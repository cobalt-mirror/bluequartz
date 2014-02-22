#!/usr/bin/perl -w -I/usr/sausalito/perl

use Sauce::Service;
use Sauce::Util;
use CCE;

my $cce = new CCE( Namespace => 'Email',
                      Domain => 'base-email' );

$cce->connectfd();

# for smtps
system("echo \"\" > /etc/admserv/certs/blank.txt"); 
system("cat /etc/admserv/certs/key /etc/admserv/certs/blank.txt /etc/admserv/certs/certificate > /usr/share/ssl/certs/sendmail.pem");
chmod 0600, "/usr/share/ssl/certs/sendmail.pem";
Sauce::Service::service_run_init('sendmail', 'restart');

# Handle Dovecot key and cert:
system("/bin/cp /etc/admserv/certs/key /etc/pki/dovecot/private/dovecot.pem");
system("/bin/cp /etc/admserv/certs/certificate /etc/pki/dovecot/certs/dovecot.pem");

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

Sauce::Service::service_run_init('dovecot', 'restart');

# Restart xinetd as well, so that ProFTPd gets to know the new cert:
Sauce::Service::service_run_init('xinetd', 'restart');

$cce->bye("SUCCESS");
exit(0);

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

