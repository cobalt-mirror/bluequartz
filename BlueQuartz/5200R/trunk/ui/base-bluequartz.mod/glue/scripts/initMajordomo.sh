#!/bin/sh

if [ ! -f /usr/lib/majordomo/sequencer ]; then
  /bin/cp -p /usr/lib/majordomo/Tools/sequencer /usr/lib/majordomo
fi
