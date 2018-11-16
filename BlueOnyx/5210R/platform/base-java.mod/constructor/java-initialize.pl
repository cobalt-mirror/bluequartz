#!/usr/bin/perl
# $Id: java-initialize.pl
#
# On first (re)start of CCE after base-java install copy the distributed config
# files into the right places and restart tomcat if need be.

use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;

my $cce = new CCE;

$cce->connectuds();

$cmd_tomcat = '/sbin/service tomcat';
$sts_tomcat = "UNKNOWN";
$sts_tempfile = '/tmp/.tomcat';
$tomcat_properties = '/etc/tomcat/tomcat-users.xml';

# Only do anything if we haven't already performed this step:
if (! -f "/etc/tomcat/.setup") {
    system("/bin/cp /etc/tomcat/tomcat-users.xml.dist /etc/tomcat/tomcat-users.xml");
    system("/bin/cp /etc/tomcat/tomcat.logrotate.dist /etc/logrotate.d/tomcat");
    system("/bin/chmod 0660 /etc/tomcat/tomcat-users.xml");
    system("/bin/chown tomcat:tomcat /etc/tomcat/tomcat-users.xml");
    system("/bin/touch /etc/tomcat/.setup");
    system("/bin/echo '# Do not remove this file. Thanks!' >> /etc/tomcat/.setup");

    # Set the password for Tomcat user 'admin' to some random string of 11 character length:
    # We do this to prevent the introduction of a default password weakness:
    $random_string=&generate_random_string(11);
    $ret = Sauce::Util::editfile($tomcat_properties, *edit_policy, $random_string);

    # Check tomcat status - this will work regardless which language the console is set to:
    $rtn_tomcat = system("$cmd_tomcat status > $sts_tempfile");
    open (F, $sts_tempfile) || die "Could not open $sts_tempfile: $!";
    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               # skip blank lines
        if ($line =~ /([0-9])/) {
                $sts_tomcat = "RUNNING";
        }
        else {
                $sts_tomcat = "STOPPED";
	}        
    }
    close(F);
    system("/bin/rm -f $sts_tempfile");
    
    # tomcat is already running. We need to restart it:
    if ($sts_tomcat eq "RUNNING") {
	system("$cmd_tomcat restart > /dev/null 2>&1");
    }

}

# This function generates random strings of a given length
sub generate_random_string {
    my $length_of_randomstring=shift;
    my @chars=('a'..'z','A'..'Z','0'..'9','_');
    my $random_string;
    foreach (1..$length_of_randomstring) {
	# rand @chars will generate a random 
	# number between 0 and scalar @chars
	$random_string.=$chars[rand @chars];
    }
    return $random_string;
}

sub edit_policy {
    my ($in, $out, $max) = @_;
    my $maxConnect = "  <user username=\"admin\" password=\"$max\" roles=\"admin,manager-gui,admin-gui\"/>\n";

    while(<$in>) {
        if(/  <user username=\"admin\" password(.+)$/) {
    	    print $out $maxConnect;
        } else {
            print $out $_;
        }
    }
    return 1;
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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