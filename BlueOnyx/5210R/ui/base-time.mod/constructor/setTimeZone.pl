#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: setTimeZone.pl

use CCE;

my $cce=new CCE;
$cce->connectuds();

my($oid)=$cce->find("System");
my($ok,$sys)=$cce->get($oid,"Time");

if (-l '/etc/localtime') {
	my $tz=readlink('/etc/localtime');
	$tz=~s#.*/([^/]+)/([^/]+)$#$1/$2#;
	if ($tz eq 'zoneinfo/UTC') {
		$tz = 'UTC';
	}
	$cce->update($oid,"Time",{timeZone=>$tz}) if $sys->{timeZone} ne $tz;
}
else {
	system("/bin/rm -f /etc/localtime");
	#system("/bin/ln -s /usr/share/zoneinfo/America/New_York /etc/localtime");
	# This seems to work better these days (SL6):
	system("/bin/ln -s /usr/share/zoneinfo/US/Eastern /etc/localtime");
        my $tz=readlink('/etc/localtime');
        $tz=~s#.*/([^/]+)/([^/]+)$#$1/$2#;
		if ($tz eq 'zoneinfo/UTC') {
			$tz = 'UTC';
		}
        $cce->update($oid,"Time",{timeZone=>$tz}) if $sys->{timeZone} ne $tz;
}
# If that doesn't work we're screwed, since that link is usually made by one of the scripts on the BTOS.
# if it isn't there then knowing the correct time zone is likely to be the 
# least of our worries

# Commit queued time changes, if necessary
$sys->{timeZone} = $sys->{deferTimeZone} if($sys->{deferTimeZone});
$cce->update($oid,"Time",{
 	 'deferCommit'		=>	0, 
	 'timeZone'		=>	$sys->{timeZone}, 
	 'deferTimeZone'	=>	'',
	}) if($sys->{deferCommit});

$cce->bye('SUCCESS');
exit 0;

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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