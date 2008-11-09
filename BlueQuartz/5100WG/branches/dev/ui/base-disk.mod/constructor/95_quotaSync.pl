#!/usr/bin/perl -I/usr/sausalito/perl

use Quota;
use CCE;

my $cce=new CCE;
$cce->connectuds();

my @userOids=$cce->find("User");
my $BLOCKS_PER_MB=1024;
my $skipped_items=0;
my $oid;
my $ok;

my ($user, $disk);
foreach $oid (@userOids){
    ($ok, $user) = $cce->get($oid);
    ($ok, $disk) = $cce->get($oid, "Disk");
    my ($uid,$dir)=(getpwnam($user->{name}))[2,7];
    unless(defined $uid && defined $dir){
	$skipped_items++;
	next;
    }
    my $dev=Quota::getqcarg($dir);
    if ($disk->{quota} == 0) {
	#set a 1kb quota
	Quota::setqlim($dev,$uid,1,1,1,1);
    } elsif ($disk->{quota} > 0) {
	my $hardQuota = ($disk->{quota}*$BLOCKS_PER_MB) + $BLOCKS_PER_MB;
	my $softQuota = ($disk->{quota}*$BLOCKS_PER_MB);
	Quota::setqlim($dev,$uid,$softQuota,$hardQuota,0,0);
    } else { #negative quota==no quota
	Quota::setqlim($dev,$uid,0,0,0,0);
    }
}

my @workgroupOids=$cce->find("Workgroup");

# the extra two parameters on the setqlim call are to make 
# it apply to groups.  the first one is documented, but
# the second one isn't.  it is 0=user, 1=group.

my ($workgroup);
foreach $oid (@workgroupOids){
    ($ok, $workgroup) = $cce->get($oid);
    ($ok, $disk) = $cce->get($oid, "Disk");
    my $gid=(getgrnam($workgroup->{name}))[2];
    unless(defined $gid){
	$skipped_items++;
	next;
    }
    my $dev=Quota::getqcarg("/home");
    if($disk->{quota} == 0){
	#set a 1kb quota
	Quota::setqlim($dev,$gid,1,1,1,1,0,1);
    }elsif($disk->{quota} > 0){
	my $hardQuota = ($disk->{quota}*$BLOCKS_PER_MB) + $BLOCKS_PER_MB;
	my $softQuota = ($disk->{quota}*$BLOCKS_PER_MB);
	Quota::setqlim($dev,$gid,$softQuota,$hardQuota,0,0,0,1);
    }else{ # negative quota==no quota
	Quota::setqlim($dev,$gid,0,0,0,0,0,1);
    }
}

if($skipped_items){
    $cce->bye("FAIL");
}else{
    $cce->bye("SUCCESS");
}
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
