#!/bin/sh
#
# $Id: am_arkeia.sh,v 1.2.2.1 2002/03/30 01:18:45 uzi Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Description:
#	Determine if the Knox Arkeia client daemon is running.
# This script should only be executed if the daemon is expected
# to be running, so we try to restart it if the daemon is stopped
# or dead.
#

. /usr/sausalito/swatch/statecodes
. /etc/rc.d/init.d/functions

RESTART=/etc/rc.d/init.d/cobalt-arkeia

STATE=`status nlservd 2>&1`
case "$STATE" in
	*stopped* | *dead*)
		#
		# The service has stopped.  Stop it again to clear out any
		# pid or lock files, then try and restart it.
		#
		$RESTART stop
		$RESTART start
		RETVAL=$?
		if [ $RETVAL -ne 0 ]; then
			#
			# The service did not restart!  Red means very bad,
			# not good as it would in some cultures.
			#
			echo -ne "[[knox-arkeia.am_stopped]]"
			exit $AM_STATE_RED
		fi

		# The service was restarted.  This is good.
		echo -ne "[[knox-arkeia.am_restarted]]"
		exit $AM_STATE_GREEN
		;;

	*running*)
		echo -ne "[[knox-arkeia.am_running]]"
		exit $AM_STATE_GREEN
		;;

	*)
		#
		# Unknown status!  Call it a warning, though it is a bug
		# in this script if this ever happens.
		#
		echo -ne "[[knox-arkeia.am_unknown]]"
		exit $AM_STATE_YELLOW
		;;
esac

# We should never get to here, but just in case 
exit $AM_STATE_GREEN
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
