#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: ssh_keycert.pl
#
# This handler is run whenever a user with Shell access
# triggers the action to create SSH keys and/or certificates.

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
($ok, $SSH_Object) = $cce->get($oid, "SSH");

# We're creating or modifying the SSH object:
if (($cce->event_is_modify())) {

    # Parse /etc/passwd:
    $pw = new Unix::PasswdFile "/etc/passwd";

    if ($MasterObject->{'CLASS'} eq "System") {

        # This handles the SSH 'root' access key/cert:
        &debug_msg("Working with the System Object.\n");

        # Set work directory for user 'root':
        $user_ssh_dir = '/root/.ssh';

        # Create the directory if it doesn't exist:
        if (!-d $user_ssh_dir) {
            system("/bin/mkdir $user_ssh_dir");
            system("/bin/chown root:root $user_ssh_dir");
            system("/bin/chmod 600 $user_ssh_dir");
        }
        # Create public/private key:
        if ((!-f "$user_ssh_dir/id_rsa") || (!-f "$user_ssh_dir/id_rsa.pub")) {
            &debug_msg("Running: /usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/id_rsa");
            system("/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/id_rsa");
        }
        # Create cert:
        if ((!-f "$user_ssh_dir/root.pem") || (!-f "$user_ssh_dir/root.pem.pub")) {
            &debug_msg("/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/root.pem");
            system("/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/root.pem");
            sleep(1);
            &debug_msg("/bin/cat $user_ssh_dir/root.pem.pub >> $user_ssh_dir/authorized_keys");
            system("/bin/cat $user_ssh_dir/root.pem.pub >> $user_ssh_dir/authorized_keys");
        }

    }
    elsif ($MasterObject->{'CLASS'} eq "User") {

        # This handles SSH access of users with shell access:
        &debug_msg("Working with a User Object.\n");

        # Check if that user has Shell access enabled:
        ($ok, $Shell_Object) = $cce->get($oid, "Shell");
        if ($Shell_Object->{'enabled'} eq "0") {
            # He hasn't. Stop processing:
            &debug_msg("User $MasterObject->{'name'} doesn't have shell enabled. Nice try!\n");
            $cce->bye('FAIL');
            exit(1);
        }

        $user_ssh_dir = $pw->home($MasterObject->{'name'}) . '/.ssh';
        &debug_msg("User's .ssh dir: $user_ssh_dir\n");

        # Start sane:
        undef $userName;
        undef $uid;
        undef $gid;
        undef $home;

        # Set work directory for this user get UID/GID:
        $userName = $MasterObject->{'name'};
        $uid = $pw->uid($MasterObject->{'name'});
        $gid = $pw->gid($MasterObject->{'name'});
        $home = $pw->home($MasterObject->{'name'});

        &debug_msg("User's UID/GID: $uid/$gid\n");

        # Set work directory for this user and create it if needed:
        if (!-d $user_ssh_dir) {
            system("/bin/mkdir $user_ssh_dir");
            system("/bin/chown $uid:$gid $user_ssh_dir");
            system("/bin/chmod 600 $user_ssh_dir");
        }
        # Create public/private key:
        if ((!-f "$user_ssh_dir/id_rsa") || (!-f "$user_ssh_dir/id_rsa.pub")) {
            &debug_msg("Running: /bin/su $userName -c \"/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/id_rsa\"");
            system("/bin/su $userName -c \"/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/id_rsa\"");
        }
        # Create cert:
        if ((!-f "$user_ssh_dir/$userName.pem") || (!-f "$user_ssh_dir/$userName.pem.pub")) {
            &debug_msg("Running: /bin/su $userName -c \"/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/$userName.pem\"");
            system("/bin/su $userName -c \"/usr/bin/ssh-keygen -q -N '' -b $SSH_Object->{'bits'} -t rsa -f $user_ssh_dir/$userName.pem\"");
            sleep(1);
            &debug_msg("/bin/su $userName -c \"/bin/cat $user_ssh_dir/$userName.pem.pub >> $user_ssh_dir/authorized_keys\"");
            system("/bin/su $userName -c \"/bin/cat $user_ssh_dir/$userName.pem.pub >> $user_ssh_dir/authorized_keys\"");
        }
    }
    else {
        # We should never get here, but if we do, we throw an error:
        &debug_msg("That's neither a 'System' nor a 'User' object!\n");
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