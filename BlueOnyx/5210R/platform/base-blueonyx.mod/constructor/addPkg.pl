#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: addPkg.pl
#

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectuds();
my @oids = $cce->find('Package', {'name' => 'OS' });

# read build date
my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($build, $model, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

if ($#oids < 0) {
    my (%rpms, $rpmlist);

    # snarf in package list. avoid duplicates
    if (open(CONF, $conf)) {
	while (<CONF>) {
		next if /^\s*#/;
		next unless /\S/;
		next if /^\S+:/;
		$rpms{"$1-$2"} = 1 if /^(\S+)\s+(\S+)/;
	}
	close(CONF);
    }

    $rpmlist = $cce->array_to_scalar(keys %rpms);
    $cce->create('Package', { 'name' => 'OS',
			      'version' => "v4.$build",
			      'vendor' => 'BlueOnyx',
			      'nameTag' => '[[base-alpine.osName]]',
			      'vendorTag' => '[[base-alpine.osVendor]]',
			      'shortDesc' => '[[base-alpine.osDescription]]',
			      'new' => 0, 'installState' => 'Installed',
			      'RPMList' => $rpmlist
			  });
    my ($sysoid) = $cce->find('System');
    $cce->set($sysoid, 'SWUpdate', { 'rpmsInstalled' => 
				     $cce->array_to_scalar(%rpms) });
}

if ($#oids == 0) {
        # Object already present in CCE. Updating it with current version information:
        ($sys_oid) = $cce->find('Package', {'name' => 'OS' });
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->set($sys_oid, '',{
				'version' => "v4.$build"
                          });
}

$cce->bye();
exit 0;

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