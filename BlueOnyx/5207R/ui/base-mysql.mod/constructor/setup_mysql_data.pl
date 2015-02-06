#!/usr/bin/perl
#
# setup_mysql_data.pl

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
use CCE;

$cce = new CCE;
$cce->connectuds();

# Create main MySQL access settings if not already present:
@mysql_main = $cce->find('MySQL');
if (!defined($mysql_main[0])) {
    ($ok) = $cce->create('MySQL', {
        'sql_host' => "localhost",
        'sql_port' => "3306",
        'sql_root' => "root",
        'sql_rootpassword' => "",
        'timestamp' => time(),
        'savechanges' => time()
    });
}

# Firstboot:
@oids = $cce->find('System');
if (not @oids) {
        $cce->bye('FAIL');
        exit 1;
}

$firstboot = "0";
($ok, $obj) = $cce->get($oids[0]);
if ($obj->{isLicenseAccepted} == "0") {
    $firstboot = "1";
}


if ($firstboot eq "1") {
    ($ok) = $cce->set($oids[0], 'mysql',{
            "enabled" => "1",
            "onoff" => time()

    });
}

$cce->bye('SUCCESS');
exit 0;

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#        notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#        notice, this list of conditions and the following disclaimer in 
#        the documentation and/or other materials provided with the 
#        distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#        contributors may be used to endorse or promote products derived 
#        from this software without specific prior written permission.
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