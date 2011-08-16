#!/bin/bash
#
# $Id: remove_passwd_lock.sh, v 1.0.0.1 Thu 15 Oct 2009 06:42:01 AM EDT mstauber Exp $
# Copyright 2006-2009 Team BlueOnyx. All rights reserved.
#
# Script which removes stale /etc/passwd.lock files older than 1 minute.

if [ -f /etc/passwd.lock ]; then
    find /etc/passwd.lock -type f -cmin +1 -print | xargs rm
fi

exit 0
