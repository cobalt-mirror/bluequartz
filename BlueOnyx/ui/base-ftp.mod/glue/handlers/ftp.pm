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
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
