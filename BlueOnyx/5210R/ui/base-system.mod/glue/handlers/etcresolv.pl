#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: etcresolv.pl
# updates /etc/resolve
#
# depends on:
#		System.dns  (formated as a :-delimited list of DNS servers)
#		System.domainname

use strict;
use Sauce::Config;
use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# get system and network object ids:
my ($system_oid) = $cce->find("System");

# get system object:
my ($ok, $obj) = $cce->get($system_oid);
if (!$ok) { 
	# FIXME: fail
}

# get list of dns servers
my @dns = CCE->scalar_to_array($obj->{dns});
my $dom = $obj->{domainname};

# target file
my $fileName = '/etc/resolv.conf';

my $etchosts = <<EOT ;
# /etc/resolv.conf
# Auto-generated file.  Keep your customizations at the bottom of this file.
EOT
my $dns;
foreach $dns (@dns) {
	$etchosts .= "nameserver $dns\n";
};
$etchosts .= <<EOT ;
search $dom
domain $dom
#END of auto-generated code.  Customize beneath this line.
EOT

# update file
{
  my $fn = sub {
    my ($fin, $fout) = (shift,shift);
    my ($text) = (shift);
    print $fout $text;
    my $flag = 0;
    while (defined($_ = <$fin>)) {
    	if ($flag) { print $fout $_; }
    	else { if (m/^#END/) { $flag = 1; } }
    }
    return 1;
  };
  Sauce::Util::editfile($fileName, $fn, $etchosts );
};

# always make sure permission is right
Sauce::Util::chmodfile(0644, $fileName);

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#  notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#  notice, this list of conditions and the following disclaimer in 
#  the documentation and/or other materials provided with the 
#  distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#  contributors may be used to endorse or promote products derived 
#  from this software without specific prior written permission.
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