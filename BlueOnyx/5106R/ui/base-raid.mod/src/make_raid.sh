#!/bin/sh
#########################################################################
#                                                                        #
#            Copyright (C) 1997-2000 Cobalt Networks, Inc                #
#                          All rights reserved                           #
#                                                                        #
# Filename: make_raid.sh                                                 #
#                                                                        #
# Author(s): Timothy Stonis <tim@cobalt.com>,                            #
#            Adrian Sun <asun@cobalt.com>                                #
#                                                                        #
# Description: Convert between RAID types                                #
#                                                                        #
##########################################################################

# DEBUG
# set -x

. /etc/rc.d/init.d/functions
. /usr/sausalito/ui/conf/ui.cfg

raid_tab="/etc/raidtab"
md4_entry="/tmp/raidtab.md4"
home_temp="/tmp/home.tar"
raid_status="$statusDir/raidstatus"
target_level=$1
services="crond sendmail httpd postgresql snmpd quota tomcat.init" 
UMOUNT_TIMEOUT=10

function toggle_services {

  for service in $services; do
    if [ -e /etc/rc.d/rc3.d/S*${service} ]; then
      /etc/rc.d/init.d/$service $1
    fi
  done

}

function check_and_tar {

  avail=$(df `dirname $1` | grep "^\/" | awk '{print $4}')
  need=$(du -s /home | awk '{print $1}')

  if [ $need -gt $avail ]; then
    echo "ERROR: Not enough disk space. Exiting"
    update_status setupNoDiskSpace error
    exit 1
  fi

  # Tar up /home
  tar -cPf $1 /home

}

function clean_on_exit {

  rm $home_temp > /dev/null 2>&1
  rm $md4_entry > /dev/null 2>&1
  toggle_services start

}

function unmount_and_raid {

  # figure out filesystem type
  getfsinfo /dev/md4

  for ((i = 0; i < $UMOUNT_TIMEOUT; i++)); do
	echo "umount attempt $i"
	toggle_services stop
	umount /dev/md4
	status=$?
	if [ $status -eq 0 ]; then
		break
	fi
	sleep 1
  done

  if [ $status -ne 0 ]; then
    echo "output of fuser -mv /home"
    echo `/sbin/fuser -mv /home`
    echo "ERROR: Couldn't umount /home. Exiting."
    update_status setupNoUmountHome error
    clean_on_exit
    exit 1
  fi

  # Now stop the raid array
  raidstop /dev/md4

  if [ $? -ne 0 ]; then
    echo "Problem stopping raid. Exiting."
    exit 1
  fi

  # Now make the new type of RAID
  cp $raid_tab /etc/raidtab.bak
  cp ${raid_tab}.new $raid_tab
  mkraid -q --really-force /dev/md4

  # Make a filesystem on it 
  $FS_MKFS /dev/md4

  # Mount the filesystem up with quotas enabled
  mount /home
  if [ $? -ne 0 ]; then
    echo "Problem remounting /home. Exiting."
    exit 1
  fi

  # Put our stuff back 
  (cd /; tar -xPf $home_temp)
 
  # Remove the tar file
  rm $home_temp 

  update_status raidstep2

  /etc/rc.d/init.d/quota start

}

function new_raidtab {

  export current_level target_level
  cat $raid_tab | awk '
    BEGIN { current=ENVIRON["current_level"]
            target=ENVIRON["target_level"]
            hit=0 }
    /^raiddev.*md4.*/,/^device.*/ {
        hit=1 
        if ( $0 ~ /^raid-level/ ) {
          printf( "raid-level\t\t%s\n",target ) 
        } else {
          if ( $0 ~ /^chunk-size/ ) {
            if ( target == 5 ) {
              printf( "chunk-size\t\t64\n");
              printf( "parity-algorithm\tleft-symmetric\n");
            } else {
              printf( "chunk-size\t\t64\n");
            }
           } else {
             if ( $0 !~ /^parity/ ) {
               print
             }
           }
         } 
      }

   {
      if ( hit != 1 ) {
        print
      } 
      hit=0
    }

  ' > /tmp/raidtab.new

  mv /tmp/raidtab.new ${raid_tab}.new    
   

}

# updates status file used by refreshing ui page
# update_status <message> <"done"|"error">
function update_status {
	message="title: [[base-raid.raidstatus]]\nmessage: [[base-raid.configuring]]\nsubmessage: [[base-raid.${1}]]\n"
	if [ "x$2" = "xdone" ]; then
		message="redirectUrl: /base/wizard/raidDone.php\n"
	fi
	if [ "x$2" = "xerror" ]; then
		message="redirectUrl: /base/wizard/raidDone.php?error=$1\n"
	fi
	echo -e "update status file: $message" 
	echo -e "$message" > ${raid_status}
	# do again in case status.php was reading at the time
	/bin/sleep 1
	echo -e "$message" > ${raid_status}
}

if [ X$target_level = X ]; then
  target_level=0
fi

# create the status directory if it hasn't been made yet.
# this should really be handled by the UI layer, but we need
# this in case we need to exit with an error before the UI gets 
# around to creating it. 
if [ ! -d $statusDir ]; then
  mkdir -p $statusDir
  chmod 755 $statusDir
  chown httpd.httpd $statusDir
  touch $raid_status
  chown httpd.httpd $raid_status
fi

update_status raidstep1

cat $raid_tab | sed -n -e "/^raiddev.*md4/,/^raiddev/ p" > $md4_entry
numdisks=$( cat $md4_entry | grep -c "^device.*dev.*" )
current_level=$( cat $md4_entry | grep "^raid-level" | awk '{print $2}')

echo "current is: $current_level"
echo "target is: $target_level"

if [ $current_level = $target_level ]; then
  echo "Same level. Exiting."
  update_status "DONE: Same level, no change" done
  clean_on_exit
  exit 0 
fi

# Handle going from 0 
if [ $current_level = 0 ]; then
  if [ $target_level -ne 5 -a $target_level -ne 1 ]; then
    echo "Can't handle transition to RAID $target_level. Exiting."
    exit 1
  fi

  if [ $target_level = 1 -a $numdisks != 2 ]; then
    echo "Can't transition to RAID 1 with $numdisks disks. Exiting."
    exit 1
  fi

  new_raidtab 
  toggle_services stop
  check_and_tar $home_temp 
  # For some reason, postgres won't die
  killall postmaster
  unmount_and_raid
  update_status raidstep3
  clean_on_exit
  update_status "DONE" done
  exit 0

fi

# Handle going to 0 from 5 and 1 
if [ $target_level = 0 ]; then

  new_raidtab 
  toggle_services stop
  check_and_tar $home_temp 
  killall postmaster
  unmount_and_raid 
  update_status raidstep3
  clean_on_exit
  update_status "DONE" done
  exit 0

else
  echo "Can't go to RAID $target_level from RAID $current_level. Exiting."
  exit 1
fi
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
