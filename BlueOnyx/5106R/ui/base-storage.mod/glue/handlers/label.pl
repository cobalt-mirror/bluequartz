#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/storage
# $Id: label.pl,v 1.11 2001/10/16 17:19:16 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# 
# handle changing the label information
# just uses symlinks to allow changing the label after sites have
# been put on the disk

use CCE;
use Storage qw(storage_label_link);
use Sauce::Util;

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-storage');
$cce->connectfd();

my $disk = $cce->event_object();
my $old_disk = $cce->event_old();

if ($cce->event_is_destroy())
{
    $disk = $old_disk;
}

# just pass over internal disks
if ($disk->{internal})
{
    $cce->bye('SUCCESS');
    exit(0);
}

# label must be unique
if ($disk->{label} &&
    scalar($cce->find('Disk', { 'label' => $disk->{label} })) > 1)
{
    $cce->bye('FAIL', 'labelInUse', { 'label' => $disk->{label} });
    exit(1);
}

my $old_link = storage_label_link($old_disk->{label});
my $new_link = storage_label_link($disk->{label});

$DEBUG && warn("old link: $old_link");
$DEBUG && warn("new link: $new_link");

# always remove the old symlink
if (-l $old_link)
{
    unlink($old_link);
    Sauce::Util::addrollbackcommand("umask 000; /bin/ln -sf $disk->{mountPoint} \"$old_link\"");
}

# only need to worry if a partition is mounted
# just pass through internal disks although they never show up
# anyways
if (!$cce->event_is_destroy() && $disk->{mounted} && !$disk->{internal})
{
    # add the new link
    if (not Sauce::Util::linkfile($disk->{mountPoint}, $new_link))
    {
        $cce->warn('[[base-storage.changeLabelFailed]]');
        $cce->bye('FAIL');
        exit(1);
    }
}

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
