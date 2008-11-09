#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#$Id: modsystem.pl 3 2003-07-17 15:19:15Z will $

use strict;
use Sauce::Config;
use Sauce::Util;
use CCE;
# use System;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

# retreive user object data:
my $oid = $cce->event_oid();

if (!$oid) {
	# something is very, very wrong.
  $cce->bye('FAIL', 'Bad CSCP header');
  exit(1);
}

my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();
use Data::Dumper;

##########################################################################
# error checking
##########################################################################
# System::validate();

##########################################################################
# actuate changes
##########################################################################

my $name = $obj->{hostname};
if ($obj->{domainname} && $obj->{hostname} !~ m/\.$obj->{domainname}$/) {
  $name = join(".",($obj->{hostname},$obj->{domainname}));
}
$name =~ s/\.+/\./g;
if (length($name) > 40) {
  $cce->baddata(0, 'hostname', 'hostname-too-long');
  $cce->bye('FAIL');
  exit 1;
}
if ($name =~ m/^localhost\./) {
      $name = "localhost";
}

if (defined($new->{hostname}) || defined($new->{domainname}))
{

  # update /etc/HOSTNAME
  {
      my $fn = sub {
      my ($fin, $fout) = (shift, shift);
      my $name = shift;
      print $fout $name,"\n";
      return 1;
    };
    Sauce::Util::editfile('/etc/HOSTNAME', $fn, $name)
  }
  
  # run the hostname command 
  system('/bin/hostname', $name);
}

if (defined($new->{hostname}) 
  || defined($new->{domainname})
  || defined($new->{gateway}) )
{
  # update /etc/sysconfig/network
  {
    my $fn = sub {
      my ($fin, $fout) = (shift,shift);
      my ($name, $gateway) = (shift, shift);
      my %hash = ();
      while ($_ = <$fin>) {
      	chomp($_);
      	if (m/^\s*([A-Za-z0-9_]+)\s*\=\s*(.*)/) { $hash{$1} = $2; }
      }
      $hash{HOSTNAME} = $name;
      $hash{NETWORKING} = "yes";
      $hash{FORWARD_IPV4} = $hash{FORWARD_IPV4} || "false";
      $hash{GATEWAY} = $gateway || $hash{GATEWAY} || "";
      if (defined($gateway)) { $hash{GATEWAY} = $gateway; };
      foreach $_ ( sort keys %hash ) {
      	print $fout $_,"=",$hash{$_},"\n";
      }
      return 1;
    };
    Sauce::Util::editfile("/etc/sysconfig/network", $fn, $name, $obj->{gateway});
    chmod(0644,'/etc/sysconfig/network');
  };
}

# comfortable shoes.
$cce->bye('SUCCESS');
exit(0);
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
