#!/bin/sh 
# test the state of the mounted filesystems
#
# Tim Hockin

. /usr/sausalito/swatch/statecodes

msg=""

function addmsg {
	[ $# = 1 ] || return -1

	if [ "$msg" ]; then
		msg="$msg\n"
	fi
	msg="$msg$1"
}

# set defaults
if [ -z $red_free ]; then
	red_free="100"
fi
if [ -z $red_pcnt ]; then
	red_pcnt="95"
fi
if [ -z $yellow_free ]; then
	yellow_free="125"
fi
if [ -z $yellow_pcnt ]; then
	yellow_pcnt="90"
fi

# we may change this later
FINAL_RET=$AM_STATE_GREEN

# acquire data
df | egrep "^/dev/" | (
while read DEV SIZE USED AVAIL PCNT MNT; do
	# check that it is r/w
	grep "^$DEV" /proc/mounts | awk '$4 ~ /^rw/ { exit 1 }'
	if [ "$?" = 0 ]; then
		continue;
	fi
		
	# skip special devices and filesystems
	if [ "$DEV" = "/dev/pts" ]; then        
		continue;
	fi
	if [ "$DEV" = "/dev/shm" ]; then        
		continue;
	fi
	if [ "$MNT" = "/boot" ]; then
		continue;
	fi
	if [ "$MNT" = "/diag" ]; then
		continue;
	fi
	if [ "$MNT" = "/mnt/floppy" ]; then
		continue;
	fi
	if [ "$MNT" = "/mnt/cdrom" ]; then
		continue;
	fi

	# use bc (not bc -l) so we don't get a floating point 
	AVAIL=`echo "$AVAIL / 1024" | bc`
	PCNT=`echo "$USED * 100 / $SIZE" | bc`

	# decide
	if [ $PCNT -gt $red_pcnt -a $AVAIL -lt $red_free ]; then
		addmsg "[[base-disk.amDiskWarning,fs=$MNT,pcnt=$PCNT,free=$AVAIL]]"
		FINAL_RET=$AM_STATE_RED
		continue
	fi

	if [ $PCNT -gt $yellow_pcnt -a $AVAIL -lt $yellow_free ]; then
		addmsg "[[base-disk.amDiskWarning,fs=$MNT,pcnt=$PCNT,free=$AVAIL]]"
		if [ "$FINAL_RET" != "$AM_STATE_RED" ]; then
			FINAL_RET=$AM_STATE_YELLOW
		fi
	fi
done

if [ "$FINAL_RET" = "$AM_STATE_GREEN" ]; then
	echo -ne "[[base-disk.amDiskOk]]"
else
	echo -ne "$msg"
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
