#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#$Id: modam.pl 3 2003-07-17 15:19:15Z will $
# 
# ActiveMonitor.* modify handler
# author: Tim Hockin (hacked from Jonathan Mayer <jmayer@cobalt.com>)
#

use strict;
use Sauce::Config;
use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

# retreive am object data:
my $oid = $cce->event_oid();

if (!$oid) {
	# something is very, very wrong.
	$cce->bye('FAIL', 'Bad CSCP header');
	exit(1);
}

my $amobj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

##########################################################################
# actuate changes
##########################################################################
my $enabled = $amobj->{enabled} ? "true" : "false";
my $maillist = $amobj->{alertEmailList} || "";
my @mla = $cce->scalar_to_array($maillist);
$maillist = join(", ", @mla);

my $fun = sub {
	my ($fin, $fout) = (shift, shift);

	while (defined($_ = <$fin>)) {
		# setup the swatch process
		s/^\s*enabled\s*=.*$/enabled = $enabled/;
		s/^\s*email_list\s*=.*$/email_list = $maillist/;
		print $fout $_;
	}
};

Sauce::Util::editfile("/etc/swatch.conf", $fun);

# comfortable shoes.
$cce->bye('SUCCESS');
exit(0);
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
