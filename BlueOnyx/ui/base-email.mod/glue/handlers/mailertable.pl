#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: mailertable.pl 827 2009-03-17 ricky
# Copyright 2009 Bluapp AB, All rights reserved.

use strict;
use CCE;
use Email;
use Sauce::Util;

my $cce = new CCE( Domain => 'base-email' );

$cce->connectfd();

my $Sendmail_mailertable = $Email::MAILERTABLE;
my $sys_obj;
my $sys_oid;
my $mx_oids;
my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/email.mailertable");

my @mx_oids = $cce->find("mx2");
$DEBUG && print STDERR "oids: @mx_oids\n";

my ($ok, $obj) = $cce->get($sys_oid, "Email");


# create the mailertable file
my $mailertable_list = &make_mailertable(\@mx_oids);

# add rollback so there is no need to copy mailertable.db for rollback
Sauce::Util::addrollbackcommand("/usr/bin/makemap hash $Sendmail_mailertable < ".
				"$Sendmail_mailertable >/dev/null 2>&1");

if (!Sauce::Util::replaceblock($Sendmail_mailertable, 
	'# Cobalt Mailertable Section Begin', $mailertable_list, 
	'# Cobalt Mailertable Section End')
   	) {
	$cce->warn('[[base-email.cantEditFile]]', { 'file' => $Email::MAILERTABLE });
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);


sub make_mailertable
{
	my $mx_oids = shift;
	my $out = "";	

	# setup mailertable for all mx_oids
	for my $mx_oid (@{ $mx_oids }) {	
	    $DEBUG && print STDERR "Mailertable oid: $mx_oid\n";
	    my ($ok, $relayOid) = $cce->get($mx_oid);
	    my $domain = $relayOid->{domain};
	    my $mapto = $relayOid->{mapto};
	    $DEBUG && print STDERR "Mailertable date: $domain->$mapto\n";
	    $out .= "$domain\tsmtp:$mapto\n";
	}
	return $out;
}
