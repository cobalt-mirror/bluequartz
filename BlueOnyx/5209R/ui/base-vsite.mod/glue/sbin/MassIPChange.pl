#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: MassIPChange.pl

use CCE;
my $cce = new CCE;

$switch_o = $ARGV[0];
$old_ip = $ARGV[1];
$switch_n = $ARGV[2];
$new_ip = $ARGV[3];

print "\n";
print "MassIPChange.pl V2.0\n";
print "======================\n\n";
print "Author: Michael Stauber\n\n";
print "This script can change all VSitess with a certain old IP to a specified new IP.\n\n";

if (!$switch_o || !$switch_n || !$old_ip || !$new_ip) {
        print "To use it, run it with the followinng parameters:\n\n";
        print $0 . " -o old-ip -n new-ip\n\n";
        exit 1;
} elsif ($switch_o && $old_ip && $switch_n && $new_ip) {
        print "Changing all Vsites with IP $old_ip to the new IP $new_ip ... \n\n";
    $cce->connectuds();
    &feedthemonster;
    $cce->bye('SUCCESS');
    exit(0);
} else {
        print "Aborting without doing anything ...\n\n";
        exit 1;
}

sub feedthemonster {
    (@oids) = $cce->find('Vsite', { 'ipaddr' => $old_ip });
    if ($#oids == -1) {
        print "No Vsite(s) found which have the IP address " . $old_ip . "\n\n";
        exit(1);
    }
    else {
        for $object (@oids) {
            ($ok, $rec) = $cce->get($object);
            print "Changing IP for " . $rec->{'fqdn'} . " to the IP " . $new_ip . "\n";
            ($ok) = $cce->set($object, '',{
                'ipaddr' => $new_ip
                });
            }
    }
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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