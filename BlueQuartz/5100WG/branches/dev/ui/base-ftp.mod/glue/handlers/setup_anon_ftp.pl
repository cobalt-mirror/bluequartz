#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# $Id: setup_anon_ftp.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# setup_anon_ftp.pl
# setup the directories and add/remove the anonymous ftp user
# from the system
#
use CCE;
use Sauce::Config;
use I18n;
use Quota;
use File::Path;
use Base::User qw(useradd userdel);
use Base::Group qw(groupadd);

# set DEBUG to 1 to turn on debug logging to STDERR
my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

# some useful variables
my ($ok, $errors) = (0, 0);

my $cce = new CCE('Domain' => 'base-ftp');
$cce->connectfd();

# FtpSite info
my $ftp_site = $cce->event_object();
my $ftp_site_old = $cce->event_old();
my $ftp_site_new = $cce->event_new();

# have everything needed, so deal with the filesystem and user
umask(002);
my $group = $ftp_site->{anonymousOwner} ? $ftp_site->{anonymousOwner} : $ftp_site_old->{anonymousOwner}; 
my $ftpuser = uc($group);
my $i18n = new I18n;
my $sys_locale = I18n::i18n_getSystemLocale();

# figure out the home directory
my $home_dir = $ftp_site->{anonBasedir} . '/ftp';

$i18n->setLocale($sys_locale);

$DEBUG && print STDERR Dumper($ftp_site_old, $ftp_site_new, $ftp_site);

# deal with changing the anonymousOwner
if ($ftp_site_new->{anonymousOwner} && $ftp_site_old->{anonymous})
{
    $DEBUG && print STDERR "changing owner\n";
    my @ftp_sites = $cce->find('FtpSite', 
                            { 
                                'anonymousOwner' => $ftp_site_old->{anonymousOwner},
                                'anonymous' => 1
                            });
    
    $DEBUG && print STDERR "found " . scalar(@ftp_sites) . " ftp sites\n";
    # only try deleting the user if it is no longer an active anonymousOwner
    if (scalar(@ftp_sites) == 0 
        && !(userdel(0, uc($ftp_site_old->{anonymousOwner})))[0])
    {
        $cce->warn("[[base-ftp.cantDelAnonFtpUser,user='" .
                    uc($ftp_site_old->{anonymousOwner}) . "']]");
    }
}

# first, see if anonymous is turned on
if ($ftp_site->{enabled} && 
    ($ftp_site_new->{anonymous} || 
        ($ftp_site->{anonymous} && $ftp_site_new->{anonymousOwner})))
{
    # make sure anonymous user exists
    if (!(getpwnam($ftpuser))[2])
    {
        my $comment = $i18n->interpolate('[[base-ftp.anonName]]', { 'group' => $group });
    
        $comment = $i18n->encodeString($comment);
        
        # system call to add user
        my $user = {
                    'comment' => $comment,
                    'homedir' => $home_dir,
                    'group' => $group,
                    'shell' => Sauce::Config::bad_shell(),
                    'name' => $ftpuser,
                    'password' => ''
                    };

        if (!(useradd($user))[0])
        {
            $cce->warn("[[base-ftp.cantAddAnonFtpUser,user='$ftpuser']]");
            $errors = 1;
        }
    }

    # make sure the correct directories exist
    if (!(-d "$home_dir/incoming"))
    {
        mkpath("$home_dir/incoming");
    }

    # make sure the ftp group exists or anonymous gets the gid of the group
    # and can really mess things up
    if (!getgrnam('ftp'))
    {
        if (!(groupadd({ 'name' => 'ftp' }))[0])
        {
            $cce->bye('FAIL', '[[base-ftp.cantCreateFtpGroup]]');
            exit(0);
        }
    }

    # set correct permissions and ownership
    Sauce::Util::chmodfile(02775, $home_dir);
    Sauce::Util::chmodfile(02773, "$home_dir/incoming");
    Sauce::Util::chownfile((getpwnam('nobody'))[2], (getgrnam($group))[2], $home_dir);
    Sauce::Util::chownfile((getpwnam('nobody'))[2], (getgrnam($group))[2], "$home_dir/incoming");
}
elsif (exists($ftp_site_new->{anonymous})) 
{
    # turn off anonymous ftp
    Sauce::Util::chmodfile(02770, "$home_dir/incoming");

    # delete the anonymous user
    if (!(userdel(0, $ftpuser))[0])
    {
        # consider this non-fatal
        $cce->warn("[[base-ftp.cantDelAnonFtpUser,user='$ftpuser']]");
    }
}

if ($ftp_site->{enabled} && $ftp_site->{anonymous})
{
    # make sure the quota is correct
    my $quota = $ftp_site->{quota} * Sauce::Config::bytes_per_block();

    $DEBUG && print STDERR "QUOTA args $home_dir ". Quota::getqcarg($home_dir) . ' ' . (getpwnam($ftpuser))[2] . ' ' . "$quota\n";

#ROLLBACK QUOTA
    Quota::setqlim(Quota::getqcarg($home_dir), (getpwnam($ftpuser))[2], $quota,
                    $quota + 1, 0, 0);
}

if ($errors)
{
    $cce->bye('FAIL');
    exit(1);
}
else
{
    $cce->bye('SUCCESS');
    exit(0);
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
