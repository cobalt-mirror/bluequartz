#!/usr/bin/perl -w -I. -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/pppoe/

# Author: Phil Ploquin
# Copyright 2000, Cobalt Networks.  All rights reserved.

use strict;
use CCE;
use Sauce::Util;

#
# Constants
#
my $pppoeConf   = "/etc/ppp/pppoe.conf";
my $papSecrets  = "/etc/ppp/pap-secrets";
my $chapSecrets  = "/etc/ppp/chap-secrets";

my $pidFile     = "/var/run/pppoe.pid";
my $pppPidFile  = "/var/run/ppp0.pid";
my $stopFile    = "/var/run/pppoe.stop";
my $killall     = "/usr/bin/killall";
my $startScript = "/usr/sbin/adsl-connect";
my $stopScript	= "/etc/rc.d/init.d/adsl";
my $startLink   = "/etc/rc.d/rc3.d/S91pppoe";
my $stop0Link   = "/etc/rc.d/rc0.d/K55pppoe";
my $stop6Link   = "/etc/rc.d/rc6.d/K55pppoe";

## In order to use PPPoE, the ethernet port we use must have an IP.
## So if the user chooses say eth1 and they have not set an IP for it,
## here is what I think are safe values as defaults
my $defaultIP      = "10.9.1.234";
my $defaultNetmask = "255.255.255.0";

#
# main
#
my $cce = new CCE;
$cce->connectfd();

my ($system_oid) = $cce->find("System");
my ($ok, $pppoe, $old, $new) = $cce->get($system_oid, 'Pppoe');

&killPppoe;
&startPppoe if ($pppoe->{connMode} ne 'off');

$cce->bye("SUCCESS");
exit(0);

#
# startPppoe :
#   Create the conf file, make the symlinks, and check to see if the ethernet
#   device they want to use has an IP.  If not, give it one.
#
sub startPppoe
{
  my $confFile = "";

  if ($pppoe->{'ethNumber'} > 0)
  {
    # For us to use PPPoE, the 'E' has to have an IP
    my @network_oids = $cce->find("Network",
                                  {'device' => 'eth' . $pppoe->{'ethNumber'}});
    my $eth;
    ($ok, $eth) = $cce->get($network_oids[0]);

    if (($eth->{'ipaddr'} eq "") || ($eth->{'ipaddr'} eq "0.0.0.0"))
    {
      $cce->set($network_oids[0], "",
                {'ipaddr' => $defaultIP, 'netmask' => $defaultNetmask});
    }
  }

  $confFile .= 'ETH=eth' . $pppoe->{'ethNumber'} . "\n";
  $confFile .= "USER='"  . $pppoe->{'userName'}  . "'\n";
  if ($pppoe->{'connMode'} eq 'demand')
  {
    $confFile .= "DEMAND=300";
  }
  else
  {
    $confFile .= "DEMAND=no";
  }

  open (OUTFILE, ">$pppoeConf") || die ("Can't write to config file!!");
  print OUTFILE<<end_of_conf;
#
# $pppoeConf
#
$confFile
CONNECT_TIMEOUT=60
CONNECT_POLL=6
PING="."
PIDFILE=$pidFile
TERMINATEFILE=$stopFile
SYNCHRONOUS=no
CLAMPMSS=1412
LCP_INTERVAL=20
LCP_FAILURE=3
PPPOE_TIMEOUT=80
FIREWALL=NONE
end_of_conf
  close OUTFILE;

  if ($new->{userName} || $new->{password}) {
	# Pap
	Sauce::Util::editfile( $papSecrets, *edit_pap_secrets, $pppoe->{userName}, 
		$pppoe->{password}, '# start base-pppoe.mod. Do not edit', 
		'# end base-pppoe.mod. Do not edit' )
      	|| return 0;

	# Chap
	Sauce::Util::editfile( $chapSecrets, *edit_chap_secrets, $pppoe->{userName}, 
		$pppoe->{password}, '# start base-pppoe.mod. Do not edit', 
		'# end base-pppoe.mod. Do not edit' )
      	|| return 0;
  }

  symlink($stopScript, $startLink);
  symlink($stopScript, $stop0Link);
  symlink($stopScript, $stop6Link);

  if(!fork()) {
	exec("$startScript &>/dev/null");
	exit(0);
  }

  return 1;
}

#
# killPppoe :
#   Kill whatever pppoe interfaces exist, remove the symlinks
#
sub killPppoe
{
  # adsl-connect script that adsl-start starts restarts pppd after 
  # it dies until it detects this stop files' existance.
  open (OUTFILE, ">$stopFile");
  print OUTFILE "Blah\n";
  close (OUTFILE);

  # kill all pppd if the pid file is there
  if ( -f $pppPidFile)
  {
    system ("$killall pppd &>/dev/null");
  }

  unlink($startLink, $stop0Link, $stop6Link);
}

sub edit_pap_secrets {

        my $in = shift;
        my $out = shift;
        my $username = shift;
        my $password = shift;
	my $smark = shift;
	my $emark = shift;

        while(<$in>) {
                if (not(/^$smark$/)) {
                        print $out $_;
                } else {
                        last;
                }
        }

        print $out "$smark\n";

	# escape the username and password
	$username = escape_me($username);
	$password = escape_me($password);
	

        # read next line to preserve password if a new one was not given
        $_ = <$in>;

	# split on the tab*tab
        my @fields = split /\t\*\t/, $_;

	# save old password if we need, otherwise use new one & quote it
	if ($password eq "") {
		$password = $fields[1];
		print $out "\"$username\"\t*\t$password";
	} else {
		print $out "\"$username\"\t*\t\"$password\"\n";
	}

        print $out "$emark\n";

        # print out rest of file if there is any
        while(<$in>) {
                if(not(/^$emark$/)) {
                        print $out $_;
                }
        }

        return 1;
}

sub edit_chap_secrets {

        my $in = shift;
        my $out = shift;
        my $username = shift;
        my $password = shift;
	my $smark = shift;
	my $emark = shift;

        while(<$in>) {
                if (not(/^$smark$/)) {
                        print $out $_;
                } else {
                        last;
                }
        }

        print $out "$smark\n";

	# escape the username and password
	$username = escape_me($username);
	$password = escape_me($password);

        # read next line to preserve password if a new one was not given
        $_ = <$in>;

	# save old password if we need, otherwise use new one & quote it
	if (!$password && (/\*\"\s+\"([^\"]+)\"/)) {
		$password = $1;
	}
	print $out '* "*" "'.$password."\"\n";

        print $out "$emark\n";

        # print out rest of file if there is any
        while(<$in>) {
                if(not(/^$emark$/)) {
                        print $out $_;
                }
        }

        return 1;
}

sub escape_me {
	my $foo = shift;
	$foo =~ s/\\/\\\\/g;
	$foo =~ s/\n/\\n/g;
	$foo =~ s/"/\\"/g;
	return $foo;
}

1;
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
