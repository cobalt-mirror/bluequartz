#!/bin/bash
# Author: Brian N. Smith, Michael Stauber 
# Copyright 2006-2007, NuOnce Networks, Inc.  All rights reserved. 
# Copyright 2006-2007, Stauber Multimedia Design  All rights reserved. 
# $Id: yum-checker.sh, v1.0 2007/12/20 9:02:00 Exp $   

/bin/touch /var/log/yum.log
/bin/chmod 644 /var/log/yum.log

if [ -f /tmp/yum.updating ]; then
    find /tmp/yum.updating -type f -cmin +240 -print | xargs rm 
fi

/usr/bin/yum check-update > /tmp/yum.check-update
/bin/chmod 444 /tmp/yum.check-update

exit 0
