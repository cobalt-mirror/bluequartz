#!/bin/bash
# Author: Brian N. Smith, Michael Stauber 
# $Id: yum-update.sh

/bin/touch /var/log/yum.log
/bin/chmod 644 /var/log/yum.log
/bin/touch /tmp/yum.updating
/bin/rm -f /tmp/yum.check-update
/usr/bin/yum -y update > /tmp/yum.update

if [ -f /etc/yumgui.conf ]; then
  source /etc/yumgui.conf
  EMAILRECIPIENT=$MAILTO
  /bin/cat /tmp/yum.update | /bin/sed 's/\r//' | /bin/mail -s "`/bin/hostname` Yum Update output for `/bin/date +\%m`-`/bin/date +\%d`-`/bin/date +\%y`" $EMAILRECIPIENT
fi

/usr/bin/yum --exclude=base-maillist* --exclude=majordomo -y groupinstall blueonyx

/usr/bin/yum check-update > /tmp/yum.check-update
/bin/rm -f /tmp/yum.updating

# Various permission fixes:
/bin/chmod 644 /var/log/yum.log
/bin/chmod 777 /var/lib/php/session

exit 0;

# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 