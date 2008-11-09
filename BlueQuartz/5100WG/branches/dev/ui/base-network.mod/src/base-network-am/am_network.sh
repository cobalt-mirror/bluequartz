#!/bin/sh
# test the network state, try to recover if something happens
# normally, this runs fast, in error cases it can take as long as ~15 seconds
#
# Tim Hockin

. /usr/sausalito/swatch/statecodes

# we may change this throughout the script
FINAL_RET=$AM_STATE_GREEN

# get network config
. /etc/sysconfig/network

PING="ping -q -c3 -l3"

# cop out if we're shut-out of network connectivity via ppp window
HOUR=`date +%H`
if [ -f /etc/ppp/nodial/$HOUR ]; then
        exit $FINAL_RET
fi

# make sure any interfaces that should be up are up
for a in /etc/sysconfig/network-scripts/ifcfg-*[^~]; do
	. $a
	if [ -z "$ONBOOT" -o "$ONBOOT" = "no" -o "$ONBOOT" = "false" ]; then
		continue
	fi

        if [ -z "$IPADDR" ]; then
                continue
        fi
	
	# see that it is in ifconfig
	ifconfig | grep $DEVICE >/dev/null 2>&1
	RET=$?
	if [ "$RET" != "0" ]; then
		# hrrm, not in ifconfig - try to ifup it
		/etc/sysconfig/network-scripts/ifup $DEVICE >/dev/null 2>&1
		ifconfig | grep $DEVICE >/dev/null 2>&1
		RET=$?
		if [ "$RET" != "0" ]; then
			echo -ne "[[base-network.amIfaceIsDown,iface=$DEVICE]]"
			FINAL_RET=$AM_STATE_RED
			continue
		fi
	fi

	# ping it
	$PING $IPADDR > /dev/null 2>&1
	RET=$?
	if [ "$RET" != "0" ]; then
		# try to recover the interface
		/etc/sysconfig/network-scripts/ifdown $DEVICE >/dev/null 2>&1
		/etc/sysconfig/network-scripts/ifup $DEVICE >/dev/null 2>&1
		# try again
		$PING $IPADDR > /dev/null 2>&1
		RET=$?
		if [ "$RET" != "0" ]; then
			echo -ne "[[base-network.amIfaceIsDown,iface=$DEVICE]]"
			FINAL_RET=$AM_STATE_RED
			continue
		fi
	fi
done
		
# test the gateway
if [ -n $GATEWAY -a -n "`/sbin/route -n | grep "^0.0.0.0" 2>/dev/null`" ]; then
	$PING $GATEWAY > /dev/null 2>&1
	RET=$?
	if [ "$RET" != "0" ]; then
		# try again
		$PING $GATEWAY > /dev/null 2>&1
		RET=$?
		if [ "$RET" != "0" ]; then
			echo -ne "[[base-network.amGatewayIsUnreachable]]"
			if [ "$FINAL_RET" != "$AM_STATE_RED" ]; then
				FINAL_RET=$AM_STATE_YELLOW	
			fi
		fi
	fi
fi

if [ "$FINAL_RET" = "$AM_STATE_GREEN" ]; then
	echo -ne "[[base-network.amNetworkOK]]"
fi

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
