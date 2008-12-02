#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#$Id: System.pm,v 1.1 2001/04/24 23:24:01 pbaltz Exp $
#
# author: Jonathan Mayer <jmayer@cobalt.com>
#
# System class

use CCE;

package System;

sub validate
{
	my $cce = shift;
  my $errors = 0;
  my $re_dotquad = qr/^
  	(([0-9])|([1-9][0-9])|(1[0-9][0-9])|(2[0-4][0-9])|(25[0-5])) \. 
  	(([0-9])|([1-9][0-9])|(1[0-9][0-9])|(2[0-4][0-9])|(25[0-5])) \. 
  	(([0-9])|([1-9][0-9])|(1[0-9][0-9])|(2[0-4][0-9])|(25[0-5])) \. 
  	(([0-9])|([1-9][0-9])|(1[0-9][0-9])|(2[0-4][0-9])|(25[0-5]))
  $/x;
  $errors += $cce->validate('hostname', qr/^[\w\-\.]+$/ );
  $errors += $cce->validate('domainname', qr/^[\w\-\.]+$/ );
  $errors += $cce->validate('eth0_ipaddr', $re_dotquad );
  $errors += $cce->validate('eth0_netmask', $re_dotquad );
  $errors += $cce->validate('eth0_up', qr/.*/ );
  $errors += $cce->validate('eth1_ipaddr', $re_dotquad );
  $errors += $cce->validate('eth1_netmask', $re_dotquad );
  $errors += $cce->validate('eth1_up', qr/.*/ );
  $errors += $cce->validate('gateway', qr/.*/ );
  $errors += $cce->validate('dns_primary', $re_dotquad );
  $errors += $cce->validate('dns_secondary', $re_dotquad );
  $errors += $cce->validate('time_region', qr// );
  $errors += $cce->validate('time_country', qr// );
  $errors += $cce->validate('time_zone', qr// );
  $errors += $cce->validate('sitedef_ipaddr', $re_dotquad );
  $errors += $cce->validate('sitedef_domainname', qr// );
  $errors += $cce->validate('sitedef_quota', qr/^(\d+)$/ );
  $errors += $cce->validate('sitedef_maxusers', qr/^(\d+)$/ );
  $errors += $cce->validate('reboot', qr// );

  # check for excessively large quota
  if ($quota && ($quota > 10240000)) {
	  $cce->baddata(0, 'quota', 'excessivelyLargeQuota', { max => 10240000 } );
    $errors++;
  }

  if ($errors) {
  	$cce->bye('FAIL');
    exit 1;
  }
  return 1;
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
