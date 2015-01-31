#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: snmp.pl

use CCE;
use Sauce::Util;
use Sauce::Validators;
use Sauce::Config;
use Sauce::Service;

#declares that should probably go elsewhere:
my $SNMPconf	     = "/etc/snmp/snmpd.conf";

my $cce = new CCE ( Domain => "base-snmp", Namespace => "Snmp" );

$cce->connectfd();

if( ! $cce->{destroyflag} ) {
	my $snmp_oid = $cce->event_oid();
	my $snmp_obj = $cce->event_object();
	my $read_community = $snmp_obj->{readCommunity};
	my $write_community = $snmp_obj->{readWriteCommunity};
	
	# Validate the communities.
	if (!validate_community($read_community)) {
		$cce->baddata(0, 'readCommunity', "readSnmpCommunityField_invalid", {invalidValue => $read_community});
		$cce->bye("FAIL");
		exit 1;
	}
	if (!validate_community($write_community)) {
		$cce->baddata(0, 'readWriteCommunity', "readWriteSnmpCommunityField_invalid", {invalidValue => $write_community});
		$cce->bye("FAIL");
		exit 1;
	}
	if ((!$read_community) && (!$write_community) && ($snmp_obj->{enabled})) {
		$cce->baddata(0, 'enabled', "readNorWriteSet"); 
		$cce->bye("FAIL");
		exit 1;
	}
	if ($read_community eq $write_community && ($snmp_obj->{enabled})) {
		$cce->set($snmp_oid, "Snmp", {"readCommunity" => ""});
		$cce->warn("readEqualWrite");
		$cce->bye("SUCCESS");
		exit;
	}
	

	# update the config file
	Sauce::Util::editfile( $SNMPconf, \&set_snmp_community, $read_community, $write_community);

	if ($snmp_obj->{enabled}) {
		#start service
		Sauce::Service::service_set_init('snmpd', 1);
		Sauce::Service::service_toggle_init('snmpd', 1);
	}
	else {	
		Sauce::Service::service_run_init('snmpd', 'stop');
		Sauce::Service::service_set_init('snmpd', 0);
	}
}

$cce->bye('SUCCESS');
exit 0;

sub get_snmp_server_on {
	# Is the snmp server set to be on?
	# Returns 1 if the snmp server is activated, 0 if deactivated
	#   This reflects what ought to be, *not* what is
	# Arguments: none
	# Side effects: none
	if (Sauce::Service::service_get_init($service) == -1) {
		return 0;
	}
	else {
		return 1;
	}
}


sub validate_community {
	(shift !~ /^[\w\.\-]*$/o) ? (return 0) : (return 1);
}	


sub set_snmp_community
# Sets the community with read and write access
# Arguments: read-only community, read/write community
# If a community name is set to empty, then disable that community
# Example: $ret = set_snmp_community("mypublic","");  (this disables the 
# r/w community and sets the read-only community to "mypublic").
{
	my ( $fin, $fout, $read_community, $write_community )=@_;
	my ( $foundro, $foundrw );

	while( <$fin> ) {
		if (/^[\#\s]*rocommunity\s+/o) {
			# Change the read-only community
			if ($read_community) {
				print $fout "rocommunity\t$read_community\n";
			} elsif (/^c/o) {
				# only comment it out if it isn't already
				print $fout "#$_";
			} else {
				print $fout "$_";
			}
			$foundro = 1;
		} elsif (/^[\#\s]*rwcommunity\s+/o) {
			# Change the read/write community (commented out by default)
			if ($write_community) {
				print $fout "rwcommunity\t$write_community\n";
			} elsif (/^c/o) {
				# only comment it out if it isn't already
				print $fout "#$_";
			} else {
				print $fout "$_";
			}
			$foundrw = 1;
		} elsif (/^trap community:/o) {
			#
			# Trap community is not documented.	We don't want
			# to just leave it as "public" if we changed community
			# names...
			#
			my $trap_community;

			# first try setting it to the write community
			$trap_community = $write_community;

			#
			#if write_community wasn't set, set it to the read
			# community
			#
			$trap_community ||= $read_community;

			#
			# otherwise...well, set it to something.  No communities
			# are set, so we'll set it to the default of "public".
			#
			$trap_community ||= "public";

			print $fout "trap community:\t$trap_community\n";
		} elsif (/^com2sec\s*.*$/) {
			# comment out any com2sec settings we use the
			# wrapper functions to set ro and rw communities
			print $fout "#$_";
		} else {
			print $fout $_;
		} 
	}

	# input the communities if they were not found
	( ! $foundro && ! $foundrw ) ? print $fout "\n\n# Communities\n" : 0;
	( ! $foundro ) ? print $fout "rocommunity\t$read_community\n" : 0;
	( ! $foundrw ) ? print $fout "rwcommunity\t$write_community\n" : 0;

	return 1;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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