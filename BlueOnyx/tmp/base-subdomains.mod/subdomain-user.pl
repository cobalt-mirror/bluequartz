#!/usr/bin/perl -I/usr/sausalito/perl
# Initial Author: Brian N. Smith
# $Id: subdomain-user.pl

use CCE;
use Sauce::Util;
use Sauce::Service;
use Base::HomeDir qw(homedir_get_user_dir);

umask(002);

$cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
$obj = $cce->event_object();

($ok, $user) = $cce->get($oid);
($ok, $subdomains) = $cce->get($oid, 'subdomains');

$alt_root = $user->{volume};
$name = $user->{name};
$site = $user->{site};

$home_dir = homedir_get_user_dir($name, $site, $alt_root);

if ( $subdomains->{'enabled'} eq "1" ) {

  ($ok, $error) = $cce->create('Subdomains', {
    'webpath' => $home_dir . "/web/",
    'hostname' => $user->{'name'},
    'group' => $user->{'site'},
    'isUser' => '1'
  });

  if (not $ok) {
    $cce->warn('[[base-subdomains.duplicateEntry]]');
    $cce->bye('FAIL');
    exit(1);
  }
} else {
  @oids = $cce->find('Subdomains', { 'group' => $user->{'site'},  'hostname' => $user->{'name'}, 'isUser' => '1'});
  if ( $oids[0] ) {
    $cce->destroy($oids[0]); 
  }
}

service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2008 NuOnce Networks, Inc.
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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