#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: system.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use CCE;

use Email;

use Sauce::Util;
use Sauce::Config;

# Globals.
my $Sendmail_cf = Email::SendmailCF;
my $Sendmail_flush_script = "/etc/rc.d/init.d/sendmail_flush_script";
my $Sendmail_args_file = "/etc/mail/sendmail.args";

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

Sauce::Util::editfile($Sendmail_cf, *make_sendmail_cf,     $obj );

# add rollback to recreate virtusertable.db
Sauce::Util::addrollbackcommand("/usr/bin/makemap hash $Email::VIRTUSER < " .
				"$Email::VIRTUSER >/dev/null 2>&1");

if (!Sauce::Util::replaceblock($Email::VIRTUSER,
	'# Cobalt System Section Begin',
	&make_virtuser_system($obj),
	'# Cobalt System Section End')
	) {
	$cce->warn('[[base-email.cantEditFile]]', { 'file' => $Email::VIRTUSER });
	$cce->bye('FAIL');
}

set_queue_time($obj->{queueTime},$cce);

$cce->bye('SUCCESS');

exit 0;

sub make_sendmail_cf
{
	my $in  = shift;
	my $out = shift;

	my $obj = shift;

	my $privacy_line;
	my $maxMessageSize_line;
	my $smartRelay_line;
	my $masqDomain_line;
	my $deliveryMode_line;

	my %Printed_line = ( privacy => 0,
	                     maxMessageSize => 0,
						 smartRelay => 0,
						 masqDomain => 0 );

	if( $obj->{privacy} ) {
		$privacy_line = "O PrivacyOptions=authwarnings,noexpn,novrfy\n";
	} else {
		$privacy_line = "O PrivacyOptions=authwarnings\n";
	}


	if ($obj->{queueTime} eq 'immediate') {
	    $deliveryMode_line = "O DeliveryMode=background\n";
	} else {
	    $deliveryMode_line = "O DeliveryMode=deferred\n";
	}

	if( $obj->{maxMessageSize} ) {
		# Max message size is in kilos. Sendmail needs kilos.
		$maxMessageSize_line = "O MaxMessageSize=" . $obj->{maxMessageSize}*1024 ."\n";
	} else {
		$maxMessageSize_line = "#O MaxMessageSize=0\n";
	}

	if( $obj->{smartRelay} ) {
		$smartRelay_line = "DS" . $obj->{smartRelay} . "\n";
	} else {
		$smartRelay_line = "DS\n";
	}

	if( $obj->{masqAddress} ) {
		$masqDomain_line = "DM" . $obj->{masqAddress} . "\n";
	} else {
		$masqDomain_line = "DM\n";
	}

	if( $obj->{smartRelay} ) {
		$smartRelay_line = "DS" . $obj->{smartRelay} . "\n";
	} else {
		$smartRelay_line = "DS\n";
	}

	select $out;
	while( <$in> ) {
		if( /^O PrivacyOptions=/o ) {
			$Printed_line{'privacy'}++;
			print $privacy_line;
		} elsif ( /O MaxMessageSize/o || /\#O MaxMessageSize/o ) {
			$Printed_line{'maxMessageSize'}++;
			print $maxMessageSize_line;
		} elsif ( /^DS/o ) {
			$Printed_line{'smartRelay'}++;
			print $smartRelay_line;
		} elsif ( /^DM/o ) {
			$Printed_line{'masqDomain'}++;
			print $masqDomain_line;
		} elsif ( /^O DeliveryMode=/ ) {
			$Printed_line{'DeliveryMode'}++;
			print $deliveryMode_line;
		} else {
			print $_;
		}
	}

	foreach my $key ( keys %Printed_line ) {
		if ($Printed_line{$key} != 1) {
			$cce->warn("error_writing_sendmail_cf");
			print STDERR "Writing sendmail_cf found $Printed_line{$key} occurences of $key\n";
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
	my $period = shift;
	my $cce = shift;
	my $fh = new IO::File;
	my $args;

	my $period_hash = {
	        'immediate' => '15m',
		'quarter-hourly' => '15m',
		'half-hourly' => '30m',
		'hourly' => '1h',
		'quarter-daily' => '6h',
		'daily' => '1d'
	};

	$fh->open("<$Sendmail_args_file");
	if( $fh->opened() ) {
		$args = $fh->getline();
		chomp($args);
	} else {
		$args = "-bd ";
	}

	$fh->close();

	Sauce::Util::modifyfile($Sendmail_args_file);
	$fh = new IO::File(">$Sendmail_args_file");
	if(! $fh ) {
		# Erk. Bad.
		$cce->warn("couldnt_create_args_file");
		return 0;
	}

	# Strip out the -q[arg] part of the command line.
	$args =~ s/-q[^ ]*//;
	# And add on ours.
	$args .= '-q' . $period_hash->{$period};

	$fh->print($args . "\n");
	$fh->close();
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
