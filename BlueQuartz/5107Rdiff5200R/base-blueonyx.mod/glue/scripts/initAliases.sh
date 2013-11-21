#!/bin/sh

grep '^root:' /etc/aliases > /dev/null 2>&1
if [ $? = 1 ]; then
  echo 'root:		admin' >> /etc/aliases
fi

