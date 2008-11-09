#!/usr/bin/perl -I/usr/sausalito/perl -I.
#
# Name: scandetection.pl
# Author: Jesse Throwe
# Description: This is the main handler for scandetection. 
#  It is run every time something is changed in the Scandetection namespace 
#  in cce.
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.
# $Id: scandetection.pl,v 1.6.2.1 2002/02/20 18:01:09 ge Exp $


# set up changeable variables
$ldfirewall = "/usr/sbin/ldfirewall";
$firewallfile = "/etc/scandetection/scandetection.fwall";
$configfile = "/etc/scandetection/scandetection.conf";
$lockouttime = 300;


# initialize CCE
use CCE;

my $cce = new CCE;
$cce->connectfd();


# find our system OID
my @system_oid = $cce->find("System");


# get the Scandetection object
my ($ok, $object, $old, $new) = $cce->get($system_oid[0], 'Scandetection');

# check to make sure CCE/Scandetection object is ok, leave if not
if (!$ok) {
	exit(0);
}

# acquire all of our values for our objects

my ($paranoiaLevel) = $cce->scalar_to_array($object->{paranoiaLevel});
my ($timeout) = $cce->scalar_to_array($object->{timeout});
my ($numScans) = $cce->scalar_to_array($object->{numScans});
my ($alertEmail) = $cce->scalar_to_array($object->{alertEmail});
my ($alertMe) = $cce->scalar_to_array($object->{alertMe});
my @permBlocked = $cce->scalar_to_array($object->{permBlocked});
my @permUnblocked = $cce->scalar_to_array($object->{permUnblocked});
	
my @networks = $cce->find("Network");


# find out if we are even doing anything
if ($paranoiaLevel == 0) { 
	
	# now we find our interfaces to put firewalls on, and install them
	$firewalls = `$ldfirewall -q`;

	my(@firewalled_lines) = split('\n',$firewalls);
	foreach $firewall_line (@firewalled_lines) {
        	($fwall_interface, $fwall_file) = split(' ', $firewall_line);
	 	system("$ldfirewall \-r $fwall_interface > /dev/null");
	 }
	$cce->bye('SUCCESS');
	exit(0); 
}

# make the firewall file
$makefwok = generate_firewall($paranoiaLevel, $timeout, $numScans, \@permBlocked, \@permUnblocked);
$makeconfigok = generate_config($paranoiaLevel, $alertMe, $alertEmail);

if (!$makefwok) {
$cce->bye('SUCCESS');
exit(0);
}

# now we find our interfaces to put firewalls on, and install them

foreach $networkoid (@networks) {
	 my ($ok, $networkobject) = $cce->get($networkoid);
	 my ($interface) = $cce->scalar_to_array($networkobject->{device});
	 system("$ldfirewall $interface $firewallfile > /tmp/noterror");
	 }


# now to cleanup

system("/etc/rc.d/init.d/scandetection restart > /dev/null");

$cce->bye('SUCCESS');
exit(0);

sub generate_config
{
  my ($paranoiaLevel, $alertMe, $alertEmail) = @_;
  my $alertFlag = $alertMe ? 1 : 0;
  open (MYCONFIGFILE, ">$configfile");
  print MYCONFIGFILE <<EOF;
actionlevel = $paranoiaLevel
alertme = $alertFlag
alertemail = $alertEmail
EOF
  close MYCONFIGFILE;
  return 1;
}

# there is a reason why the function is not tabbed, the stuff inside the 
# EOF's HAS TO BE WHERE IT IS otherwise the firewall file will not load 
# correctly
# The permBlocked and permUnblocked parameters are lists passed as REFERENCES
#

sub generate_firewall
{

# get our vars
my ($paranoiaLevel, $timeout, $numScans, $permBlocked, $permUnblocked) = @_;
my $fwdebug = ""; # set to "/log" for debugging, "" otherwise

# reduce numscans by one to make it accurate
$numScans--;

# open our firewall
open (MYFIREWALL, ">$firewallfile");

# print out the stuff at the top of the file
print MYFIREWALL <<EOF;
default

   pass

     label=shutdown
     {
       timeout=$lockouttime
       !recv/srcaddr/log
       !send/dstaddr/log
     }

EOF

# enable firewallign if its requested
if ($paranoiaLevel > 1) {

# now for the always block list
foreach $iptoblock (@$permBlocked) {
print MYFIREWALL "      !recv/srcaddr=$iptoblock/log=denied\n";
}

# now for the never block list
foreach $iptounblock (@$permUnblocked) {
print MYFIREWALL "      send/dstaddr=$iptounblock\n";
}


#print out the special deny trigger
print MYFIREWALL <<EOF;


     label=tcp-$numScans
     {
       timeout=$timeout
       send/tcp/rst/srcaddr/dstaddr/trigger=shutdown/log=portscan
     }

     label=udp-$numScans
     {
       timeout=$timeout
       icmp/3/3/send/srcaddr/dstaddr/trigger=shutdown/log=portscan
     }


EOF

} elsif ($paranoiaLevel == 1) {

print MYFIREWALL <<EOF;

     label=tcp-$numScans
     {
       timeout=$timeout
       send/tcp/rst/srcaddr/dstaddr/log=portscan
     }

     label=udp-$numScans
     {
       timeout=$timeout
       icmp/3/3/send/srcaddr/dstaddr/log=portscan
     }


EOF

}

# now print out the meat of the firewall file
for ($i = ($numScans -1); $i > 0; $i--) {

$ipone = $i + 1;

print MYFIREWALL <<EOF;

     label=tcp-$i
     {
       timeout=$timeout
       send/tcp/rst/srcaddr/dstaddr/srcport$fwdebug
       send/tcp/rst/srcaddr/dstaddr/trigger=tcp-$ipone$fwdebug
     }

     label=udp-$i
     {
       timeout=$timeout
       icmp/3/3/send/srcaddr/dstaddr/srcport$fwdebug
       icmp/3/3/send/srcaddr/dstaddr/trigger=udp-$ipone$fwdebug
     }


EOF
}



# print our logging and initial trigger
print MYFIREWALL <<EOF;

     send/tcp/rst/trigger=tcp-1$fwdebug
     icmp/3/3/send/trigger=udp-1$fwdebug
     all
  
  log

    rejected

EOF

# return now that were done
close MYFIREWALL;
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
