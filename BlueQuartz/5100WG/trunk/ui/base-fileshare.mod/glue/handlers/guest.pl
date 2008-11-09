#!/usr/bin/perl -I. -I/usr/sausalito/perl
#
# Copyright(c) 2000, Cobalt Networks, Inc.
# Author: asun@cobalt.com
#
# modify guest share access. while this handles two cases,
# it only gets one case at a time.
#
use Sauce::Config;
use CCE;

my $cce = new CCE(Namespace => 'FileShare');
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();

my (%settings, $err);

# change the user name
if ($old->{guestUser} ne $obj->{guestUser}) {
    my $uid = getpwnam($obj->{guestUser});

    if (defined($uid)) {
	$cce->bye('FAIL', '[[base-fileshare.userAlreadyTaken]]');
	exit 1;
    }
    
    `/usr/sbin/usermod -g '$obj->{guestGroup}' -l '$obj->{guestUser}' '$old->{guestUser}'`;
}

# change the user's group
`/usr/sbin/usermod -g '$obj->{guestGroup}' '$obj->{guestUser}'` if $old->{guestGroup} ne $obj->{guestGroup};

if ($old->{guestWorkGroup} ne $obj->{guestWorkGroup}) {
    my @oids = $cce->find('Workgroup', { 'name' => $old->{guestWorkGroup}},'');
    if (not @oids) {
	$cce->bye('FAIL', '[[base-fileshare.groupDoesntExist]]');
	exit 1;
    }

    $err = $cce->set($oids[0], '', { 'name' => $obj->{guestWorkGroup} });
    if ($err) {
	$cce->bye('FAIL', '[[base-fileshare.cantChangeGroup]]');
	exit 1;
    }

    # restore old permissions
    my $dir = Sauce::Config::groupdir_base . "/$obj->{guestWorkGroup}";
    my $uid = getpwnam(Sauce::Config::groupdir_owner);
    my $gid = (getpwnam($obj->{guestUser}))[3];
    chown($uid, $gid, $dir);
    chown($uid, $gid, "$dir/incoming");
    chown($uid, $gid, "$dir/incoming/.AppleDouble");
    chmod(02775, $dir);
    chmod(02773, "$dir/incoming");
    chmod(02773, "$dir/incoming/.AppleDouble"); 
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
