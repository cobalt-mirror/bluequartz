#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id$

use strict;
use CCE;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectuds();

# Are on OpenVZ?
if (-f "/proc/user_beancounters") { 
    # Apparently yes. Stopping right here, as we have no MACs.
    exit(0);
}

my @oids = $cce->find('Network');
foreach my $oid (@oids) {
  my ($ok, $obj) = $cce->get($oid);
  my $device = $obj->{'device'};

  my $data = `$Network::IFCONFIG $device`;

  my $hex = '[0-9a-fA-F]{2,}';
  if ($data =~ m/HWaddr ($hex:$hex:$hex:$hex:$hex:$hex)/) {
    my $mac = $1;
    $DEBUG && print STDERR "mac address for device $device: $mac\n";
    my ($ok) = $cce->set($oid, '', { 'mac' => $mac });
  }
}

$cce->bye('SUCCESS');
exit(0);

#
# Copyright 2007 Project BlueQuartz.  All rights reserved.
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 