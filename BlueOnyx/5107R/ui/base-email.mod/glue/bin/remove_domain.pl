#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: remove_domain.pl 805 2006-06-25 11:32:18Z shibuya $

use strict;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

(my $ok, my $sys_obj) = $cce->get($oids[0]);
($ok, my $obj) = $cce->get($oids[0], 'Email');
unless ($ok and $obj) {
	$cce->bye('FAIL');
	exit 1;
}

my %relayAllow;
map { $relayAllow{$_} = 1; } $cce->scalar_to_array($obj->{relayFor});
if ($sys_obj->{domainname} ne '' && !$relayAllow{$sys_obj->{domainname}}) {
	# remove domainname from access
	system("perl -pi -e 's|^$sys_obj->{domainname}\tRELAY||g' /etc/mail/access");
	system("/usr/sbin/makemap hash /etc/mail/access.db < /etc/mail/access");
}

$cce->bye('SUCCESS');
exit(0);

