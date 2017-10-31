#!/bin/sh

/usr/bin/logger "Start: Running final_constructor.sh"
/usr/sausalito/sbin/bx_runonce.sh
/usr/sausalito/sbin/swatch.sh
/usr/bin/logger "End: Running final_constructor.sh"

exit

