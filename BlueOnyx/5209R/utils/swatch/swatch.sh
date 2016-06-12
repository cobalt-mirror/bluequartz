#!/bin/bash

export LANG=en_US
export LC_ALL=en_US.UTF-8
export LINGUAS="en_US ja da_DK de_DE"
exec=/usr/sausalito/sbin/swatch.sh
lockfile=/var/lock/subsys/swatch
FIND=`which find`
XARGS=`which xargs`
TOUCH=`which touch`
REM=`which rm`
CCEDUP=`/usr/sausalito/bin/check_cce.pl`

# Run fix_syslog.sh:
/usr/sausalito/sbin/fix_syslog.sh

if [ "$CCEDUP" != "SUCCESS" ];then
        /usr/sausalito/bin/cced_unstuck.sh >/dev/null 2>&1
        sleep 5
fi

if [ -f $lockfile ] ; then
        $FIND $lockfile -type f -cmin +25 -print | $XARGS $REM >/dev/null 2>&1
        #echo "Swatch cronjob is already running. Delaying execution for now.";
        exit
fi

$TOUCH $lockfile

if [ -f "/tmp/.swatch.lock" ]; then
        $FIND /tmp/.swatch.lock -type f -cmin +25 -print | $XARGS $REM >/dev/null 2>&1
        #echo "Swatch executeable is already running. Delaying execution for now.";
        exit
else
        $TOUCH /tmp/.swatch.lock
        #echo "Running Swatch"
        /usr/sbin/swatch -c /etc/swatch.conf >/dev/null 2>&1
        $REM -f /tmp/.swatch.lock
fi

if [ -f /usr/bin/systemctl ]; then
  if [ ! -f /usr/lib/systemd/system/swatch.service ];then
    cp /usr/sausalito/swatch/swatch.service /usr/lib/systemd/system/swatch.service
    systemctl daemon-reload >/dev/null 2>&1 || :
    systemctl enable swatch.service >/dev/null 2>&1 || :
  fi
fi


# Pause to wait for constructors to stop running  - max of 5 minutes
TIMEOUT=300
WAITFOR=cce_construct
pgrep -f $WAITFOR > /dev/null
while [ $? -eq 0 -a $TIMEOUT -gt 0 ]; do
        sleep 1
        ((TIMEOUT--))
        pgrep -f $WAITFOR > /dev/null
done

# Run hotfix script:
/usr/sausalito/sbin/hotfixes.sh

$REM -f $lockfile
exit

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#         notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#         notice, this list of conditions and the following disclaimer in 
#         the documentation and/or other materials provided with the 
#         distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#         contributors may be used to endorse or promote products derived 
#         from this software without specific prior written permission.
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
