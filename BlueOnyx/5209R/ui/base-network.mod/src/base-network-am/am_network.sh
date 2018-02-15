#!/bin/sh
# test the network state, try to recover if something happens
# normally, this runs fast, in error cases it can take as long as ~15 seconds
#
# Original Author: Tim Hockin

. /usr/sausalito/swatch/statecodes

# we may change this throughout the script
FINAL_RET=$AM_STATE_GREEN

# get network config
. /etc/sysconfig/network

PING="ping -q -c3 -l3"
PING6="ping6 -q -c3 -l3"

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

# IPv4 Gateway:
IPv4=$(/sbin/ip route | awk '/default/ { print $3 }')

# IPv6 Gateway:
IPv6=$(/sbin/ip -6 route | awk '/default/ { print $3 }')

# Determine Gateway:
if [ -f /proc/user_beancounters ];then
    if [ `/sbin/ip addr show |grep inet4 |wc -l` -gt 0 ]; then
        if [ `cat /proc/user_beancounters | grep kmemsize | awk '{print $1}' | cut -d : -f1` -gt 0 ]; then
            # Ping the IP of the master node instead of the Gateway:
            GATEWAY=`ping -t 1 -c 1 1.2.3.4 | grep "exceed\|Unreachable" | cut -d " " -f 2`
        fi
    else
        # Then we try IPv6 instead:
        GATEWAY=`ping6 -t 1 -c 1 2001:4860:4860::8888 | grep "exceed\|Unreachable" | cut -d " " -f 2`

    fi
fi

# Test the gateway
if [[ $GATEWAY =~ .*:.* ]];then
  # IPv6:
  $PING6 $GATEWAY > /dev/null 2>&1
else
  # IPv4
  $PING $GATEWAY > /dev/null 2>&1
fi

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

if [ "$FINAL_RET" = "$AM_STATE_GREEN" ]; then
    echo -ne "[[base-network.amNetworkOK]]"
fi

exit $FINAL_RET
# 
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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
