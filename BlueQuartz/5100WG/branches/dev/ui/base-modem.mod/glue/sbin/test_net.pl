#!/usr/bin/perl -w

# there should probably be a better way to test the network
# but for right now this works

use strict;
use lib qw( /usr/sausalito/handlers/base/modem );
use Modem;
use Fcntl qw( O_WRONLY O_CREAT O_EXCL );

if (not $ARGV[0]) {
	print "Usage:  test_net.pl <logfile>\n\n";
	exit 0;
}

# MPBug FIXED
# create our log file safely
unlink($ARGV[0]);
sysopen(LOG, $ARGV[0], O_WRONLY|O_CREAT|O_EXCL, 0644) || exit 255;
autoflush LOG 1;

my @hosts_to_try = (	'www.netscape.com',
			'www.yahoo.com',
			'www.cobalt.com',
			'www.w3c.org'
			);

my $hostfound = 0;
my $connect = 0;

# test dns
for my $host (@hosts_to_try) {
	my @results = gethostbyname($host);

	if (@results) {
		print LOG "dns ok\n";
		$hostfound = 1;
		last;
	}
}

if (not $hostfound) {
	print LOG "dns failed\n";
}

# test network separately, in case we are on a private ppp link
if (Modem::test_net('ppp0')) {
	print LOG "network ok\n";
} else {
	print LOG "network failed\n";
}

close LOG;

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
