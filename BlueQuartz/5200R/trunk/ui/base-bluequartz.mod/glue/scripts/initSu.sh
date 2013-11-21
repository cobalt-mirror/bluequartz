#!/bin/sh

cat /etc/pam.d/su | sed -e 's|^#auth\t\trequired\tpam_wheel.so use_uid|auth\t\trequired\tpam_wheel.so use_uid|' > /etc/pam.d/su.temp
/bin/mv /etc/pam.d/su.temp /etc/pam.d/su

grep '^SU_WHEEL_ONLY' /etc/login.defs > /dev/null 2>&1
if [ $? = 1 ]; then
  echo 'SU_WHEEL_ONLY	yes' >> /etc/login.defs
fi

