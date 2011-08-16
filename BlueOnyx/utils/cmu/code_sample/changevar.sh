#!/bin/bash

# user
perl -pi -e 's#usrFullName#fullname#g' *.pm
perl -pi -e 's#usrAltName#altname#g' *.pm
perl -pi -e 's#usrQuota#quota#g' *.pm
perl -pi -e 's#usrEmailAliases#aliases#g' *.pm
perl -pi -e 's#usrSuspend#suspend#g' *.pm
perl -pi -e 's#usrVacation#vacation#g' *.pm
perl -pi -e 's#usrApop#apop#g' *.pm
perl -pi -e 's#usrShell#shell#g' *.pm
perl -pi -e 's#usrAdmin#admin#g' *.pm
perl -pi -e 's#usrFpx#fpx#g' *.pm
perl -pi -e 's#usrForward#forward#g' *.pm

# vsite
perl -pi -e 's#vsHostName#hostname#g' *.pm
perl -pi -e 's#vsDomain#domain#g' *.pm
perl -pi -e 's#vsIpAddr#ipaddr#g' *.pm
perl -pi -e 's#vsQuota#quota#g' *.pm
perl -pi -e 's#vsCasp#casp#g' *.pm
perl -pi -e 's#vsPhp#php#g' *.pm
perl -pi -e 's#vsMaxUsers#maxusers#g' *.pm
perl -pi -e 's#vsCgi#cgi#g' *.pm
perl -pi -e 's#vsFpx#fpx#g' *.pm
perl -pi -e 's#vsSsi#ssi#g' *.pm
perl -pi -e 's#vsSsl#ssl#g' *.pm
perl -pi -e 's#vsShell#shell#g' *.pm
perl -pi -e 's#vsApop#apop#g' *.pm
perl -pi -e 's#vsSuspend#suspend#g' *.pm
perl -pi -e 's#vsFtp#ftp#g' *.pm
perl -pi -e 's#vsFtpUsers#ftpusers#g' *.pm
perl -pi -e 's#vsFtpQuota#ftpquota#g' *.pm
perl -pi -e 's#vsWebDomain#webAliases#g' *.pm
perl -pi -e 's#vsEmailDomain#mailAliases#g' *.pm
perl -pi -e 's#vsVolume#volume#g' *.pm
perl -pi -e 's#vsApop#apop#g' *.pm
perl -pi -e 's#vsNewGroup#newGroup#g' *.pm

# Mailing lists
perl -pi -e 's#mlSub#subscription#g' *.pm
perl -pi -e 's#mlRestrict#restrict#g' *.pm
perl -pi -e 's#mlIntRecips#intRecips#g' *.pm
perl -pi -e 's#mlExtRecips#extRecips#g' *.pm
perl -pi -e 's#mlPassword#password#g' *.pm
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
