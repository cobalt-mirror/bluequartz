#!/bin/bash
# Author: Brian N. Smith, Michael Stauber 
# Copyright 2006-2007, NuOnce Networks, Inc.  All rights reserved. 
# Copyright 2006-2007, Stauber Multimedia Design  All rights reserved. 
# $Id: yum-update.sh, v1.0 2007/12/20 9:02:00 Exp $   

/bin/touch /tmp/yum.updating
/bin/rm -f /tmp/yum.check-update
/usr/bin/yum -y update > /tmp/yum.update

if [ -f /etc/yumgui.conf ]; then
  source /etc/yumgui.conf
  EMAILRECIPIENT=$MAILTO
  /bin/cat /tmp/yum.update | /bin/mail -s "`/bin/hostname` Yum Update output for `/bin/date +\%m`-`/bin/date +\%d`-`/bin/date +\%y`" $EMAILRECIPIENT
fi

/usr/bin/yum check-update > /tmp/yum.check-update
/bin/rm -f /tmp/yum.updating

/bin/chmod 644 /var/log/yum.log

exit 0;
