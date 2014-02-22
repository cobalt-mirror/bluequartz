#!/bin/bash

/bin/ps axf|/bin/grep "/usr/bin/perl /usr/sbin/poprelayd"|/bin/grep -v grep > /dev/null
RETVAL=$?
if [ $RETVAL == 0 ]; then
  		/bin/echo "is running..."
fi
