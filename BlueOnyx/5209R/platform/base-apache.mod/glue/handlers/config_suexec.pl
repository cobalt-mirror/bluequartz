#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: config_suexec.pl
#

sub suexec_conf_filename  { '/etc/suexec.conf'; };
sub suexec_conf_perms 	  { 0600; };
sub suexec_conf_uid   	  { 0; };
sub suexec_conf_gid   	  { 0; };

use CCE;
use Sauce::Util;

my $cce = new CCE; $cce->connectfd();

my $oid = $cce->event_oid();
my ($ok, $obj) = $cce->get($oid, "Web");

my $cgiAccess = $obj->{cgiAccess};
my $cgiUserList = $obj->{cgiUsers};

my @cgiUsers = ();
if ($cgiAccess eq 'all') {
	@cgiUsers = ( '[ALL]' );
}
elsif ($cgiAccess eq 'subset') {
	@cgiUsers = $cce->scalar_to_array($cgiUserList);
}

if ($#cgiUsers < 0) {
	@cgiUsers = ( '[NONE]' );
}

Sauce::Util::replaceblock( suexec_conf_filename,
						"# Do not edit below this line",
						"/home: FILE: " . join(",",@cgiUsers) . "\n",
						"# Do not edit above this line" 
						 );
Sauce::Util::chownfile ( suexec_conf_filename, suexec_conf_uid, suexec_conf_gid );
Sauce::Util::chmodfile ( suexec_conf_filename, suexec_conf_perms );

$cce->bye("SUCCESS");
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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