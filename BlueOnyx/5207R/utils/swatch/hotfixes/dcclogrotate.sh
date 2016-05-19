#! /bin/sh
if [ -f /usr/libexec/dcc/cron-dccd ];then
	/usr/libexec/dcc/cron-dccd >/dev/null 2>&1
fi
exit 0
