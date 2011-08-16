#!/bin/bash

/sbin/pidof saslauthd >/dev/null
if [ $? == 0 ]; then
  /bin/echo "is running..."
fi
