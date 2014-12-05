#!/bin/sh

/usr/bin/newaliases 
/sbin/service sendmail condrestart 

echo "BYE SUCCESS"
exit 0
