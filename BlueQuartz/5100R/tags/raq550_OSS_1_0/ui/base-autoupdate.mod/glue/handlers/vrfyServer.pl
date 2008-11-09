#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use POSIX;

$cce = new CCE;
$cce->connectfd();

$server = $cce->event_object();

if($server->{name} ne "autoupdate"){
	$cce->bye("SUCCESS");
	exit(0);
}

if(testServer($server->{location})){
	$cce->bye("SUCCESS");
	exit(0);
}

$cce->warn("[[base-autoupdate.invalidServer]]");
$cce->("FAIL");
exit(1);

sub testServer{
	my($server) = @_;

	my $file = POSIX::tmpnam();
	print D "$file\n";
#	my $authstr;

#	if($pass){
#		$authstr = "--http-user $pass --http-pass $pass";
#	}else{
#		$authstr = "";
#	}

	open(WGET, "/usr/bin/wget -t 2 -T 60 \"$server\" -O $file 2>&1 |");
	print D "wget starting:";
 
    while (<WGET>) {
	print D;
        if (/Host\s+not\s+found/i) {
            close(WGET);
	    system("/bin/rm -f $file");
            return 0;
        }
 
        if (/404\s+Not\s+Found/i) {
            close(WGET);
	    system("/bin/rm -f $file");
            return 0;
        }
 
        if (/refused/i) {
            close(WGET);
	    system("/bin/rm -f $file");
            return 0;
        }

	if (/Giving\s+up./i) {
	    close(WGET);
	    system("rm -f $file");
	    return 0;

	}
	if (/Authorization\s+Required/i) {
	    close(WGET);
            system("rm -f $file");
            return 0;

	}
    }

	my $info=`/usr/bin/file $file`;
	system("/bin/rm -f $file");
	if($info=~/tar|gzip/){
		return 1;
	}else{
		return 0;
	}
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
