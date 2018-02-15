#!/bin/bash

if [ -f /proc/user_beancounters ]; then
  /bin/echo "This is an OpenVZ VPS. Network settings may not be changed from inside the VPS."
  exit;
fi

: ${DIALOG=dialog}

IPADDRESS=`ifconfig  | grep 'inet '| grep -v '127.0.0.1' | cut -d: -f2 | awk '{ print $2}'|head -1`
NETMASK=`ifconfig | grep $IPADDRESS | awk '{ print $4}'`
DEFAULTGW=`/sbin/ip route | awk '/default/ { print $3 }'|head -1`
DNSSERVER=`cat /etc/resolv.conf |grep ^nameserver|awk '{ print $2}'|head -1`
IPV6ADDRESS=`/sbin/ip -6 -o addr show |grep eth0|grep -v fe80|awk '{print$4}'| tr '/' ' ' | awk '{print$1}'|head -1`
DEFAULTGWV6=`/sbin/ip -6 route show default|grep -v fe80|awk '{print$3}'|head -1`

function GetIP() {
MSG="Please Enter your IPv4 IP Address\n\n"
exec 3>&1
IPADDRESS="`$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE"  \
  --inputbox "$MSG" 15 70 $IPADDRESS 2>&1 1>&3`"
retval=$?
exec 3>&-
RESET=0
}

function GetNM() {
MSG="Please Enter your Netmask\n\n"
exec 3>&1
NETMASK="`$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE"  \
  --inputbox "$MSG" 15 70 $NETMASK 2>&1 1>&3`"
retval=$?
exec 3>&-
RESET=0
}

function GetGW() {
MSG="Please Enter your Default IPv4 Gateway\n\n"
exec 3>&1
DEFAULTGW="`$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE"  \
  --inputbox "$MSG" 15 70 $DEFAULTGW 2>&1 1>&3`"
retval=$?
exec 3>&-
RESET=0
}

function GetDNS() {
MSG="Please Enter your DNS Server IP\n\n"
exec 3>&1
DNSSERVER="`$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE"  \
  --inputbox "$MSG" 15 70 $DNSSERVER 2>&1 1>&3`"
retval=$?
exec 3>&-
RESET=0
}

function GetIPv6() {
MSG="Please Enter your IPv6 IP Address\n\n"
exec 3>&1
IPV6ADDRESS="`$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE"  \
  --inputbox "$MSG" 15 70 $IPV6ADDRESS 2>&1 1>&3`"
retval=$?
exec 3>&-
RESET=0
}

function GetV6GW() {
MSG="Please Enter your Default IPv6 Gateway\n\n"
exec 3>&1
DEFAULTGWV6="`$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE"  \
  --inputbox "$MSG" 15 70 $DEFAULTGWV6 2>&1 1>&3`"
retval=$?
exec 3>&-
RESET=0
}

CDTITLE="Team BluOnyx Presents - Network Reconfigure"
TITLE="Network Setup Utility"

IPv4=3
IPv6=3

function AskIPv4 {
        MSG="Do you want to configure IPv4?"
        $DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE" \
          --yesno "$MSG" 0 0
        IPv4=$?
}

if [ "$IPv4" == "3" ]; then
        AskIPv4
fi
if [ "$IPv4" == "0" ]; then
  echo "IPv4 setup ..."
  GetIP
  GetNM
  GetGW
  GetDNS
fi
if [ "$IPv4" == "1" ]; then
  echo "Skipping IPv4 setup"
fi

function AskIPv6 {
        MSG="Do you want to configure IPv6?"
        $DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE" \
          --yesno "$MSG" 0 0
        IPv6=$?
}

if [ "$IPv6" == "3" ]; then
        AskIPv6
fi
if [ "$IPv6" == "0" ]; then
  echo "IPv6 setup ..."
  GetIPv6
  GetV6GW
  GetDNS
fi
if [ "$IPv6" == "1" ]; then
  echo "Skipping IPv6 setup"
fi

if [ "$IPv6" == "1" ] && [ "$IPv4" == "1" ];then
  echo "Not configuring network at all."
  exit
fi

function Confirm() {
MSG="
Please Confirm your Network Settings\n\n
IPv4 Address : $IPADDRESS\n
Netmask      : $NETMASK\n
Gateway      : $DEFAULTGW\n
DNS Server   : $DNSSERVER\n
IPv6 Address : $IPV6ADDRESS\n
IPv6 Gateway : $DEFAULTGWV6\n
\n
Accept?"
$DIALOG --nocancel --backtitle "$CDTITLE" --title "$TITLE" \
  --yesno "$MSG" 0 0
NO=$?
}

Confirm
if [ "$NO" == "1" ];then
  echo "Exiting without applying any changes."
  exit
fi

GOTIPv4=0;
if [ "$IPADDRESS" != "" ] && [ "$NETMASK" != "" ] && [ "$DEFAULTGW" != "" ];then
  GOTIPv4=1;
fi
GOTIPv6=0;
if [ "$IPV6ADDRESS" != "" ] && [ "$DEFAULTGWV6" != "" ];then
  GOTIPv6=1;
fi

