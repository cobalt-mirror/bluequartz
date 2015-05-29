#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: web_alias_redirects.pl
#
# Two different behaviours are possible for 'Web Server Aliases':
#
# 1.) A web server alias will redirect to the sites primary domain name.
# 2.) Each web server alias will have it's own URL. Each of them shows the content of the website.
#
# This behaviour can be configured for each site through the switch "Web Alias Redirects".
#
# If the checkbox is ticked, behaviour #1 is used (redirects to main site).
# If the checkbox is not ticked, behaviour #2 is used and no redirects happen.
#
# With this script you can set ALL sites on this server to one of the two behaviours in one go.
#
#   USAGE:
#   ======
#
#   /usr/sausalito/sbin/web_alias_redirects.pl --enabled
#   /usr/sausalito/sbin/web_alias_redirects.pl --disabled


use CCE;
my $cce = new CCE;
$cce->connectuds();

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Handle switches:
$type_switch = $ARGV[0];
if ($type_switch eq "--enabled") {
        $switch = "1";
        $switch_desc = "Enabling";
}
elsif ($type_switch eq "--disabled") {
        $switch = "0";
        $switch_desc = "Disabling";
}
else {
    print "\n";
    print "Usage:\n";
    print "======\n";
    print "\n";
    print "/usr/sausalito/sbin/web_alias_redirects.pl --enabled\n";
    print "/usr/sausalito/sbin/web_alias_redirects.pl --disabled\n";
    print "\n";
    exit(0);
}



# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    print "\n$switch_desc redirects for Site $my_vsite->{fqdn}\n";

    # Set 'Vsite':
    ($ok) = $cce->set($vsite, '', {'webAliasRedirects' => $switch});

}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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