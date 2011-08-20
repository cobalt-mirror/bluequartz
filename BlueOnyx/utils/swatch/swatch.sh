#!/bin/bash
export LANG=en_US
export LC_ALL=en_US.UTF-8
export LINGUAS="en_US ja da_DK de_DE"
if [ -f "/tmp/.swatch.lock" ]; then
        /bin/find /tmp/.swatch.lock -type f -cmin +25 -print | /usr/bin/xargs /bin/rm >/dev/null 2>&1
        killall -9 /usr/sbin/swatch >/dev/null 2>&1
        /etc/init.d/cced.init restart >/dev/null 2>&1
else
        /bin/touch /tmp/.swatch.lock
        /usr/sbin/swatch -c /etc/swatch.conf
        /bin/rm -f /tmp/.swatch.lock
fi


