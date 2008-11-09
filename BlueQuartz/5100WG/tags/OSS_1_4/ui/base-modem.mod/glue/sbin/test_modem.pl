#!/usr/bin/perl -w -I/usr/sausalito/handlers/base/modem

use strict;
use Modem;
use FileHandle;
use Fcntl qw( O_WRONLY O_CREAT O_EXCL );

my $pppd_cmd =  '/usr/sbin/pppd ' . &make_pppd_cmd(Modem::ppp_cfg()) .
		"connect \"/usr/sbin/chat -v -s -f " . Modem::chatscript() . " 2>$ARGV[0]\" &>$ARGV[1] &";

# MPBug fixed
# create log files safely
for my $file ($ARGV[0], $ARGV[1]) {
	unlink($file);
	sysopen(FH, $file, O_WRONLY|O_CREAT|O_EXCL, 0644) || exit 255;
	close(FH);
}

if(not exec($pppd_cmd)){
	exit 3;
} else {
	exit 0;
}	

sub make_pppd_cmd {
	my $file = shift;
	my $fh = new FileHandle($file);
	my $cmd_string = "lock nodetach ";

	# read in entire file first
	my %phash = ();
	while (<$fh>) {
		next if (/^#/);
		
		# find the key and value
		/^(\w+)\=(.*)$/;

		my $attr = $1;
		my $value = $2;

		# substitute in previous values that were defined in file 
		if ($value =~ m/\$(\w+)/ && defined($phash{$1})) {
			$value =~ s/\$(\w+)/$phash{$1}/g;
		}
		$value =~ s/[\"\']//g;
		
		# always overwrite with the new value, this will preserve value if variable name is on right hand side
		$phash{$attr} = $value;
	}

	$fh->close();

	# build options string to pass to pppd
	if ($phash{HARDFLOWCTL} =~ /yes/i) {
		$cmd_string .= "modem crtscts ";
	}

	if ($phash{ESCAPECHARS} =~ /yes/i){
		$cmd_string .= "asyncmap 00000000 ";
	}

	if ($phash{DEFROUTE} =~ /yes/i){
		$cmd_string .= "defaultroute ";
	}

	if ($phash{MRU}) {
		$cmd_string .= "mru $phash{MRU} ";
	}

	if ($phash{MTU}) {
		$cmd_string .= "mtu $phash{MTU} ";
	}

	if ($phash{IPADDR} || $phash{REMIP}) {
		$cmd_string .= "$phash{IPADDR}:$phash{REMIP} ";
	}

	if ($phash{PAPNAME}) {
		$cmd_string .= "name $phash{PAPNAME} ";
	}
	
	if ($phash{DEBUG} =~ /yes/i) {
		$cmd_string .= "debug ";
	}

	$cmd_string .= "$phash{MODEMPORT} $phash{LINESPEED} remotename $phash{DEVICE} ";

	# need to strip out demand dialing options
	$phash{PPPOPTIONS} =~ s/(?:demand|idle\s*\d+|holdoff\s*\d+)//g;
	$cmd_string .= "ipparam $phash{DEVICE} $phash{PPPOPTIONS} idle 15 ";

	return $cmd_string;
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
