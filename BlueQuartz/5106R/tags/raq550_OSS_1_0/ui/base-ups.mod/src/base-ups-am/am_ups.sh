#!/bin/bash
# Active Monitor Script for UPS
#
# Author: Joshua Uziel
# Sun Microsystems, Inc. 2001.  All rights reserved.
# $Id: am_ups.sh,v 1.14.2.1 2002/02/16 05:11:06 uzi Exp $

# Get the current configuration info and the statecodes
. /etc/sysconfig/ups
. /usr/sausalito/swatch/statecodes

UPSC="/usr/bin/upsc"

# Set defaults
if [ -z $batt_yellow ]; then
	batt_yellow="80"
fi
if [ -z $batt_red ]; then
	batt_red="50"
fi
FINAL_RET=$AM_STATE_GREEN

# First we get the battery percentage line.
BATTLINE=`$UPSC $HOST | grep BATTPCT`
if [ "$?" != "0" ]; then
	# Attempt to restart ups stuff if we can't connect.
	/etc/rc.d/init.d/ups restart > /dev/null 2>&1

	# If it fails again, die.
	BATTLINE=`$UPSC $HOST | grep BATTPCT`
	if [ "$?" != "0" ]; then
		echo -ne "[[base-ups.amCantConnect]]"
		exit $AM_STATE_RED
	fi
fi

# Report status based on the battery percentage left.
BATTPCT=`echo $BATTLINE | sed -e 's/.*: //' -e 's/\..*//' -e 's/^0*//'`
if [ "$BATTPCT" -lt $batt_yellow ]; then
	msg="[[base-ups.amBatteryLow,level=\"$batt_yellow\"]]  "
	FINAL_RET=$AM_STATE_YELLOW
fi
if [ "$BATTPCT" -lt $batt_red ]; then
	msg="[[base-ups.amBatteryLow,level=\"$batt_red\"]]  "
	FINAL_RET=$AM_STATE_RED
fi
if [ "$BATTPCT" = "" ]; then		# No battery level read
	msg="[[base-ups.amCantConnect]]  "
	FINAL_RET=$AM_STATE_RED
fi

# Get the status of the UPS... if OB (On Battery), then red alert.
STATUS=`$UPSC $HOST | grep STATUS | sed 's/.*: //'`
if [ "$STATUS" = "OB" ]; then
	echo -ne "[[base-ups.amOnBattery]]  "
	FINAL_RET=$AM_STATE_RED
fi


if [ $FINAL_RET -eq $AM_STATE_GREEN ]; then
	msg="[[base-ups.amAllOK]]  "
fi

echo -ne $msg
exit $FINAL_RET
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
