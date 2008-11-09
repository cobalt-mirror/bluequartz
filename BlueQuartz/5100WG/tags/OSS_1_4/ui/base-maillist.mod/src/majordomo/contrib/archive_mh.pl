#!/bin/perl

# archive: A hack to use mh to handle the archives
#
# You may redistribute this file, or inlcude it into the offical majordomo
#   package
#
# $Source$
# $Revision: 3 $
# $Date: 2003-07-17 15:19:15 +0000 (Thu, 17 Jul 2003) $
# $Author: will $
# $State$
#
# $Locker$

# set our path explicitly
$ENV{'PATH'} = "/bin:/usr/bin:/usr/ucb";

# Read and execute the .cf file
$cf = $ENV{"MAJORDOMO_CF"} || "/tools/majordomo-1.56/majordomo.cf";
if ($ARGV[0] eq "-C") {
    $cf = $ARGV[1];
    shift(@ARGV); 
    shift(@ARGV); 
}
if (! -r $cf) {
    die("$cf not readable; stopped");
}
require "$cf"; 

# Go to the home directory specified by the .cf file
chdir("$homedir");

exec("/tools/mh-6.8/lib/mh/rcvstore +$filedir/$ARGV[0] -nocreate\n");
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
