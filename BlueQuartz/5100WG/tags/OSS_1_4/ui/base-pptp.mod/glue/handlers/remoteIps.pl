#!/usr/bin/perl -I/usr/sausalito/perl

use strict;
use CCE;

my $pptpConfFile = "/etc/pptpd.conf";

my $cce = new CCE();
$cce->connectfd();

my @oids = $cce->find("PptpDynamic");

my @remoteIps;
foreach my $oid (@oids) {
  my ($ok, $obj) = $cce->get($oid);
  if ($obj->{ipaddrlo} eq $obj->{ipaddrhi}) {
    # just a single IP
    push @remoteIps, $obj->{ipaddrlo};
  } else {
    push @remoteIps, $obj->{ipaddrlo} . "-" . $obj->{ipaddrhi};
  }
}

sub writeIpFunc {
  my ($in, $out, $ip) = @_;
  print $out "remoteip $ip\n";
}
Sauce::Util::editblock($pptpConfFile,
		       \&writeIpFunc,
		       "# :: AUTOGENERATE: REMOTEIP START::",
		       "# :: AUTOGENERATE: REMOTEIP STOP::",
		       join (",", @remoteIps)
		       );

if (scalar @oids == 0) {
  # we gotta disble the service because it's configuration is no
  # longer valid
  my ($sysoid) = $cce->find("System");
  $cce->set($sysoid, "Pptp", {enabled => "0"});
  $cce->warn("[[base-pptp.pptp_disabling_service_no_ranges]]");
}

$cce->bye("SUCCESS");
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
