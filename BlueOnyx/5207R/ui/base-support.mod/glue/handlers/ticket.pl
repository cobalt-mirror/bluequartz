#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: ticket.pl
#
# This handler is run whenever a Ticket needs to be submitted.
#
# There are two scenarios how this handler will be called:
#
# 1.) Ticket contains *no* SOS-Report:
#     In that case /support/ticket sets 'ticket_trigger' directly.
#
# 2.) Bugreport DOES contain a SOS-Report:
#     In that case /support/ticket does NOT set 'ticket_trigger'.
#     Instead /support/ticket creates the ticket tempfile with the
#     email details and sets 'sos_generate'. At the end of the SOS-Report
#     generation the script /usr/sausalito/sbin/sosreport-helper.pl is
#     called and that then checks if the ticket tempfile is present.
#     If it is present, that script sets 'ticket_trigger', which will
#     trigger the run of this script here. 
#
# Yeah, the only easy day was yesterday. Hooorah!
#

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

#
#### No configureable options below!
#

use CCE;
use JSON;
use LWP::UserAgent; 
use HTTP::Request::Common qw{ POST };

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();
$obj = $cce->event_object();

$old = $cce->event_old();
$new = $cce->event_new();

# Get the System Object:
@sysoids = $cce->find('System');
($ok, $Support) = $cce->get($sysoids[0], 'Support');

# We're creating or modifying the Support object:
if (($cce->event_is_modify())) {
    &debug_msg("Preparing Ticket Sending.");

    if (-f $Support->{'ticket'}) {
        $ticket = '';
        &debug_msg("Opening " . $Support->{'ticket'});
        open (F, $Support->{'ticket'}) || die "Could not open $Support->{'ticket'}: $!";
        while ($line = <F>) {
            chomp($line);
            next if $line =~ /^\s*$/;                   # skip blank lines
            next if $line =~ /^#$/;                     # skip comments
            $ticket .= $line;
        }
        close(F);

        # Perform the POST action to https://support.blueonyx.it/index.php
        &debug_msg("Posting ticket to https://support.blueonyx.it/index.php");
        $ua       = LWP::UserAgent->new(ssl_opts => { verify_hostname => 1 });
        $ua->agent('BlueLinQ/1.0');
        $response = $ua->post( 'https://support.blueonyx.it/index.php', { 'ticket' => $ticket } );
        if ($response->is_success) {
            &debug_msg("Response from https://support.blueonyx.it/index.php was: SUCCESS " . $response->status_line);
        }
        else {
            &debug_msg("Response from https://support.blueonyx.it/index.php was: FAIL " . $response->status_line);
        }

        # Cleanup:
        system("/bin/rm -f $Support->{'ticket'}");

        # Cleanup CODB:
        ($ok, $badkeys, @info) = $cce->set($sysoids[0], 'Support', { 
                                                                    'ticket' => '', 
                                                                    'include_sos' => '0',
                                                                    'access_generate' => '0',
                                                                    'ticket_number' => '0'
                                                                    });
    }
    else {

        &debug_msg("File " . $Support->{'ticket'} . " not present!");

        # Cleanup CODB:
        ($ok, $badkeys, @info) = $cce->set($sysoids[0], 'Support', { 
                                                                    'ticket' => '', 
                                                                    'include_sos' => '0',
                                                                    'access_generate' => '0',
                                                                    'ticket_number' => '0'
                                                                    });

        # Exit with a walk of shame:
        $cce->bye('FAIL');
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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