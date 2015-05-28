#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: fix_php_conf.pl
#
# This constructor takes a look at /etc/httpd/conf.d/php.conf and trims it down to
# BlueOnyx's requirements. After all we only want PHP enabled on sites where we 
# manually turn it on ... and not everywhere.
#
# Depends on:
#   System.hostname
#   System.domainname

my $confdir = '/etc/httpd/conf.d';

use Sauce::Config;
use FileHandle;
use File::Copy;
use CCE;

my $cce = new CCE;
$cce->connectuds();

umask(0077);
my $stage = "$confdir/php.conf~";
open(HTTPD, "$confdir/php.conf");
unlink($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
while (<HTTPD>) {
  s/^AddHandler\s/#AddHandler /g;
  s/^AddType\s/#AddType /g;
  s/^DirectoryIndex\s/#DirectoryIndex /g;
  s/^<FilesMatch\s/#<FilesMatch /g;
  s/^</#</g;
  s/^    SetHandler\s/#    SetHandler /g;
  print STAGE;
}
close(STAGE);
close(HTTPD);

chmod(0644, $stage);
if (-s $stage) {
  move($stage,"$confdir/php.conf");
  chmod(0644, "$confdir/php.conf"); # paranoia
  $apache_ok = "1";
} else {
  $apache_ok = "0";
}

#
## Install the required PEAR module for all versions of PHP that are currently present:
#

# Known PHP versions:
%known_php_versions = (
                        'PHPOS' => '/usr',
                        'PHP'   => '/home/solarspeed/php',
                        'PHP53' => '/home/solarspeed/php-5.3',
                        'PHP54' => '/home/solarspeed/php-5.4',
                        'PHP55' => '/home/solarspeed/php-5.5',
                        'PHP56' => '/home/solarspeed/php-5.6'
                        );

# Only run this if we're online and pear.php.net can be pinged:
$check_net = `ping -c 1 -w 1 pear.php.net|grep "1 received"|wc -l`;
chomp($check_net);
if ($check_net eq "1") {
  for $phpVer (keys %known_php_versions) {
    $module_path = $known_php_versions{$phpVer} . "/share/pear/Net/IDNA2";
    $top_module_path = $known_php_versions{$phpVer} . "/share/pear/Net";
    $pear_path = $known_php_versions{$phpVer} . "/bin/pear";
    if ((!-d $module_path) && (-f $pear_path)) {
      system("$pear_path install channel://pear.php.net/Net_IDNA2-0.1.1 > /dev/null");
      # Fix permissions recursively, because the PEAR-installer doesn't. WTF! YGBSM!!
      system("chmod -R 755 $top_module_path");
    }
  }
}

if ($apache_ok == "1") {
  $cce->bye("SUCCESS");
}
else {
  $cce->bye("FAILURE");
}

exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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