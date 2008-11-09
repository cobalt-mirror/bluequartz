#!/usr/bin/perl

# determine what db files are referenced in /etc/named.conf, 
# delete the unused from /var/named/.
#
# Will DeHaan for Cobalt Networks, Inc. 2000 
# $Id: purge_db.pl 256 2003-10-28 15:25:35Z shibuya $

# defs
my $conf = '/etc/named.conf';
my $db_dir = '/var/named/';

# determine active db files
my %active;
open(CNF, $conf) || exit 0;
while(<CNF>) {
	if (/^\s*file\s*\"([^\"]+)\"/) {
		$active{$1} = 1;
		$active{$1.'.include'} = 1;
		$active{$1.'~'} = 1;
	}
}
close(CNF);

# List and purge existing db files
opendir(DBS, $db_dir) || exit 0;
my $dir;
while($dir = readdir(DBS)) {
	next if ($dir !~ /^db\./);
	unlink($db_dir.$dir) unless ($active{$dir});
}
closedir(DBS);

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
