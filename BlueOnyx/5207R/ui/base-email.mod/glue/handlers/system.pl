#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: system.pl 

use CCE;

use Email;

use Sauce::Util;
use Sauce::Config;

# Globals.
my $Sendmail_mc = Email::SendmailMC;
# This actually no longer exists and hasn't been around for a while:
#my $Sendmail_flush_script = "/etc/rc.d/init.d/sendmail_flush_script";
my $Sendmail_sysconfig_file = "/etc/sysconfig/sendmail";

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

Sauce::Util::editfile($Sendmail_mc, *make_sendmail_mc, $obj );

# add rollback to recreate virtusertable.db
Sauce::Util::addrollbackcommand("/usr/bin/makemap hash $Email::VIRTUSER < $Email::VIRTUSER >/dev/null 2>&1");

system("/usr/bin/newaliases");

if (!Sauce::Util::replaceblock($Email::VIRTUSER,
			       '# Cobalt System Section Begin',
			       &make_virtuser_system($obj),
			       '# Cobalt System Section End')
    ) {
    $cce->warn('[[base-email.cantEditFile]]', { 'file' => $Email::VIRTUSER });
    $cce->bye('FAIL');
}

my $ret = Sauce::Util::editfile($Sendmail_sysconfig_file, *set_queue_time,
				$obj, $cce);
if(! $ret ) {
    $cce->bye('FAIL', 'cantEditFile', {'file' => $Sendmail_sysconfig_file});
} else {
    $cce->bye("SUCCESS");
}

exit 0;

sub make_sendmail_mc
{
	my $in  = shift;
	my $out = shift;

	my $obj = shift;

	my $privacy_line;
	my $maxMessageSize_line;
	my $maxRecipientsPerMessage_line;
	my $smartRelay_line;
	my $masqDomain_line;
	my $deliveryMode_line;
	my $delayChecks_line;

	my %Printed_line = ( privacy => 0,
		maxMessageSize => 0,
		maxRecipientsPerMessage => 0,
		smartRelay => 0,
		masqDomain => 0,
		delayChecks => 0 );
	my @Mailer_line = ();
	
	if( $obj->{privacy} ) {
	    $privacy_line = "define(`confPRIVACY_FLAGS', `noexpn noexpn authwarnings')\n";
	    
	} else {
	    $privacy_line = "define(`confPRIVACY_FLAGS', `authwarnings')\n";
	}

	if ($obj->{queueTime} eq 'immediate') {
	    $deliveryMode_line = "define(`confDELIVERY_MODE', `background')\n";
	} else {
	    $deliveryMode_line = "define(`confDELIVERY_MODE', `deferred')\n";
	}
	
	if( $obj->{maxMessageSize} ) {
	    # Max message size is in kilos. Sendmail needs bytes.
	    $maxMessageSize_line = "define(`confMAX_MESSAGE_SIZE',". $obj->{maxMessageSize}*1024 .")\n";
	} else {
	    $maxMessageSize_line = "define(`confMAX_MESSAGE_SIZE',0)\n";
	}

	if( $obj->{maxRecipientsPerMessage} ) {
	    # Maximum number of recipients per SMTP envelope:
	    $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',". $obj->{maxRecipientsPerMessage} .")\n";
	} else {
	    $maxRecipientsPerMessage_line = "define(`confMAX_RCPTS_PER_MESSAGE',0)\n";
	}

	if( $obj->{masqAddress} ) {
		$masqDomain_line = "MASQUERADE_AS(`". $obj->{masqAddress} ."')\n"
	} else {
		$masqDomain_line = "MASQUERADE_AS(`')\n";
	}

	if( $obj->{smartRelay} ) {
	    $smartRelay_line = "define(`SMART_HOST', `". $obj->{smartRelay} . "')\n";
	} else {
	    $smartRelay_line = "define(`SMART_HOST', `')\n";
	}

	if( $obj->{delayChecks} ) {
	    $delayChecks_line = "FEATURE(delay_checks)dnl\n";
	} else {
	    $delayChecks_line = "dnl FEATURE(delay_checks)dnl\n";
	}

	my $mailer_lines = 0;
	select $out;
	while( <$in> ) {
	    if( ( /^define\(`confPRIVACY_FLAGS'/o ||/^dnl define\(`confPRIVACY_FLAGS'/o ) && ! $Printed_line{'privacy'} ) {
                 $Printed_line{'privacy'}++;
		 print $privacy_line;
	    } elsif ( /^define\(`confMAX_MESSAGE_SIZE'/o || /^dnl define\(`confMAX_MESSAGE_SIZE'/o ) { #`
		$Printed_line{'maxMessageSize'}++;
		print $maxMessageSize_line;
	    } elsif ( /^define\(`confMAX_RCPTS_PER_MESSAGE'/o || /^dnl define\(`confMAX_RCPTS_PER_MESSAGE'/o ) {
		$Printed_line{'maxRecipientsPerMessage'}++;
		print $maxRecipientsPerMessage_line;
	    } elsif ( /^define\(`SMART_HOST'/o || /^dnl define\(`SMART_HOST'/o ) { #`
		$Printed_line{'smartRelay'}++;
		print $smartRelay_line;
	    } elsif ( /^MASQUERADE_AS/o || /^dnl MASQUERADE_AS/o ) {
		$Printed_line{'masqDomain'}++;
		print $masqDomain_line;
	    } elsif ( /^define\(`confDELIVERY_MODE'/o || /dnl ^define\(`confDELIVERY_MODE'/o ) { #`
		$Printed_line{'DeliveryMode'}++;
		print $deliveryMode_line;
	    } elsif ( /^FEATURE\(delay_checks/o || /^dnl FEATURE\(delay_checks/o ) {
		$Printed_line{'delayChecks'}++;
		print $delayChecks_line;
	    } elsif ( /^MAILER\(/o ) {
                $Mailer_line[$mailer_lines] = $_;
                $mailer_lines++;
	    } else {
		print $_;
	    }
	}

	foreach my $key ( keys %Printed_line ) {
		if ($Printed_line{$key} != 1) {
                        if ($key == 'maxRecipientsPerMessage') {
                            print $maxRecipientsPerMessage_line;
                        } elsif ($key == 'delayChecks') {
                            print $delayChecks_line;
                        } else {
			    $cce->warn("error_writing_sendmail_mc");
			    print STDERR "Writing sendmail_mc found $Printed_line{$key} occurences of $key\n";
                       }
		}
 	}

        if( $mailer_lines ) {
            foreach my $line (@Mailer_line) {
	        print $line;
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 