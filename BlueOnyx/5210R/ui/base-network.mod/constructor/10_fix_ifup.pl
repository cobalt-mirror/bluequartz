#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 10_fix_ifup.pl

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Sauce::Util;

my $DEBUG = 0;
my $errors = 0;
my $conf = '/etc/sysconfig/network';

my $cce = new CCE;
$cce->connectuds();

# Modify /etc/sysconfig/network-scripts/ifup-routes:
&fix_if_up;

# Done:
$cce->bye('SUCCESS');
exit(0);

sub fix_if_up {

    # Append a call to run /usr/sausalito/handlers/base/network/change_route.pl to /etc/sysconfig/network-scripts/ifup.
    # That handler adds all extra IP's and fixes up the routing.

    # Fix /etc/sysconfig/network-scripts/ifup:
    if (-f "/etc/sysconfig/network-scripts/ifup") {
        # Check if ifup has our provisions to conditionally fire up the change_route.pl handler:
        $check_ifup = `cat /etc/sysconfig/network-scripts/ifup|grep /usr/sausalito/handlers/base/network/change_route.pl|wc -l`;
        chomp($check_ifup);
        # Provisions not present. Add them:
        if ($check_ifup eq "0") {
            system('echo \'if [ -x /usr/sausalito/handlers/base/network/change_route.pl ]; then' . "\n" . '    /usr/bin/flock -n /usr/sausalito/license/change_route.lock /usr/sausalito/handlers/base/network/change_route.pl -c 2>/dev/null;' . "\n". 'fi\' >> /etc/sysconfig/network-scripts/ifup');
        }
    }

    # Create /sbin/ifup-local if it's missing:
    if (!-f "/sbin/ifup-local") {
        my $filename = "/sbin/ifup-local";
        system("touch $filename");
        my $ok = Sauce::Util::editfile($filename, *edit_ifcfg, $filename);
        system("chmod 755 $filename");
        system("chown root:root $filename");
    }
    else {
        # If it's there, make sure it has what we need:
        $check_ifuplocal = `cat /sbin/ifup-local|grep /usr/sausalito/handlers/base/network/change_route.pl|wc -l`;
        chomp($check_ifuplocal);
        if ($check_ifuplocal eq "0") {
            # Not containing correct info. Fixing that:
            my $filename = "/sbin/ifup-local";
            my $ok = Sauce::Util::editfile($filename, *edit_ifcfg, $filename);
            system("chmod 755 $filename");
            system("chown root:root $filename");
        }
    }
}

sub edit_ifcfg {
    my ($fin, $fout, $filename) = @_;
    print $fout '#!/bin/sh' . "\n";
    print $fout 'if [ "$1" == "eth0" ] || [ "$1" == "venet0" ]  ;then' . "\n";
    print $fout '    /usr/sausalito/handlers/base/network/change_route.pl -c 2>/dev/null' . "\n";
    print $fout 'fi' . "\n";
    return 1;
}

# 
# Copyright (c) 2015-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2018 Team BlueOnyx, BLUEONYX.IT
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
