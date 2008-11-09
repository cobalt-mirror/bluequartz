#!/usr/bin/perl -I. -I/usr/sausalito/perl
#
# Copyright(c) 2000, Cobalt Networks, Inc.
# Author: asun@cobalt.com
#
# this just modifies the quota for the public share
use Quota;
use CCE;

my $BlocksPerMB = 1024;

my $cce = new CCE(Namespace => 'FileShare');
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();

# sanity checking is good
my $user = $obj->{guestUser};
my ($uid, $dir) = (getpwnam($user))[2,7];
unless ($user and $uid) {
    $cce->bye('FAIL', '[[base-fileshare.invalidUser]]');
    exit 1;
}

unless (-d $dir) {
    $cce->bye('FAIL', '[[base-fileshare.invalidPath]]');
    exit 1;
}

my $dev = Quota::getqcarg($dir);
Quota::sync($dev);

# just blindly reset the quota. 
my ($softquota, $hardquota, $softinode, $hardinode);
my $quota = $obj->{guestQuota}*$BlocksPerMB;

# figure out the quota
if ($quota eq 0) { # no quota is really quota for one file
    $softquota = 1;
    $hardquota = 1;
    $softinode = 1;
    $hardinode = 1;
} elsif ($quota gt 0) {
    $softquota = $quota;
    $hardquota = $softquota + $BlocksPerMB;
}

# trailing parameters 0, 1 indicate group
if (Quota::setqlim($dev, $uid, $softquota, $hardquota, 
		   $softinode, $hardinode, 0, 1)) {
    $cce->bye('FAIL', '[[base-fileshare.cantSetQuota]]');
    exit 1;
}
    
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
