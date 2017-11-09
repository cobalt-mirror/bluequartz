#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: reload_httpd.pl

# signal apache to reload it's configuration files.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

# Get Event Object:
my $obj = $cce->event_object();

# If the Event is CREATE or MODIFY and Vsite->{force_update} isn't yet set, then we do NOT
# restart Apache. On CREATE this is empty until the GUI has populated CODB with all options.
# Only then, as last act it sets Vsite->{force_update}. That is when we want to restart during
# a CREATE transaction. During MODIFY this will already be set from the CREATE *or* the last 
# MODIFY transaction. Hence during MODIFY we will always restart Apache. But only once, as there
# is only Handler that runs that matters and all Parameters that trigger *this* handler are in
# the same CLEANUP stage anyway.
if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($obj->{force_update} eq '')) {
	$cce->bye('SUCCESS');
	exit(0);
}

&debug_msg("Issuing service_run_init('httpd', 'reload') for event_object: " . $obj->{OID} . "");

service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

# For debugging:
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

# 
# Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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