if [ "$GOTIPv4" == "1" ] || [ "$GOTIPv6" == "1" ]; then

  echo "Applying new Network configuration. Please wait ... "

  # Change the default gateway
  /usr/bin/perl -pi -e "s#GATEWAY=.*#GATEWAY=$DEFAULTGW#" /etc/sysconfig/network

  # Find the Network ID & Broadcast ID
  NETWORKID=`/usr/sausalito/sbin/minicalc.pl id $IPADDRESS $NETMASK`
  BROADCAST=`/usr/sausalito/sbin/minicalc.pl bid $IPADDRESS $NETMASK`

  # Create/Update /etc/udev/rules.d/70-persistent-net.rules:
  if [ ! -f /etc/udev/rules.d/70-persistent-net.rules ]; then
    /usr/sausalito/sbin/write_udev.pl > /etc/udev/rules.d/70-persistent-net.rules
    # Let's get rid of ifcfg-* files for non-ethX interfaces:
    /bin/cp /etc/sysconfig/network-scripts/ifcfg-* /tmp/
    /bin/rm -f /etc/sysconfig/network-scripts/ifcfg-*
    /bin/cp /tmp/ifcfg-lo /etc/sysconfig/network-scripts/
  fi

  # Configure /etc/sysconfig/network:

  /bin/echo "FORWARD_IPV4=false" > /etc/sysconfig/network
  if [ "$GOTIPv4" == "1" ] && [ "$DEFAULTGW" != "" ];then
    /bin/echo "GATEWAY=$DEFAULTGW" >> /etc/sysconfig/network
    /bin/echo "NETWORKING=yes" >> /etc/sysconfig/network
  fi
  /bin/echo "HOSTNAME=localhost.localdomain" >> /etc/sysconfig/network
  if [ "$GOTIPv6" == "1" ] && [ $DEFAULTGWV6 != "" ];then
    /bin/echo "IPV6FORWARDING=yes" >> /etc/sysconfig/network
    /bin/echo "IPV6_AUTOCONF=no" >> /etc/sysconfig/network
    /bin/echo "IPV6_DEFAULTDEV=eth0" >> /etc/sysconfig/network
    /bin/echo "IPV6_DEFAULTGW=$DEFAULTGWV6" >> /etc/sysconfig/network
    /bin/echo "NETWORKING_IPV6=yes" >> /etc/sysconfig/network
    sleep 5
  fi
  /bin/echo "NOZEROCONF=yes" >> /etc/sysconfig/network

  # Configure up the init scripts for eth0 properly.
  /bin/echo "DEVICE=eth0" > /etc/sysconfig/network-scripts/ifcfg-eth0
  /bin/echo "BOOTPROTO=none" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  /bin/echo "ONBOOT=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  /bin/echo "DELAY=0" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  /bin/echo "NM_CONTROLLED=no" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  if [ "$GOTIPv4" == "1" ] && [ "$IPADDRESS" != "" ] && [ "$NETMASK" != "" ];then
    /bin/echo "BROADCAST=$BROADCAST" >> /etc/sysconfig/network-scripts/ifcfg-eth0
    /bin/echo "NETWORK=$NETWORKID" >> /etc/sysconfig/network-scripts/ifcfg-eth0
    /bin/echo "NETMASK=$NETMASK" >> /etc/sysconfig/network-scripts/ifcfg-eth0
    /bin/echo "IPADDR=$IPADDRESS" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  fi
  /bin/echo "USERCTL=no" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  /bin/echo "ARPCHECK=no" >> /etc/sysconfig/network-scripts/ifcfg-eth0
  if [ "$GOTIPv6" == "1" ] && [ "$IPV6ADDRESS" != "" ] && [ "$DEFAULTGWV6" != "" ];then
    /bin/echo "IPV6INIT=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
    /bin/echo "IPV6ADDR=$IPV6ADDRESS" >> /etc/sysconfig/network-scripts/ifcfg-eth0
    /bin/echo "IPV6_DEFAULTGW=$DEFAULTGWV6" >> /etc/sysconfig/network-scripts/ifcfg-eth0
    sleep 5
  fi

  # Restart Network:
  echo "Restarting Network ... "
  systemctl disable NetworkManager.service &>/dev/null || :
  systemctl stop NetworkManager.service --no-block &>/dev/null || :
  rm -f /etc/systemd/system/multi-user.target.wants/NetworkManager.service
  rm -f /etc/systemd/system/dbus-org.freedesktop.NetworkManager.service
  rm -f /etc/systemd/system/dbus-org.freedesktop.nm-dispatcher.service
  systemctl enable network.service &>/dev/null || :
  systemctl restart network.service &>/dev/null || :

  # Convince Sausalito that we are using eth0 & have proper IP info already
  /usr/sausalito/constructor/base/network/10_fix_ifup.pl
  /usr/sausalito/constructor/base/network/30_addNetwork.pl
  /usr/sausalito/constructor/base/network/40_addGateway.pl
  /usr/sausalito/constructor/base/network/checkInterfaceConfigure.pl
  /usr/sausalito/constructor/base/network/updateMac.pl

  # restart daemons
  echo "Restarting Daemons ... "
  # Remove all non-ethX style interfaces except for lo:
  ls -k1 /etc/sysconfig/network-scripts/ifcfg-*|grep -v ifcfg-lo|grep -v ifcfg-eth|xargs rm -f

  # Turn off the EL7 firewall:
  systemctl stop firewalld.service --no-block &>/dev/null || :
  systemctl disable firewalld.service &>/dev/null || :
  rm -f /etc/systemd/system/dbus-org.fedoraproject.FirewallD1.service
  rm -f /etc/systemd/system/basic.target.wants/firewalld.service

  # Full constructor run, just to be sure:
  /usr/sausalito/sbin/cced.init restart

fi

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
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
