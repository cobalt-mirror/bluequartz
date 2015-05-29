#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: vsite_destroy.pl
#
# Actually removes all site users and the site itself updating the
# status as each user is destroyed.
#
# Usage:  vsite_destroy.pl <site name> <page to redirect to when finished>
#

use strict;
use CCE;
use FileHandle;
use File::Path;

# sane umask
umask(002);

# figure out where the status should be written
my $status_file = '';
my $redirect = $ARGV[1];

open(CFG, "/usr/sausalito/ui/conf/ui.cfg");
while (my $line = <CFG>) {
    if ($line =~ /^statusDir=(\S+)/) {
        my $dir = $1;
        $status_file = "$dir/remove$ARGV[0]";
        if (! -d $dir) {
            mkpath($dir, 0, 0755);
            chown(scalar(getpwnam('admserv')),
                scalar(getgrnam('admserv')), $dir);
            system('/bin/touch', $status_file);
            chown(scalar(getpwnam('admserv')),
                scalar(getgrnam('admserv')), $status_file);
        }
        last;
    }
}
close(CFG);

&update_status({
        'task' => '[[base-vsite.removingUsers]]',
        'progress' => 0
           });

my $cce = new CCE;
$cce->connectuds();

$cce->authkey($ENV{'CCE_USERNAME'}, $ENV{'CCE_SESSIONID'});

# make sure the passed site name is valid
my ($site_oid) = $cce->find('Vsite', { 'name' => $ARGV[0] });
if (!$site_oid) {
    &update_status({
                'done' => 1,
                'error' => "[[base-vsite.noSuchSite,site=$ARGV[0]]]"
               });
    $cce->bye();
    exit(1);
}

# find all the users to destroy
my @users = $cce->find('User', { 'site' => $ARGV[0] });

my $processed = 0;
my $total = scalar(@users) + 1;

for my $user (@users) {
    # override file check since this is a site destroy
    $cce->set($user, '', { 'noFileCheck' => 1 });
    my ($ok, @info) = $cce->destroy($user);
    if (!$ok) {
        # failed. update status and exit.
        &update_status({
                    'done' => 1,
                    'error' => &grab_error(@info)
                   });
        $cce->bye();
        exit(1);
    } else {
        $processed++;
        &update_status({
                'progress' => int(100 * ($processed / $total)),
                'task' => '[[base-vsite.removingUsers]]'
                   });
    }
}

# now destroy the site
&update_status({
        'progress' => int(100 * ($processed / $total)),
        'task' => '[[base-vsite.removingSite]]'
           });
my ($ok, @info) = $cce->destroy($site_oid);
my $exit = 0;
if ($ok) {
    &update_status({ 'done' => 1 });
} else {
    &update_status({
            'done' => 1,
            'error' => &grab_error(@info)
               });
    $exit = 1;
}

$cce->bye();
exit($exit);

sub update_status
{
    my $status = shift;

    # open file if necessary
    my $status_fh = new FileHandle(">$status_file");
    $status_fh->autoflush();
    if ($status->{done}) {
        if (exists($status->{error})) {
            print $status_fh "title: [[base-vsite.removeFailed]]\n";
            print $status_fh "message: $status->{error}\n";
            print $status_fh "isNoRefresh: true\n";
            print $status_fh "backUrl: $redirect\n";
        } else {
            print $status_fh "redirectUrl: $redirect\n";
        }
    } else {
        print $status_fh "title: [[base-vsite.deletingSite]]\n";
        print $status_fh "message: $status->{task}\n";
        print $status_fh "progress: $status->{progress}\n";
    }

    $status_fh->close();
}

sub grab_error
{
    my @info = @_;

    for my $msg (@info) {
        if (($msg =~ /^305 /) &&
            ($msg =~ /WARN\s+"(\[\[.+?\]\])"\s*$/)) {
            return $1;
        }
    }

    return '';
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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