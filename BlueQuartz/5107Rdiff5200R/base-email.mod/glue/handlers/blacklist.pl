#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: blacklist.pl 827 2009-03-17 ricky
# Copyright 2009 Bluapp AB, All rights reserved.

use strict;
use CCE;
use Email;
use Sauce::Util;
use Sauce::Config;

# Globals.
my $Sendmail_mc = Email::SendmailMC;

my $cce = new CCE( Domain => 'base-email' );

my $DEBUG = 0;
$DEBUG && open(STDERR, ">/tmp/email_blacklist.debug");
$DEBUG && warn `date`;

$cce->connectfd();

$DEBUG && warn "SENDMAIL: $Sendmail_mc\n";

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

my $ret = Sauce::Util::editfile($Sendmail_mc, *make_sendmail_mc, $obj, $old, $new );

if(! $ret ) {
    $cce->bye('FAIL', 'cantEditFile', {'file' => $Sendmail_mc});
    exit(0);
} 


$cce->bye('SUCCESS');
exit(0);


sub make_sendmail_mc
{
    my $in  = shift;
    my $out = shift;
    
    my $obj = shift;

    my $old = shift;
    my $new = shift;
    
    my $blacklistHost;
    my $deferTemporary;
    my $active;
    my $prefix;
    my $defer;
    my $searchString;
    my %Printed_line = ( blacklistHost => 0);
    my $mailer_lines = 0;
    my @Mailer_line = ();

    if( !$old->{active}) {
	$prefix = "dnl ";
    }
    if( !$old->{blacklistHost}) {
	$searchString = $prefix . "FEATURE\\(dnsbl, \\`". $new->{blacklistHost} ."\\'";
    } else {
	$searchString = $prefix . "FEATURE\\(dnsbl, \\`". $old->{blacklistHost} ."\\'";
    }
    
    if( $obj->{active} ) {
	$prefix = "";
    } else {
	$prefix = "dnl ";
    }
    if( $obj->{deferTemporary} ) {
	$defer = "`t'";
    } else {
	$defer = "";
    }
    if( $obj->{blacklistHost} ) {
	$blacklistHost = $prefix . "FEATURE(dnsbl, `". $obj->{blacklistHost} ."',,$defer)\n";
	
    } else {
	$blacklistHost = "";
    }
    
    select $out;
    while( <$in> ) {
	if( /^$searchString/o ) {
	    $Printed_line{'blacklistHost'}++;
	    print $blacklistHost;
	} elsif ( /^MAILER\(/o ) {
	    $Mailer_line[$mailer_lines] = $_;
	    $mailer_lines++;
	} else {
	    print $_;
	}
    }

    foreach my $key ( keys %Printed_line ) {
	if ($Printed_line{$key} != 1) {
	    print $blacklistHost;
	}
    }
    
    if( $mailer_lines ) {
	foreach my $line (@Mailer_line) {
	    print $line;
	}
    }
    
    return 1;
}
