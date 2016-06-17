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
                        'PHP56' => '/home/solarspeed/php-5.6',
                        'PHP70' => '/home/solarspeed/php-7.0',
                        'PHP71' => '/home/solarspeed/php-7.1',
                        'PHP72' => '/home/solarspeed/php-7.2',
                        'PHP73' => '/home/solarspeed/php-7.3',
                        'PHP74' => '/home/solarspeed/php-7.4',
                        'PHP75' => '/home/solarspeed/php-7.5',
                        'PHP76' => '/home/solarspeed/php-7.6',
                        'PHP77' => '/home/solarspeed/php-7.7',
                        'PHP78' => '/home/solarspeed/php-7.8',
                        'PHP79' => '/home/solarspeed/php-7.9'
                        );

# List of PEAR modules that we *need* to install:
%required_modules = (
                      'Net_IDNA2', 
                      'Net_SMTP', 
                      'Net_Sieve', 
                      'Net_Socket', 
                      'Mail_Mime', 
                      'Auth_SASL' 
                    );

# Only run this if we're online and pear.php.net can be pinged:
$check_net = `ping -n -q -c 1 -w 1 pear.php.net|grep "1 received"|wc -l`;
chomp($check_net);
# Only run this if we can ping pear.php.net:
if ($check_net eq "1") {
  # Do this for all known PHP versions:
  for $phpVer (keys %known_php_versions) {
    $pear_path = $known_php_versions{$phpVer} . "/bin/pear";
    $module_registry_path = $known_php_versions{$phpVer} . "/share/pear/.registry/";
    $top_module_path = $known_php_versions{$phpVer} . "/share/pear";

    if (! -f $pear_path) {
      # $pear_path doesn't exist, skipping.
      next;
    }

    # Special provisions for the PHP of the OS:
    if ($phpVer eq "PHPOS") {
      system("cp /etc/php.ini /etc/php.ini.bak");
      system("cat /etc/php.ini|grep -v ^open_basedir > /etc/php.ini");
    }
    else {
      if (-f $pear_path) {
        system("cp $known_php_versions{$phpVer}/etc/php.ini $known_php_versions{$phpVer}/etc/php.ini.bak");
        system("cat $known_php_versions{$phpVer}/etc/php.ini|grep -v ^open_basedir > $known_php_versions{$phpVer}/etc/php.ini");
      }
    }
    # Do this for all %required_modules:
    for $module (keys %required_modules) {
      my $module_regi_check = $module_registry_path . lc $module . ".reg";
      if (-f $module_regi_check) {
        next;
      }
      if ((-f $pear_path) && (! -f $module_regi_check)) {
        $module_check = `$pear_path list|grep $module|wc -l`;
        chomp($module_check);
        if (($module_check == "0") && (-f $pear_path)) {
          # Do the PEAR installs:
          if ($module eq "Net_IDNA2") {
            # Handle special case for Net_IDNA2 which is beta and needs this work around:
            system("$pear_path install channel://pear.php.net/Net_IDNA2-0.1.1 --alldeps > /dev/null");
          }
          else {
            # All others can be installed normally:
            system("$pear_path install $module --alldeps > /dev/null");
          }
          # Fix permissions recursively, because the PEAR-installer doesn't. WTF! YGBSM!!
          system("chmod -R 755 $top_module_path");
        }
      }
    }
    if ($phpVer eq "PHPOS") {
      # Move the unmodified php.ini of the OS back in place:
      system("mv /etc/php.ini.bak /etc/php.ini");
    }
    else {
      if (-f $pear_path) {
        # Move the unmodified php.ini of the OS back in place:
        if (-f "$known_php_versions{$phpVer}/etc/php.ini") {
          system("mv $known_php_versions{$phpVer}/etc/php.ini.bak $known_php_versions{$phpVer}/etc/php.ini");
        }
      }
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
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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
