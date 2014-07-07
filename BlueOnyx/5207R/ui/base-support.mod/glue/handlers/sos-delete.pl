#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: sos-delete.pl
#
# This handler is run whenever a SOS-Report needs to be deleted.

# Debugging switch:
$DEBUG = "1";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

# Uncomment correct type:
#$whatami = "constructor";
$whatami = "handler";

#
#### No configureable options below!
#

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();

    # Get our events from the event handler stack:
    $oid = $cce->event_oid();
    $obj = $cce->event_object();

    $old = $cce->event_old();
    $new = $cce->event_new();

    # Get the System Object:
    @sysoids = $cce->find('System');
    ($ok, $Support) = $cce->get($sysoids[0], 'Support');

    # We're creating or modifying the Support object:
    if (($cce->event_is_modify())) {
        if (-d '/usr/sausalito/ui/web/debug') {
            # Delete and recreate /usr/sausalito/ui/web/debug
            &debug_msg("Deleting /usr/sausalito/ui/web/debug"); 
            system("/bin/rm -Rf /usr/sausalito/ui/web/debug");
            &debug_msg("Creating /usr/sausalito/ui/web/debug"); 
            system("/bin/mkdir /usr/sausalito/ui/web/debug");
            system("/bin/chmod 755 /usr/sausalito/ui/web/debug");
            &debug_msg("Informing CODB that the SOS-Report is gone.");
            ($ok, $badkeys, @info) = $cce->set($sysoids[0], 'Support', { 'sos_present' => '0', 'sos_internal' => '', 'sos_external' => '', 'sos_trigger' => '0' });
        }
    }
    $cce->bye('SUCCESS');
    exit(0);
}
else {
    $cce->connectuds();
    $cce->bye('SUCCESS');
    exit(0);
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