#!/usr/bin/perl
# Test for bind 8.2.x secondary db corruption
#
# The syntax appears to be "named-xfer[<pid>]: send AXFR query 0 to <IP>"
# erroneously prepended to the db by named.
#
# This utility scans db files and tests/repairs this issue.
#

use strict;

my $named_conf = '/etc/named.conf';
my $named_dir = '/var/named/';

my @sec_db_files;
my $mark = 0;
open(CONF, $named_conf) || exit 0;
while(<CONF>)
{
	if(/type\s+slave/)
	{
		$mark = 1;
	}
	elsif($mark && /file\s+\"([^\"]+)\"/)
	{
		push(@sec_db_files, $1) if (-r $named_dir.$1);
		$mark = 0;
	}
}

foreach my $db_file (@sec_db_files)
{
	open(ZONE, $named_dir.$db_file) || next;
	my $test = <ZONE>;
	if($test =~ /^named\-xfer/)
	{
		my $fixed;
		while(<ZONE>) { $fixed .= $_; }
		close(ZONE);

		open(UPDATED, ">$named_dir$db_file") || next;
		print UPDATED $fixed;
		close(UPDATED);

		chmod(0640, $named_dir.$db_file);
		system('/etc/rc.d/init.d/named reload >/dev/null 2>&1');
	}
	else
	{
		close(ZONE);
	}
}

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
