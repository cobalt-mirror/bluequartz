#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: system.pl 1523 2010-09-06 02:43:58Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use CCE;

use Email;

use Sauce::Util;
use Sauce::Config;

# Globals.
my $Postfix_cf = Email::PostfixMainCF;

# These should be globals..

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );

$cce->connectfd();

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

# make sure masqAddress is not set to localhost
if ($obj->{masqAddress} =~ /^localhost$/i)
{
	$cce->baddata($cce->event_oid(), 
			'masqAddress', 'masqAddressCantBeLocalhost');
	$cce->bye('FAIL');
	exit(1);
}

my $sys_obj = ( $cce->get( ($cce->find("System"))[0] ) )[1];

Sauce::Util::editfile($Postfix_cf, *make_main_cf, $obj );

# add rollback to recreate virtusertable.db
Sauce::Util::addrollbackcommand("/usr/bin/postmap hash:$Email::VIRTUSER >/dev/null 2>&1");

if (!Sauce::Util::replaceblock($Email::VIRTUSER,
	'# Cobalt System Section Begin',
	&make_virtuser_system($obj),
	'# Cobalt System Section End')
	) {
	$cce->warn('[[base-email.cantEditFile]]', { 'file' => $Email::VIRTUSER });
	$cce->bye('FAIL');
}
system("/usr/sbin/postmap hash:$Email::VIRTUSER >/dev/null 2>&1");

$cce->bye("SUCCESS");

exit 0;

sub make_main_cf
{
	my $in  = shift;
	my $out = shift;

	my $obj = shift;

	my $maxMessageSize_line;
	my $smartRelay_line;
	my $fallbackRelay_line;
	my $masqDomain_line;

	my %Printed_line = (
	                     maxMessageSize => 0,
			     smartRelay => 0,
			     fallbackRelay => 0,
			     masqDomain => 0,
			     localHeader => 0 );


	if( $obj->{maxMessageSize} ) {
		# Max message size is in kilos. Sendmail needs kilos.
		$maxMessageSize_line = "message_size_limit = " . $obj->{maxMessageSize}*1024 ."\n";
	} else {
		$maxMessageSize_line = "message_size_limit = 0\n";
	}

	if( $obj->{smartRelay} ) {
		if ( $obj->{smartRelay} =~ /^(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])$/ ) {
			$smartRelay_line = "relayhost = [" . $obj->{smartRelay} . "]\n";
		} else {
			$smartRelay_line = "relayhost = " . $obj->{smartRelay} . "\n";
		}
	} else {
		$smartRelay_line = "#relayhost = \n";
	}

	if( $obj->{fallbackRelay} ) {
		if ( $obj->{fallbackRelay} =~ /^(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])$/ ) {
			$fallbackRelay_line = "fallback_relay = [" . $obj->{fallbackRelay} . "]\n";
		} else {
			$fallbackRelay_line = "fallback_relay = " . $obj->{fallbackRelay} . "\n";
		}
	} else {
		$fallbackRelay_line = "#fallback_relay = \n";
	}

	if( $obj->{masqAddress} ) {
		system("echo \'/@(.*)\$/	\@$obj->{masqAddress}\' >/etc/postfix/sender_canonical");
		$masqDomain_line = "sender_canonical_maps = regexp:/etc/postfix/sender_canonical\n";
		$localHeader_line = "local_header_rewrite_clients = static:all\n";
	} else {
		system("cat /dev/null  >/etc/postfix/sender_canonical");
		$masqDomain_line = "#sender_canonical_maps = regexp:/etc/postfix/sender_canonical\n";
		$localHeader_line = "#local_header_rewrite_clients = static:all\n";
	}

	my $found = 0;
	select $out;
	while( <$in> ) {
		if (/^# Add configuration for BlueQuartz by init script./o) {
			$found = 1;
		} elsif (!$found) {
			print $_;
		}

		if ($found) {
			if ( /^message_size_limit =/o || /^\#message_size_limit =/o ) {
				$Printed_line{'maxMessageSize'}++;
				print $maxMessageSize_line;
			} elsif ( /^relayhost =/o || /^\#relayhost =/o ) {
				$Printed_line{'smartRelay'}++;
				print $smartRelay_line;
			} elsif ( /^fallback_relay =/o || /^\#fallback_relay =/o ) {
				$Printed_line{'fallbackRelay'}++;
				print $fallbackRelay_line;
			} elsif ( /^sender_canonical_maps =/o || /^\#sender_canonical_maps =/o ) {
				$Printed_line{'masqDomain'}++;
				print $masqDomain_line;
			} elsif ( /^local_header_rewrite_clients =/o || /^#local_header_rewrite_clients =/o ) {
				$Printed_line{'localHeader'}++;
				print $localHeader_line;
			} else {
				print $_;
			}
		}
	}

	foreach my $key ( keys %Printed_line ) {
		if ($Printed_line{$key} != 1) {
#			$cce->warn("error_writing_postfix_main_cf");
$cce->warn($key);
			print STDERR "Writing postfix_main_cf found $Printed_line{$key} occurences of $key\n";
		}
	}

	return 1;
}

sub make_virtuser_system
{
	my $obj = shift;
	my $out = "";

	my %routes = $cce->scalar_to_array( $obj->{routes} );

	while( my($domain,$target) = each %routes ) {
		$out .= "@".$domain."\t%1@".$target."\n";
	}
	return $out;
}

sub set_queue_time
{
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
