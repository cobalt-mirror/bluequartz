#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: $
# Copyright 2010 Project BlueQuartz., All rights reserved.

use strict;
use CCE;
use Email;
use Sauce::Util;

# For now we don't need this:
#
#my $Postfix_cf = Email::PostfixMainCF;
#
#my $cce = new CCE( Domain => 'base-email' );
#
#$cce->connectfd();
#
#my $obj = $cce->event_object();
#
#if ($obj->{hostname} || $obj->{domainname} || 
#	($cce->event_property() eq 'acceptFor')) {
#	Sauce::Util::editfile($Postfix_cf, *make_main_cf, $obj );
#}

$cce->bye('SUCCESS');
exit(0);

sub make_main_cf
{
	my $in  = shift;
	my $out = shift;

	my $obj = shift;

	my $hostname = $obj->{hostname} . "." . $obj->{domainname};

	select $out;
	while(<$in>) {
		if (/^myhostname = /o) {
			print "myhostname = $hostname\n";
		} else {
			print $_;
		}
	}
	return 1;
}

