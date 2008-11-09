#!/usr/bin/perl -I/usr/sausalito/perl
#
# this adds the guest user and group.
use Sauce::Config;
use CCE;
use Quota;

my $BlocksPerMB = 1024;

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'FileShare'); 
if (not ($ok and $obj)) {
	$cce->bye('FAIL');
	exit 1;	
}

my $user = $obj->{guestUser};
my $group = $obj->{guestGroup};
my $wg = $obj->{guestWorkGroup};
if (not ($obj and $user and $wg and $group)) {
    $cce->bye('FAIL');
    exit 1;
}

my $err;

# try to create the group
if (not $cce->find('Workgroup', { "name" => $wg } )) {
    $ok = $cce->create('Workgroup', { name => $wg, enabled => 1,
				      dont_delete => 1,
				      description => '[[base-fileshare.guestDescription]]',
				      desc_readonly => 1,
				      members => '&admin&' });
    if (!$ok) {
	$cce->bye('FAIL');
	exit 1;
    }
}

# okay, we now need to create the user
# NOTE: the guest user's group should not be the same as the workgroup.
my $dir = Sauce::Config::groupdir_base . '/' . $wg;
my $useradd = Sauce::Config::bin_useradd;
`$useradd -c 'guest share user' -d $dir -p '*' -s /bin/badsh -g $group $user` unless getpwnam($user);

# now, create/reset a public share. this should *not* be owned by 
# the guest user.
my $guestid = getpwnam($user);
my $uid = getpwnam(Sauce::Config::groupdir_owner());
my $gid = getgrnam($wg);
chown($uid, $gid, $dir);
chmod(02775, $dir);

# drop box
mkdir("$dir/incoming", 02773);
chmod(02773, "$dir/incoming"); # in case it already exists
chown($uid, $gid, "$dir/incoming");
mkdir("$dir/incoming/.AppleDouble", 02773);
chmod(02773, "$dir/incoming/.AppleDouble"); # in case it already exists
chown($uid, $gid, "$dir/incoming/.AppleDouble");

# currently, .AppleDesktop has to be writeable by others or else 
# guest copies of applications fail.
mkdir("$dir/.AppleDesktop", 02777);
chmod(02777, "$dir/.AppleDesktop"); 
chown($uid, $gid, "$dir/.AppleDesktop");

# create/touch a file to make quota stuff complain less.
open(FILE, ">$dir/.guest_settings");
close(FILE);
chown($guestid, $gid, "$dir/.guest_settings");
chmod(0000, "$dir/.guest_settings");

# now set the quota on the share
# this would work better if i could just force the handler to run.
my ($softquota, $hardquota, $softinode, $hardinode);
my $quota = $obj->{guestQuota}*$BlocksPerMB;
my $dev = Quota::getqcarg($dir);
Quota::sync($dev);
if ($quota eq 0) {
	$softquota = $hardquota = $softinode = $hardinode = 1;
} elsif ($quota gt 0) {
	$softquota = $quota;
	$hardquota = $softquota + $BlocksPerMB;
}
Quota::setqlim($dev, $guestid, $softquota, $hardquota, $softinode, $hardinode);


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
