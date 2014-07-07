#!/bin/bash
#
# BlueOnyx sosreport interface
#
#

REPORTONLINE=1

#
#
#
#
#

SOSVERSION=`rpm -qi sos | grep ^Version | cut -d ":" -f 2 | cut -d " " -f 2`
SOSMAJORVERSION=`echo $SOSVERSION | cut -d "." -f 1`
MODEL=`cat /etc/build |cut -d " " -f5|cut -d "R" -f1`

#
#
# Create our directory, and cleanup previous sessions
#
#

if [ ! -e /usr/sausalito/ui/web/debug/ ]
then
  mkdir /usr/sausalito/ui/web/debug
else
  tmpwatch 96 /usr/sausalito/ui/web/debug
fi

#
# Make sure we are running on a blueonyx server
#

if [ ! -e /usr/sausalito/ui/web/ ]
then
  echo "Sausalito directory structure error - Cannot find output directory"
  exit 1
fi

#
# Before we start, create bx.py as our plugin. No point proceeding without it!!!!
#

if [ ! -e /usr/lib/python*/site-packages/sos/plugins/bx.py ]
then
  cd /usr/lib/python*/site-packages/sos/plugins/
  #echo Creating bx.py in `pwd`


#####
cat <<'EOF' >bx.py
##
## BlueOnyx plugin for sosreport
##

import sos.plugintools
import os

class bx(sos.plugintools.PluginBase):
    """bx related information
    """

    def checkenabled(self):
        if os.path.exists("/usr/sausalito/sbin/cced"):
            return True
        return False

    def setup(self):
        self.addCopySpec("/home/.pkg_install_tmp/.swupdate/package.log")
        self.collectExtOutput("/usr/sausalito/sbin/pkg_list.pl >/tmp/pkglist.txt; cat /tmp/pkglist.txt; rm -rf /tmp/pkglist.txt")
        self.collectExtOutput("/usr/sbin/cmuExport -c -d /tmp/cmudump > /dev/null; cat /tmp/cmudump/cmu.xml; rm -rf /tmp/cmudump")
        return
EOF
#####

fi

#
# Before we start, create admserv.py as our plugin. No point proceeding without it!!!!
#

if [ ! -e /usr/lib/python*/site-packages/sos/plugins/admserv.py ]
then
  cd /usr/lib/python*/site-packages/sos/plugins/
  #echo Creating admserv.py in `pwd`


#####
cat <<'EOF' >admserv.py
##
## BlueOnyx AdmServ plugin for sosreport
##

import sos.plugintools

class admserv(sos.plugintools.PluginBase):
    """AdmServ related information
    """
    optionList = [("log", "gathers all admserv logs", "slow", False)]

    def checkenabled(self):
        if self.isInstalled("base-admserv-capstone"):
            return True
        return False
    
    def setup(self):
        self.addCopySpec("/etc/admserv/conf/admserv.conf")
        self.addCopySpec("/etc/admserv/conf.d/*.conf")
        self.addForbiddenPath("/etc/admserv/conf/password.conf")
        if self.getOption("log"):
            self.addCopySpec("/var/log/admserv/*")
        return

EOF
#####
fi

SOID=`echo "find System" | /usr/sausalito/bin/cceclient | grep ^104 | cut -d " " -f 3`
SERIALNUMBER=`echo get $SOID | /usr/sausalito/bin/cceclient | grep ^102.*serialNumber | cut -d '"' -f 2`

if [ -z "$SERIALNUMBER" ]
then
  echo "Unable to find serial number"
  exit 1
fi

if [ -z "$MODEL" ]
then
  echo "Unable to find model number"
  exit 1
fi

