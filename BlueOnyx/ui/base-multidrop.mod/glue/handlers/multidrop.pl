#!/usr/bin/perl -w -I/usr/sausalito/perl/

use strict;

use CCE;

use Sauce::Util;
use Sauce::Config;
use Sauce::Validators qw(hostname username password alters);

my $cce = new CCE( Namespace => 'Multidrop',
                   Domain    => 'base-multidrop' );

$cce->connectfd();

# Globals.

my $Fetchmailrc_dir = "/etc/fetchmail";
my $Fetchmailrc_conf = $Fetchmailrc_dir . "/multidrop:localdomain";
my $chkconfig = "/sbin/chkconfig";
my $scriptName = "fetchmail";
my $scriptLoc = "/etc/init.d/fetchmail";


my $obj = $cce->event_object();

if($obj->{interval} < 1){
	$cce->warn("[[base-multidrop.badInterval]]");
	$cce->bye("FAIL");
}

# do this here or we lose the password, if they don't re-enter it
# and enable multidrop later
umask(0176); #for fetchmailrc file
Sauce::Util::replaceblock($Fetchmailrc_conf,
	'# Start Cobalt Multidrop Domain',
	&set_domain_rc(),
	'# End Cobalt Multidrop Domain') || finish($cce);

if($obj->{enable}){
	system($chkconfig ,'--add', $scriptName);
	system("$scriptLoc restart > /dev/null 2>&1");
}else{
	system($chkconfig,'--del', $scriptName);
	system("$scriptLoc stop > /dev/null 2>&1");
}

$cce->bye("SUCCESS");

exit(0);

sub finish
{
	my $cce = shift;
	$cce->warn("failure");
	$cce->bye("FAIL");
	exit 1;
}


sub set_domain_rc
{
	my $ret="";
	my $interval=$obj->{interval}*60; #minutes to seconds
	my $pass="";
	#produces server-specific (well...mostly)
	#fetchmail conf stuff.

	if(-e $Fetchmailrc_conf){
		open(RC,$Fetchmailrc_conf);
		my @rc=<RC>;
		close RC;
		(grep {/with pass/} @rc)[0] =~ /pass "([^\s]+)"/;
		$pass=$1;
	}
	
	$pass=$obj->{password} || $pass;

	$ret="set daemon $interval\n";
	$ret.="poll $obj->{server} aka $obj->{userDomain} proto $obj->{proto}:\n";
	$ret.="user $obj->{userName} with pass \"$pass\" to * here\n";

	return $ret;
}

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
