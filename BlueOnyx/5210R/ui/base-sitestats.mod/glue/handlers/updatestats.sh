#!/bin/sh
#
# $Id: updatestats.sh
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
		/usr/local/sbin/split_logs web $DATE < /var/log/httpd/access_log
	;;

	mail)
		/usr/local/sbin/maillog2commonlog.pl sendmail < /var/log/maillog | /usr/local/sbin/split_logs mail $DATE 
	;;

	ftp)
		/usr/local/sbin/ftplog2commonlog < /var/log/xferlog | /usr/local/sbin/split_logs ftp $DATE
	;;

	*)
		$0 net
		$0 web
		$0 mail
		$0 ftp
	;;
esac

exit 0;

# 
# Copyright (c) 2015-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2018 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 