#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#$Id: System.pm
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
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