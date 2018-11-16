#!/usr/bin/perl
# $Id: sosreport-helper.pl
#
# This script is used by /usr/sausalito/sbin/sosreport.sh to 
# update CODB about the presence and whereabouts of SOS-Reports.
#
$DEBUG = '0';
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use lib '/usr/sausalito/perl';
use CCE;
use vars qw($opt_f);
use Getopt::Long;
use LWP::UserAgent; 
use HTTP::Request::Common qw{ POST };
use URI::Escape;
$cce = new CCE;
$cce->connectuds();

GetOptions(	'flush=s' => \$flush, 
			'report=s' => \$report,
			'internal=s' => \$internal,
			'external=s' => \$external,
           	);

# Find 'System' Object:
@System = ();
(@System) = $cce->findx('System');

# Get the 'System' Object:
($ok, $system) = $cce->get($System[0]);

# Get the 'Support' Object:
($ok, $Support) = $cce->get($System[0], 'Support');

&debug_msg("report=$report flush=$flush internal=$internal external=$external"); 

# Find out primary IP:
@interfaces = $cce->find('Network', '', ('real' => 1, 'enabled' => 1), (), 'ascii', 'device');
($ok, $NET) = $cce->get($interfaces[0]);
$ipaddr = $NET->{'ipaddr'};

if ($flush ne "") {
		# Flushing CODB:
		$epoc = time();
		&debug_msg("Flushing CODB.");
		($ok) = $cce->set($System[0], 'Support', { 
			'sos_present' => '0',
			'sos_internal' => '',
			'sos_external' => '',
			'sos_epoch' => '0'
			});

		# Delete and recreate /usr/sausalito/ui/web/debug
		&debug_msg("Deleting /usr/sausalito/ui/web/debug"); 
		system("/bin/rm -Rf /usr/sausalito/ui/web/debug");
		&debug_msg("Creating /usr/sausalito/ui/web/debug"); 
		system("/bin/mkdir /usr/sausalito/ui/web/debug");
		system("/bin/chmod 755 /usr/sausalito/ui/web/debug");

		# Tell cce everything is okay
		$cce->bye('SUCCESS');
		exit(0);
}

if ($report ne "") {
	print "Reporting.\n";
	if (($internal eq "") || ($external eq "")) {
		print "Please set the internal and external report URL's!\n";
		$cce->bye('FAIL');
		exit(1);
	}
	else {
		# Updating CODB with the report URLs:
		&debug_msg("Updating CODB with the SOS-Report URLs.");
		$epoc = time();
		($ok) = $cce->set($System[0], 'Support', { 
			'sos_present' => '1',
			'sos_internal' => $internal,
			'sos_external' => $external,
			'sos_epoch' => $epoc,
			'sos_trigger' => '0'
			});

		# Fixing permissions:
		system("/bin/chmod -R 755 /usr/sausalito/ui/web/debug");

		# Reporting SOS-Report location to NewLinQ:
		# URL Scheme: http://newlinq.blueonyx.it/sosreport/SERIAL#HERE/<IP>/<debugdir-to-html>
		$repURL = 'http://newlinq.blueonyx.it/sosreport/' . $system->{'serialNumber'} . '/' . $ipaddr . '/' . uri_escape($external);
        &debug_msg("Registering SOS-Report with: $repURL");
        $ua       = LWP::UserAgent->new();
        $ua->agent('BlueLinQ/1.0');
        $response = $ua->get( 'https://support.blueonyx.it/index.php', { 'ticket' => $ticket } );
        if ($response->is_success) {
            &debug_msg("Response from $repURL was: SUCCESS " . $response->status_line);
        }
        else {
            &debug_msg("Response from $repURL was: FAIL " . $response->status_line);
        }

		# Triggering processing:
		if (($Support->{'bugreport'} ne "") && (-f $Support->{'bugreport'}) && ($Support->{'include_sos'} == "1")) {
			# Triggering Bugreport processing.
			&debug_msg("Triggering Bugreport processing.");
			($ok) = $cce->set($System[0], 'Support', { 
				'bugreport_trigger' => '1'
				});
		}
		elsif (($Support->{'ticket'} ne "") && (-f $Support->{'ticket'}) && ($Support->{'include_sos'} == "1")) {
			# Triggering Ticket processing.
			&debug_msg("Triggering Ticket processing.");
			($ok) = $cce->set($System[0], 'Support', { 
				'ticket_trigger' => '1'
				});
		}
		else {
			&debug_msg("Not triggering Bugreport or Ticket processing, as neither was requested. Or we have no tempfile either."); 
		}

		# Tell cce everything is okay
		$cce->bye('SUCCESS');
		exit(0);
	}
}

# Tell cce everything is okay
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
