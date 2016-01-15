#!/bin/bash
# $Id: yum-update.sh

/bin/touch /var/log/yum.log
/bin/chmod 644 /var/log/yum.log
/bin/touch /tmp/yum.updating
/bin/rm -f /tmp/yum.check-update
/usr/bin/yum clean all
/usr/bin/yum -y update > /tmp/yum.update

if [ -f /etc/yumgui.conf ]; then
  source /etc/yumgui.conf
  EMAILRECIPIENT=$MAILTO
  /bin/cat /tmp/yum.update | /bin/sed 's/\r//' | /bin/mail -s "`/bin/hostname` Yum Update output for `/bin/date +\%m`-`/bin/date +\%d`-`/bin/date +\%y`" $EMAILRECIPIENT
fi

/usr/bin/yum --exclude=base-maillist* --exclude=majordomo --exclude=mailman --exclude base-mailman* -y groupinstall blueonyx >/dev/null 2>&1

/usr/bin/yum check-update > /tmp/yum.check-update
/bin/rm -f /tmp/yum.updating

# Various permission fixes:
/bin/chmod 644 /var/log/yum.log
/bin/chmod 777 /var/lib/php/session

exit 0;

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2006 Brian N. Smith, NuOnce Networks, Inc.
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
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