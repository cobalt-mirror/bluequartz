#!/bin/sh
# $Id: am_dns.sh
# Bind test

# Load return codes
. /usr/sausalito/swatch/statecodes

# Test whether we're intentionally disabled
# /sbin/chkconfig --list named | grep '3:on' > /dev/null
# if [ $? -gt 0 ]; then
# 	exit $AM_STATE_NOINFO
# fi

# Check if the 'named-chroot' Systemd Unit-File is present:
if [ -f "/usr/lib/systemd/system/named-chroot.service" ];then
	FOUND=`cat /usr/lib/systemd/system/named-chroot.service | grep "^ExecStartPre=/bin/bash"|wc -l`
	if [ $FOUND = "1" ];then
		# Run the fixDNS.pl constructor. That will make sure that the Systemd
		# Unit-File of named-chroot will be fixed if needed:
		if [ -f "/usr/sausalito/constructor/base/dns/fixDNS.pl" ];then
			/usr/sausalito/constructor/base/dns/fixDNS.pl
		fi
	fi
fi

# Test localhost lookup
/usr/bin/host -W 2 127.0.0.1 127.0.0.1 | grep '1.0.0.127.in-addr.arpa.' >/dev/null

if [ $? -gt 0 ]; then

	# Merciful restart attempt:
	if [ -f /usr/bin/systemctl ]; then
		# We're on EL7 with split named/named-chroot Unit files:
		/sbin/service named-chroot restart > /dev/null 2>&1
	else
		/sbin/service named restart > /dev/null 2>&1
	fi

	# Re-test
	/usr/bin/host -W 2 127.0.0.1 127.0.0.1 | grep '1.0.0.127.in-addr.arpa.' >/dev/null

	if [ $? -gt 0 ]; then
		echo -n "$redMsg"
		exit $AM_STATE_RED;
	fi
fi
	
echo -n "$greenMsg"
exit $AM_STATE_GREEN;

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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
