#!/usr/bin/perl -I/usr/sausalito/perl/ -w
# $Id: forward.pl

use CCE;

use Sauce::Config;
use Sauce::Util;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
		use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );

$cce->connectfd();

my $errors;

my($success, $user, $old, $new) = $cce->get($cce->event_oid());

my $mail = $cce->event_object();

my @pwent = getpwnam($user->{name});
my $forward_file = $pwent[7] . '/.forward';

my $data = "";
my $bad_local_users = "";

&debug_msg("ui_enabled: " . $user->{ui_enabled} . "\n");

#ui_enabled

if ((! $mail->{forwardEnable}) || (! $user->{ui_enabled})) {
	$data .= "# forwarding not enabled\n";
} else {
	for my $forward (CCE->scalar_to_array($mail->{forwardEmail})) {
		$data .= "$forward\n";
	}
}
if ($mail->{forwardEnable} && $mail->{forwardSave}) {
	$data .= "\\" . $user->{name};
}

Sauce::Util::replaceblock( $forward_file,
	'# forward.pl: Do not edit below this line',
	$data,
	'# forward.pl: Do not edit above this line',
	0644);

# .forward needs to be 0644 or sendmail ignores it
Sauce::Util::chmodfile(0644, $forward_file);
Sauce::Util::chownfile(@pwent[2,3], $forward_file);

if ($bad_local_users) {
	$cce->warn("nonExistentUser", { 'users' => "$bad_local_users" });
	$cce->bye("FAIL");
	exit(1);
}

$cce->bye("SUCCESS");
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


# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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