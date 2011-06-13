#!/bin/sh

/usr/bin/newaliases 
/etc/init.d/sendmail condrestart 

echo "BYE SUCCESS"
exit 0
