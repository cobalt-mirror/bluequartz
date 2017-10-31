#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: fix_user_suspension.pl
#
# User accounts MUST be locked or unlocked. Otherwise suspended users can still use SMTP-Auth.
# This script walks through all Vsites and checks if the Vsite is suspended (or unsuspended).
# If a Vsite is suspended, this script will user 'usermod -L <username>' to lock the account.
# If a Vsite is NOT suspended, this script will user 'usermod -U <username>' to unlock the account.
#
# You can use this script to repair the lock status of accounts. Just run it from the command line
# without any parameters. 
#

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

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    if ($my_vsite->{suspend} == "0") {
        $site_status = "Not suspended";
    }
    else {
        $site_status = "Suspended";
    }

    print "\nChecking Site $my_vsite->{fqdn} - Site status: $site_status \n";

    # Start with an empty and predefined array:
    my @users = ();
    
    # Get all users of this Vsite:
    @users = $cce->findx('User', { 'site' => $my_vsite->{name} });

    # Walk through all users of that Vsite:
    for my $user (@users) {

        # Get the username:
        ($ok, my $myuser) = $cce->get($user);

        if (($my_vsite->{suspend} == "0") && ($myuser->{enabled} == "1")) {
        # Unlock account:
            system("/usr/sbin/usermod -U $myuser->{name}");
        print "User $myuser->{name} is not suspended. Running '/usr/sbin/usermod -U $myuser->{name}' to unlock the account.\n";
        }
    else {
            # Lock account:
            system("/usr/sbin/usermod -L $myuser->{name}");
        print "User $myuser->{name} should be suspended. Running '/usr/sbin/usermod -L $myuser->{name}' to lock the account.\n";
    }
    }
}

$cce->bye('SUCCESS');
exit(0);

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