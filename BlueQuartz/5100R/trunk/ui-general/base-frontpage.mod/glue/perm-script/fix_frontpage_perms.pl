#!/usr/bin/perl
# $Id: fix_frontpage_perms.pl,v 1.1.2.1 2002/04/09 23:27:12 pbaltz Exp $
# Copyright 2002, Sun Microsystems, Inc.
#
# Frontpage permissions script:
# Find users with FrontPage Server Extensions enabled and
# set world readability on all web content
#
# Not using CCE so we can run as efficiently as possible.
# This script will consume considerable disk buffer ram
# for servers with thousands of users.
#
my $find = '/usr/bin/find';
my $fpxd; # the user's home directory
while ($fpxd = (getpwent())[7])
{
        next unless (-e $fpxd . '/web/_vti_cnf/index.html');

        # scan directories for known fpx incorrect permissions
        # and make them 02775
        system("$find $fpxd/web -type d -perm +100 -exec chmod 02775 \"{}\" \";\"");

        # scan user web files and make them 0664 iff they're the known bad state
        system("$find $fpxd/web -type f -perm 640 -exec chmod 664 \"{}\" \";\"");
}
exit 0;
################ End ################
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
