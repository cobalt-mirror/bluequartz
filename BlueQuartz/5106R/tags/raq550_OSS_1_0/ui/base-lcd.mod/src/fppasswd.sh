#!/bin/sh

DOMAIN=base-lcd
PASSFILE=/etc/cobalt/.fppasswd
LOCKFILE=/etc/cobalt/.LCK..cobtpanel

. /etc/rc.d/init.d/functions

if [ ! -e $PASSFILE ]; then
  # If we don't have a password... just use enter
  echo 190 > $PASSFILE 
  chmod 600 $PASSFILE
fi

function read_button {

  BUTTON=0
  while [ $BUTTON = 0 ]; do
    BUTTON=`/sbin/readbutton; echo $?`
  done
  debounce
  return $BUTTON
}

function debounce {

  /bin/usleep 200000

}

function get_sequence {
  BUT=0
  PASSWD=""
  STARS=""

  /sbin/lcd-write -s "$(getmsg $1)" "$STARS" 
  /bin/sleep 1 
  while [ $BUT != 190 ]; do 
    /sbin/lcd-write -s "$(getmsg $1)" "$STARS" 
    read_button
    BUT=$?
    PASSWD=$PASSWD" $BUT"
    STARS=$STARS*
  done

  echo $PASSWD

}

function flash_error {

  count=0
  while [ $count -lt 5 ]; do
    /sbin/lcd-write -s "$(getmsg $1)" "$(getmsg $2)"
    /bin/usleep 500000
    /sbin/lcd-write -s "" ""
    /bin/usleep 200000
    count=$(($count+1))
  done

}

function new_passwd {

  PASSWD1=`get_sequence enter_sequence`
  PASSWD2=`get_sequence sequence_again`

  if [ "$PASSWD1" != "$PASSWD2" ]; then
    flash_error sequence_nomatch1 sequence_nomatch2
    exit 1
  fi

  if [ ! -e $PASSFILE ]; then
    touch $PASSFILE
  fi
  chmod 600 $PASSFILE
  echo "$PASSWD1" > $PASSFILE

  /sbin/lcd-write -s "$(getmsg sequence_set1)" "$(getmsg sequence_set2)"
  /bin/sleep 2 
}

function unlock_panel {
  
    rm $LOCKFILE
    (cd /etc/lcd.d/10main.m/35PANEL.m/; mv 10UNLOCK_PANEL.s 10LOCK_PANEL.s)
    (cd /etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s; mv 10unlock 10lock)
    /sbin/lcd-write -s "$(getmsg unlock1)" "$(getmsg unlock2)"
    /bin/sleep 2 
    exit 0
}

function check_passwd {
 
  PASSWD1=`get_sequence enter_sequence`
  PASSWD2=`cat $PASSFILE`

  if [ "$PASSWD1" = "$PASSWD2" ]; then
    # /sbin/lcd-write -s "$(getmsg unlock1)" "$(getmsg unlock2)"
    # /bin/sleep 2
    exit 0
  else
    flash_error nounlock1 nounlock2
    exit 1
  fi
}

function lock_panel {
  PASSWD1=`get_sequence enter_sequence`
  PASSWD2=`cat $PASSFILE`

  if [ "$PASSWD1" = "$PASSWD2" ]; then
    touch $LOCKFILE
    # Update LCD string...
    (cd /etc/lcd.d/10main.m/35PANEL.m/; mv 10LOCK_PANEL.s 10UNLOCK_PANEL.s)
    (cd /etc/lcd.d/10main.m/35PANEL.m/10UNLOCK_PANEL.s; mv 10lock 10unlock)
    /sbin/lcd-write -s "$(getmsg locked1)" "$(getmsg locked2)"
    /bin/sleep 2 
    exit 0
  else
    flash_error nounlock1 nounlock2 
    exit 1
  fi
}

while getopts "unlc" opt; do

  case $opt in 
    u) # Unlock the panel 
       unlock_panel 
       exit 0 
       ;;
    l) # Lock the panel
       lock_panel
       exit 0
       ;;
    n) # New front panel passwd
       new_passwd
       exit 0
       ;;
    c) # Check passwd
       check_passwd
       ;;
  esac

done

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
