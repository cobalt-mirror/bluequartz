#!/bin/sh
#
# $Id: updatestats.sh,v 1.6 2001/12/14 03:56:55 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Updates network/daemon stats to the present day
#

YEAR=`date +%Y`
MONTH=`date +%m | sed "s/^0//"`
DAY=`date +%d | sed "s/^0//"`
DATE="$YEAR/$MONTH/$DAY/"

cp /etc/analog.cfg.tmpl /etc/analog.cfg

case "$1" in 
	net)
		/etc/cron.hourly/log_traffic >/dev/null 2>&1
		/usr/local/sbin/split_logs net $DATE < /var/log/ipacct
	;;

	web)
		/usr/local/sbin/split_logs web $DATE < /var/log/httpd/access
	;;

	mail)
		/usr/local/sbin/maillog2commonlog.pl sendmail \
		< /var/log/maillog | /usr/local/sbin/split_logs mail $DATE 
	;;

	ftp)
		/usr/local/sbin/ftplog2commonlog < /var/log/xferlog \
		| /usr/local/sbin/split_logs ftp $DATE
	;;

	*)
		$0 net
		$0 web
		$0 mail
		$0 ftp
	;;
esac

exit 0;

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
