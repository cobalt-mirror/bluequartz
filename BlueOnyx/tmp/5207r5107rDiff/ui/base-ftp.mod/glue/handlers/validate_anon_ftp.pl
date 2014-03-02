#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: validate_anon_ftp.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# make sure all the necessary attributes are filled in if anonymous
# ftp is enabled

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $ftp_site = $cce->event_object();

# if anonymous is enabled, certain attributes must have values
# only worry about the things needed in this handler, which are
# anonymousOwner and quota
my $errors = 0;
if ($ftp_site->{anonymous})
{
    if (!$ftp_site->{anonymousOwner})
    {
        $cce->baddata($cce->event_oid(), 'anonymousOwner', '[[base-ftp.anonymousOwnerRequired]]');
        $errors = 1;
    }
    if (!$ftp_site->{quota})
    {
        $cce->baddata($cce->event_oid(), 'quota', '[[base-ftp.quotaRequired]]');
        $errors = 1;
    }
    if (!$ftp_site->{maxConnections})
    {
        $cce->baddata($cce->event_oid(), 'maxConnections', 
                        '[[base-ftp.maxConnectionsRequired]]');
        $errors = 1;
    }

    if (!$ftp_site->{anonBasedir})
    {
        $cce->warn('[[base-ftp.anonBasedirRequired]]');
        $errors = 1;
    }

    # check for errors and bail if any
    if ($errors)
    {
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