#!/usr/bin/perl -w -I/usr/sausalito/perl

use strict;

use CCE;
use Sauce::Util;
use Sauce::Validators;

#declares that should probably go elsewhere:
my $SNMPlink         = "/etc/rc.d/rc3.d/S40snmpd";
my $SNMPscript       = "/etc/rc.d/init.d/snmpd";
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
    		if (! -e $SNMPlink) { #Link doesn't exist, start service
	        	my $pid;
		        if (!defined($pid = fork)) {
		            $cce->warn("cannotFork", {msg => $!});
			    die;
		        } elsif ($pid) {
		            # i'm the parent
		        } else {
		            # otherwise I'm the child
		            symlink($SNMPscript,$SNMPlink) || (
			      $cce->warn("cannotCreateSymlink", {msg => "$SNMPlink: $!"}) && die);
			    system ("$SNMPscript start > /dev/null 2>&1") && (
			      $cce->warn("cannotStartSnmpServer", {msg =>"$!"}) && die);
		            exit;
		        }
		} else {
			# restart service to reflect changes
			system("$SNMPscript restart > /dev/null 2>&1");
		}

	
	} elsif (-e $SNMPlink) {	#NO SERVICE, Link exists: kill service and remove link
		#kill service
		system ("$SNMPscript stop > /dev/null 2>&1") && 
		$cce->warn("cannotStopSnmpService", {msg=>"$!"});
		unlink($SNMPlink) ||
		$cce->warn("cannotBreakLink", {msg => "$SNMPlink: $!"});
	} else {	#Nothing Changed...
	}
			
	
}

$cce->bye('SUCCESS');
exit 0;

sub validate_community {
	(shift !~ /^[\w\.\-]*$/o) ? (return 0) : (return 1);
}	
sub set_snmp_community
# Sets the community with read and write access
# Arguments: read-only community, read/write community
#   If a community name is set to empty, then disable that community
# Example: $ret = set_snmp_community("mypublic","");  (this disables the 
#   r/w community and sets the read-only community to "mypublic").
{

    my ( $fin, $fout, $read_community, $write_community )=@_;
    my ( $foundro, $foundrw );

    while( <$fin> ) {
      if (/^[\#\s]*rocommunity\s+/o ) {
	# Change the read-only community
	if ($read_community) {
	  print $fout "rocommunity\t$read_community\n";
	}
	elsif (/^roc/o) {
	  # only comment it out if it isn't already
	  print $fout "#$_";
	} else {
	  print $fout "$_";
	}
        $foundro = 1;
      } 
      elsif (/^[\#\s]*rwcommunity\s+/o) {
	# Change the read/write community (commented out by default)
	if ($write_community) {
	  print $fout "rwcommunity\t$write_community\n";
	}
	elsif (/^rwc/o) {
	  # only comment it out if it isn't already
	  print $fout "#$_";
	} else {
          print $fout "$_";
        }
        $foundrw = 1;
      }
      elsif (/^trap community:/o) {
	# Trap community is not documented.  We don't want to just
	# leave it as "public" if we changed community names...
	my $trap_community;
	# first try setting it to the write community
	$trap_community = $write_community;
	# if write_community wasn't set, set it to the read community
	$trap_community ||= $read_community;
	# otherwise...well, set it to something.  No communities are
	# set, so we'll set it to the default of "public".
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
