#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: check_deluser.pl,v 1.1.2.1 2002/02/14 23:21:33 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc., All rights reserved.
# This handler checks preconditions for deleting a user.

use CCE;
use Base::HomeDir qw(homedir_create_user_link homedir_get_group_dir);

my $cce = new CCE;
$cce->connectfd();

# retreive user object data:
my $old = $cce->event_old();

# check for override flag
if ($old->{noFileCheck}) {
	$cce->bye('SUCCESS');
	exit(0);
}

my ($user_link, $link_target) = homedir_create_user_link($old->{name}, 
                                    $old->{site}, $old->{volume});
my $group_dir = homedir_get_group_dir($old->{site}, $old->{volume});

$link_target =~ s/^\.\.//;
$link_target = $group_dir . $link_target;

# If there are files outside of the user's home directory that the user
# owns, then don't allow the delete.  If they put a file in the site's
# web or ftp directory, there is a good chance that the admin will want
# to keep those files. This forces the admin to decide what to do instead
# of just erasing the files and hoping that was the desired action.
my $result = system("find $group_dir -user $old->{name} | grep -v $user_link | grep -v $link_target");

if ($result != 256) {
	$cce->warn("[[base-user.cleanupOldFiles]]");
	$cce->bye("FAIL");
	exit(0);
}

$cce->bye("SUCCESS");
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
