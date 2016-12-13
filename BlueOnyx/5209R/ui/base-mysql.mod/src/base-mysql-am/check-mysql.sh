#!/bin/bash

/sbin/pidof mysqld >/dev/null
if [ $? == 0 ]; then
  /bin/echo "is running..."
fi
