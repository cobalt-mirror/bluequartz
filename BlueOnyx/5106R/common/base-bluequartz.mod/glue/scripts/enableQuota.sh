#!/bin/sh

tmpfile='/etc/fstab.tmp'

grep '/home' /etc/fstab > /dev/null 2>&1
if [ $? -eq 0 ];
then
  dir='/home'
else
  dir='/'
fi

rm -f $tmpfile
cat /etc/fstab | while read line
do
  mntpnt=`echo $line | awk '{ print $2 }'`
  if [ "$mntpnt" = "$dir" ]
  then
    for qtype in grpquota usrquota
    do
      if ! (echo "$line" | grep -q -e "$qtype")
      then
        line=`echo "$line" | sed -e "s|defaults|defaults,$qtype|"`
      fi
    done
  fi
  echo "$line" >> $tmpfile
done
mv /etc/fstab /etc/fstab.bak
mv $tmpfile /etc/fstab
/bin/mount -o remount $dir > /dev/null 2>&1
/sbin/quotacheck -cugm $dir >/dev/null 2>&1
/sbin/quotaon -ug $dir > /dev/null 2>&1
