#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: config_pass.pl

my $tomcat_properties = '/etc/tomcat/tomcat-users.xml';

my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;

use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
my ($ok, $java) = $cce->get($oids[0], "Java");

if($ok) {
	my $ret = Sauce::Util::editfile($tomcat_properties, *edit_policy,
		$java->{JavaAdminPass});

	unless($ret) {
		$DEBUG && warn "$0 failing, editfile $tomcat_properties, ".
			$java->{JavaAdminPass}." failed.\n";
		# $cce->bye('FAIL');
		# exit(1);
	}
}
else {
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Fin

sub edit_policy {
        my ($in, $out, $max) = @_;
	my $maxConnect = "  <user username=\"admin\" password=\"$max\" roles=\"admin,manager\"/>\n";

	while(<$in>) {
		if(/  <user username=\"admin\" password(.+)$/) {
			$DEBUG && warn "* Found: $_, using $maxConnect *\n";
			print $out $maxConnect;
		} else {
			print $out $_;
		}
	}
	return 1;
}

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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