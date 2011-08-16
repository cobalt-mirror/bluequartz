#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: vsite_anonftp.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# toggles all the necessary bits in the correct FtpSite object
# for the Vsite being modified.

use CCE;
use Base::Vsite qw(vsite_update_site_admin_caps);
use Base::HomeDir qw(homedir_get_group_dir);

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

my $cce = new CCE('Domain' => 'base-ftp');
$cce->connectfd();

# get vsite info
my $vsite = $cce->event_object();
my ($ok, $anonftp, $anonold, $anonnew) = $cce->get($cce->event_oid(), 'AnonFtp');
if (not $ok)
{
    $cce->bye('FAIL', '[[base-ftp.cantReadAnonFtp]]');
    exit(1);
}

# verify settings against limits quota must be less than site quota,
my ($vsite_disk);
($ok, $vsite_disk) = $cce->get($cce->event_oid(), 'Disk');
if (not $ok)
{
    $cce->bye('FAIL', '[[base-ftp.cantReadVsiteDiskInfo]]');
    exit(1);
}

# do bounds checking
if (($vsite_disk->{quota} >= 0) && ($anonftp->{quota} > $vsite_disk->{quota}))
{
    if ($cce->event_namespace() eq 'Disk' && $anonftp->{enabled})
    {
        $cce->baddata($cce->event_oid(), 'quota', 'siteQuotaLessThanFtpQuota', 
                { 'quota' => $anonftp->{quota} });
        $cce->bye('FAIL'); 
        exit(1);
    }
    elsif ($cce->event_namespace() ne 'Disk')
    {
        my $msg = 'quotaExceedsSiteQuota';
        if ($anonnew->{enabled})
        {
            $msg = 'quotaExceedsSiteQuotaEnabled';
        }

        $cce->baddata($cce->event_oid(), 'quota', $msg,
                { 'quota' => $vsite_disk->{quota} });
        $cce->bye('FAIL');
        exit(1);
    }
}
elsif ($cce->event_namespace() eq 'Disk')
{
    # no need to go farther for Vsite.Disk namespace
    $cce->bye('SUCCESS');
    exit(0);
}

# get the FtpSite object that this Vsite is tied to
my ($ftp_site_oid) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
if (not $ftp_site_oid)
{
    # fail, but maybe this should just create it if necessary
    $cce->bye('FAIL', '[[base-ftp.noFtpSite]]');
    exit(1);
}

($ok, my $ftp_site) = $cce->get($ftp_site_oid);
if (not $ok)
{
    $cce->bye('FAIL', '[[base-ftp.cantReadFtpSite]]');
    exit(1);
}

# check if this vsite has access to the anonymous section of this ftp site
if ($ftp_site->{anonymous} && ($ftp_site->{anonymousOwner} ne $vsite->{name}))
{
    # someone is using anonymous ftp and it is not this vsite
    # succeed if they don't have anon ftp enabled
    # otherwise need to fail because some other vsite owns anon ftp
    if (not $anonftp->{enabled})
    {
        $cce->bye('SUCCESS');
        exit(0);
    }
    else
    {
        $cce->bye('FAIL', "[[base-ftp.anonymousFtpInUse,ipaddr='$vsite->{ipaddr}']]");
        exit(1);
    }
}

# if this point is reached, we are free to tromp on the
# anonymous ftp settings
my $new_settings = {};

$new_settings->{anonymous} = $anonftp->{enabled};

# this vsite owns anon ftp for this ip address
if (not $anonftp->{enabled})
{
    $new_settings->{anonymousOwner} = '';
}
else # only set connection limit and quota if enabled
{
    $new_settings->{anonymousOwner} = $vsite->{name};
    $new_settings->{maxConnections} = $anonftp->{maxConnections};
    $new_settings->{quota} = $anonftp->{quota};
    if ($vsite->{basedir})
    {
        $new_settings->{anonBasedir} = $vsite->{basedir};
    }
    else
    {
        $new_settings->{anonBasedir} = homedir_get_group_dir($vsite->{name},
                                                $vsite->{volume});
    }
}

($ok, my $baddata) = $cce->set($ftp_site_oid, '', $new_settings);
if (not $ok)
{
    $DEBUG && print STDERR "BAD DATA\n";
    $DEBUG && print STDERR Dumper $baddata;
    for my $oid (keys %$baddata)
    {
        for my $key (keys %{$baddata->{$oid}})
        {
            my $map_key;
            if ($key eq 'anonymousOwner')
            {
                $map_key = 'name';
            }
            else
            {
                $map_key = $key;
            }
            $cce->baddata($oid, $map_key, $cce->unescape($baddata->{$oid}->{$key}));
        }
    }

    $cce->bye('FAIL', '[[base-ftp.cantUpdateFtpSite]]');
    exit(1);
}

# update site admin caps
if (!$cce->event_is_destroy()) {
	vsite_update_site_admin_caps($cce, $vsite, 'siteAnonFTP', $anonftp->{enabled});
}

# success
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
