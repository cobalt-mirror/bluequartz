#!/usr/bin/perl -I/usr/sausalito/perl

use strict;
use CCE;

my $cce = new CCE(Domain => "base-winnetwork");
$cce->connectfd();

my $old = $cce->event_old();
my $new = $cce->event_object();

my $destroy = $cce->{destroyflag};

my $ok = 0;

# Check that this machine name isn't already taken...
my @oids = $cce->find("WindowsMachine", {name=>$new->{name}});

if (@oids > 1 || 
	(@oids == 1 && $oids[0] != $cce->event_oid())) {
	$cce->warn("windowsMachineAlreadyExists", {name=>$new->{name}});
	$cce->bye("FAIL");
	exit;
}


# clear the old name if it exists..
if ($old->{name}) {
	$old->{name} =~ s/[`';]/_/g;
	if ((system ("/usr/bin/smbpasswd -x '". $old->{name} . "\$' > /dev/null")  
           || system ("/usr/sbin/userdel '" . $old->{name} . "\$' > /dev/null")) 
&& (!$destroy)) {
		$cce->warn("couldNotClearOldMachine", {name=>$old->{name}});
		$cce->bye("FAIL");
		exit;
	}
}

if (!$destroy) {
        $new->{name} =~ s/[`';]/_/g;
	if (system ("/usr/sbin/useradd -d /dev/null -s /bin/false '" . $new->{name} . "\$' > /dev/null")
	   || system ("/usr/bin/smbpasswd -a -m '" . $new->{name} . "' > /dev/null")
) {
		$cce->warn("couldNotCreateNewMachine", {name=>$new->{name}}); 
		$cce->bye("FAIL");
		exit;
	}
}

$cce->bye("SUCCESS");
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
