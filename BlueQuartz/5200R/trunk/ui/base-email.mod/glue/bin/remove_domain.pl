#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: remove_domain.pl 1495 2010-06-25 09:15:33Z shibuya $

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
	system("perl -pi -e 's|^$sys_obj->{domainname}\tRELAY||g' /etc/postfix/access");
	system("/usr/sbin/postalias hash:/etc/postfix/access");
}

$cce->bye('SUCCESS');
exit(0);

