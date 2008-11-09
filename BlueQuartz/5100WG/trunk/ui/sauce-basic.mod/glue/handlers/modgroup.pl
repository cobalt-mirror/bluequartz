#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/sauce-basic

my $DEBUG = 0;

use CCE;
use Sauce::Util;
#use Workgroup;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();
my $oid = $cce->event_oid();

$DEBUG && print STDERR "$0: modifying group ",$obj->{name},"\n";

my $members = $obj->{members} || "";
my @members = CCE->scalar_to_array($members);
$members = join(",",@members);

# Workgroup::validate($cce);

my $fun = sub {
	my ($fin, $fout) = (shift, shift);
	my $oldname = $old->{name};
	my $newname = $obj->{name};
	my $ok = 0;
	while ($_ = <$fin>) {
		if (m/^${oldname}:x:(\d+)/) {
			print $fout $newname,":x:",$1,":",$members,"\n";
			$ok = 1;
		} else {
			print $fout $_;
		}
	}
	return $ok;
};
my $ret = Sauce::Util::editfile("/etc/group", $fun);
chmod(0644,'/etc/group');
if (!$ret) {
	print STDERR "Could not edit /etc/group\n";
	$cce->bye('FAIL');
	exit 1;
};

if ($old->{name} ne $obj->{name}) {
	# move the group home directory
	my $olddir = "/home/groups/".$old->{name};
	my $newdir = "/home/groups/".$obj->{name};
	system ("/bin/mv", $olddir, $newdir);
};

$cce->bye('SUCCESS');
exit 0;

# done.
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
