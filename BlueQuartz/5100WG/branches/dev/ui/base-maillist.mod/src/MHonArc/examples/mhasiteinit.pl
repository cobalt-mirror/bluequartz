#!/usr/local/bin/perl
##---------------------------------------------------------------------------##
##  File:
##      @(#) mhasiteinit.pl 1.1 99/08/11 23:21:53
##  Description:
##      Site-specific initialization code for MHonArc.  If used, it
##	should be place in the MHonArc library directory as specified
##	during initialization.  The expressions in this file are
##	executed everytime an archive is opened for processing.
##
##	Note, it is recommended to use a default resource file when
##	at all possible.  The file should only be used when a
##	default resource file is not sufficient.
##---------------------------------------------------------------------------##

## Set package to something other than "mhonarc" to protect ourselves
## from unintentially screwing with MHonArc's internals

package mhonarc_site_init;

## Uncomment the following to set the default LOCKMETHOD to flock.
## Flock is better than the directory file method if flock() is supported
## by your system.  If using NFS mounted filesystems, make sure flock()
## under Perl works reliable over NFS.  See the LOCKMETHOD resource
## page of the documentation for more information.

#&mhonarc::set_lock_mode(&mhonarc::MHA_LOCK_MODE_DIR);

##---------------------------------------------------------------------------##
## Make sure to return a true value for require().
1;
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
