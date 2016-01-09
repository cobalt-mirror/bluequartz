#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: fix_php_conf. 
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
while(<HTTPD>) {
  s/^AddHandler\s/#AddHandler /g;
  s/^AddType\s/#AddType /g;
  s/^DirectoryIndex\s/#DirectoryIndex /g;
  
  print STAGE;
}
close(STAGE);
close(HTTPD);

chmod(0644, $stage);
if(-s $stage) {
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

# List of PEAR modules that we *need* to install:
%required_modules = (
                      'Net_IDNA2', 
                      'Net_SMTP', 
                      'Net_Sieve', 
                      'Net_Socket', 
                      'Mail_Mime', 
                      'Auth_SASL' 
                    );

# Get the PHP object to find out what 'open_basedir' is set to:
@PHP_oid = $cce->find('PHP');
($ok, $php_settings) = $cce->get($PHP_oid[0]);
@OBpaths = split(':', $php_settings->{'open_basedir'});

# Only run this if we're online and pear.php.net can be pinged:
$check_net = `ping -c 1 -w 1 pear.php.net|grep "1 received"|wc -l`;
chomp($check_net);
# Only run this if we can ping pear.php.net:
if ($check_net eq "1") {
  # Do this for all known PHP versions:
  for $phpVer (keys %known_php_versions) {
    $pear_path = $known_php_versions{$phpVer} . "/bin/pear";
    $top_module_path = $known_php_versions{$phpVer} . "/share/pear";
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
      if (-f $pear_path) {
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

# Handle 'open_basedir':
$open_basedir_addition = '';
for $phpVer (keys %known_php_versions) {
  $pear_path = $known_php_versions{$phpVer} . "/bin/pear";
  $top_module_path = $known_php_versions{$phpVer} . "/share/pear";

  # Only do something if the module path is present:
  if (-d $top_module_path) {
    if (!in_array(\@OBpaths, $top_module_path)) {
      if ((-d '/home/solarspeed/php') && ($phpVer ne 'PHPOS')) {
        # If 'traditional' Solarspeed PHP is installed, add its path:
        $open_basedir_addition = $top_module_path;
      }
      elsif ((!-d '/home/solarspeed/php') && ($phpVer eq 'PHPOS')) {
        # If Solarspeed PHP is NOT installed and this is the OS PHP,
        # then add the path of it:
        $open_basedir_addition = $top_module_path;
      }
    }
  }
}

# If we need to add the PEAR path to 'open_basedir', then we do that as well:
if ($open_basedir_addition ne "") {
  $new_ob_line = $php_settings->{'open_basedir'} . ':' . $open_basedir_addition;
  ($ok) = $cce->set($php_settings->{'OID'}, '', { 'open_basedir' => $new_ob_line});
}

if ($apache_ok == "1") {
  $cce->bye("SUCCESS");
}
else {
  $cce->bye("FAILURE");
}

exit(0);

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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