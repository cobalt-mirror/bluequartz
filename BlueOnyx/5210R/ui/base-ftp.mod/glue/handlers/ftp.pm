#!/usr/bin/perl -I. -I/usr/sausalito/perl
#
# ftp config for proftpd

package ftp;

use Sauce::Config;

sub ftp_getconf
{
	return '/etc/proftpd.conf';
}

sub ftps_getconf
{
	return '/etc/proftpds.conf';
}

sub ftp_getscript
{
	return '/etc/xinetd.d/proftpd';
}

sub ftps_getscript
{
	return '/etc/xinetd.d/proftpds';
}

sub ftp_anonscript
{
	my ($user, $group, $wg, $groupdir, $maxusers) = @_;

	# handle some special arguments that don't always apply
	if (not $groupdir)
	{
		$groupdir = Sauce::Config::groupdir_base . "/$wg";
	}

	if ($maxusers) 
	{ 
		$maxusers = "MaxClients\t$maxusers\n\t\t"; 
	}
	else 
	{
		$maxusers = '';
	}

	my $anonscript=<<END;
	<Anonymous $groupdir>
		User	  $user
		Group	 $group
		UserAlias anonymous $user
		UserAlias guest $user
		UserAlias ftp $user
		$maxusers<Directory *>
			<Limit WRITE>
				DenyAll
			</Limit>
		</Directory>
		<Directory $groupdir/incoming/*>
			Umask	002
			AllowOverwrite off
			<Limit STOR>
				AllowAll
			</Limit>
			<Limit READ DIRS>
				DenyAll
			</Limit>
		</Directory>
	</Anonymous>
END
}

sub edit_anon
{
	my ($input, $output, $enabled, $user, $group, $wg) = @_;
	my $script = ftp_anonscript($user, $group, $wg) if $enabled;
	print $output $script;
	return 0;
}

1;
# 
# Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
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