#!/bin/sh 
# $Id: am_asp.sh,v 1.1 2001/06/18 23:20:51 will Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# ASP Active Monitor component
# Will DeHaan <null@sun.com>

. /usr/sausalito/swatch/statecodes

# we may change this later
FINAL_RET=$AM_STATE_GREEN

STRING=`(/etc/rc.d/init.d/asp-apache-3000 status | grep "caspctrl: running for")`
if [ -z "$STRING" ]; then
	/etc/rc.d/init.d/asp-apache-3000 restart > /dev/null 2>&1
	sleep 3

	STRING=`(/etc/rc.d/init.d/asp-apache-3000 status | grep "caspctrl: running for")`
	if [ -z "$STRING" ]; then
		echo -ne "[[base-asp.amAspNotRunning]]"
		FINAL_RET=$AM_STATE_RED
	fi
fi

if [ "$FINAL_RET" = "$AM_STATE_GREEN" ]; then
	echo -ne "[[base-asp.amAspStatusOK]]"
fi

exit $FINAL_RET
)

exit $?
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
