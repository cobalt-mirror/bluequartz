#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id$
# Copyright 2010 Project BlueQuartz.  All rights reserved.
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT

use strict;
use CCE;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectuds();

my $configure = 1;

# If we're using DHCP (like in AWS) or if we're under OpenVZ, then we cannot
# change the IP address ranges via the GUI:
if ((-f "/etc/is_aws") || (-f "/proc/user_beancounters")) {
        $configure = 0;
}

my @oids = $cce->find('System');
my $sys_oid = $oids[0];
my ($ok) = $cce->set($sys_oid, 'Network', { 'interfaceConfigure' => $configure });

@oids = $cce->find('IPPoolingRange');
if ($#oids < 0 && $configure == 0) {
  my @net_oids = $cce->find('Network', {'real' => 1});
  foreach my $oid (@net_oids) {
    my ($ok, $obj) = $cce->get($oid);
    my $ipaddr = $obj->{'ipaddr'};
    $ok = $cce->create('IPPoolingRange', {'min' => $ipaddr, 'max' => $ipaddr});
  }
  my ($ok) = $cce->set($sys_oid, 'Network', {'pooling' => 1});
}

$cce->bye('SUCCESS');
exit(0);

#
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
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