rpm -ql sos | less | grep plugins | sed -e "s/.*plugins.//" | sed -e "s/\.py.*//" | uniq > /tmp/sosoptions.txt
SOSREPORTOPTIONS=
if [ -e /proc/user_beancounters ]
then
  grep "0:" /proc/user_beancounters
  grep -q "0:" /proc/user_beancounters
  if [ $? -gt 0 ]
  then
    for X in filesys gfs2 devicemapper
    do
      grep -q $X /tmp/sosoptions.txt
      if [ $? -eq 0 ]
      then
        SOSREPORTOPTIONS="$SOSREPORTOPTIONS -n $X"
      fi
    done
    #SOSREPORTOPTIONS="-n filesys -n gfs2 -n devicemapper"
  fi
fi

if [ $SOSMAJORVERSION -eq 2 ]
then
  SOSREPORTOPTIONS="$SOSREPORTOPTIONS --report"
fi

echo "Running sosreport now. This will take a while. Do not break out of the program."

echo "$SOSREPORTOPTIONS"

## Flushing any old SOS-Report's from CODB:
/usr/sausalito/sbin/sosreport-helper.pl -flush=yes

sosreport -av --batch --ticket-number=$MODEL --name=$SERIALNUMBER $SOSREPORTOPTIONS # 2> /dev/null >/dev/null
ls /tmp/sosreport-$SERIALNUMBER.$MODEL-* 2>/dev/null > /dev/null
if [ ! $? -eq 0 ]
then
  echo "sosreport failed to find /tmp/sosreport-$SERIALNUMBER.$MODEL-*"
  exit 1
fi


rm -rf /usr/sausalito/ui/web/debug/sosreport*$SERIALNUMBER.$MODEL-* 
rm -rf /usr/sausalito/ui/web/debug/$SERIALNUMBER*$MODEL* 

for X in `ls -F /usr/sausalito/ui/web/debug/ | grep "/$" | cut -d "/" -f 1`
do
  rm -rf /usr/sausalito/ui/web/debug/$X
done

mv /tmp/sosreport-$SERIALNUMBER.$MODEL-* /usr/sausalito/ui/web/debug/

if [ $REPORTONLINE -eq 1 ]
then
  ls /usr/sausalito/ui/web/debug/*.bz2 2>/dev/null > /dev/null
  if [ $? -eq 0 ]
  then
    tar xfj /usr/sausalito/ui/web/debug/sosreport-$SERIALNUMBER.$MODEL*.tar.bz2 -C /usr/sausalito/ui/web/debug/
  else
    tar xfJ /usr/sausalito/ui/web/debug/sosreport-$SERIALNUMBER.$MODEL*.tar.xz -C /usr/sausalito/ui/web/debug/
  fi

  for X in `find /usr/sausalito/ui/web/debug/ -type d`
  do
    chmod o+x $X
    chmod -R o+r $X
  done
  #chmod -R o+r /usr/sausalito/ui/web/debug/$SERIALNUMBER.$MODEL*
fi

for X in `ls -F /usr/sausalito/ui/web/debug/ | grep -v "/$" | cut -d "/" -f 1 | grep -v ^html`
do
  echo "<a href=\"$X\">$X</a><br>"
done >> /usr/sausalito/ui/web/debug/$SERIALNUMBER-$MODEL.html

for Y in `ls -F /usr/sausalito/ui/web/debug/ | grep "/$" | cut -d "/" -f 1`
do
  echo "<a href=\"$Y/sos_reports/sosreport.html\">$Y/sos_reports/sosreport.html</a><br>"
done >> /usr/sausalito/ui/web/debug/$SERIALNUMBER-$MODEL.html

for Z in `ifconfig  | grep "inet addr" | cut -d ":" -f 2 | cut -d " " -f 1 | grep -v 127.0.0.1 | head -1`
do
  echo "http://$Z:444/debug/$SERIALNUMBER-$MODEL.html"
done
# | mail -s "Blueonyx Debug Submission $MODEL" mstauber@blueonyx.it

# Updating CODB with the new SOS-Report:
/usr/sausalito/sbin/sosreport-helper.pl -report=yes -internal=$Y -external=/debug/$SERIALNUMBER-$MODEL.html

# Fix permissions:
/bin/chmod -R 755 /usr/sausalito/ui/web/debug

rm yum_debug_dump*

# 
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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