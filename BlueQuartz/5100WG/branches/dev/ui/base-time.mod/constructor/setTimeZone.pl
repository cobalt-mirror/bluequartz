#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: setTimeZone.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use CCE;

my $cce=new CCE;
$cce->connectuds();

my($oid)=$cce->find("System");
my($ok,$sys)=$cce->get($oid,"Time");

if (-l '/etc/localtime') {
	my $tz=readlink('/etc/localtime');
	$tz=~s#.*/([^/]+)/([^/]+)$#$1/$2#;
	$cce->set($oid,"Time",{timeZone=>$tz}) if $sys->{timeZone} ne $tz;
}
# else we're screwed, since that link is made by one of the scripts on the BTOS.
# if it isn't there then knowing the correct time zone is likely to be the 
# least of our worries

# Commit queued time changes, if necessary
$sys->{timeZone} = $sys->{deferTimeZone} if($sys->{deferTimeZone});
$cce->set($oid,"Time",{
 	 'deferCommit'		=>	0, 
	 'timeZone'		=>	$sys->{timeZone}, 
	 'deferTimeZone'	=>	'',
	}) if($sys->{deferCommit});

$cce->bye('SUCCESS');
exit 0;

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
