#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: imapsync_logdelete.pl
#
# This handler is run when a User requests the deletion
# of the ImapSync logfile.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
    &debug_msg("Debug enabled.\n");
}

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;
use Unix::PasswdFile;
use Unix::GroupFile;

my $cce = new CCE;

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();
$obj = $cce->event_object();

$old = $cce->event_old();
$new = $cce->event_new();

# Get the parent Objects from CODB:
($ok, $MasterObject) = $cce->get($oid);
($ok, $ImapSync_Object) = $cce->get($oid, "ImapSync");

# We're modifying the ImapSync object:
if (($cce->event_is_modify())) {

    # Parse /etc/passwd:
    $pw = new Unix::PasswdFile "/etc/passwd";

    if ($MasterObject->{'CLASS'} eq "User") {

        &debug_msg("Working with a User Object.\n");
        &debug_msg("User: $MasterObject->{'name'}\n");

        $user_home_dir = $pw->home($MasterObject->{'name'});
        &debug_msg("User's home directory: $user_home_dir\n");

        if ($ImapSync_Object ne '0') {
            &debug_msg("/bin/rm -f $user_home_dir/.imapsync.log\n");
            system("/bin/rm -f $user_home_dir/.imapsync.log");
        }

        $cce->bye('SUCCESS');
        exit(0);
    }
    else {
        # We should never get here, but if we do, we throw an error:
        &debug_msg("That's not a 'User' object!\n");
        $cce->bye('FAIL');
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
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