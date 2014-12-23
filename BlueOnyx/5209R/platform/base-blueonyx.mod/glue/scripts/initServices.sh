#!/bin/sh

# Disable some services that do not need to be on
services="smartd autofs irqbalance netfs microcode_ctl mdchk kudzu iscsid iscsi sysstat ip6tables auditd kdump lldpad fcoe atd messagebus NetworkManager lldpad fcoe cups netfs portreserve firewalld"
for service in $services; do
  /sbin/chkconfig $service off >/dev/null 2>&1
  /sbin/chkconfig --del $service >/dev/null 2>&1
  /usr/bin/systemctl disable $service.service > /dev/null 2>&1
done

# Remount /tmp to be non-executable!
/usr/bin/perl -pi -e "if (/\/tmp/) { s/defaults/noexec,nosuid,rw/ }" /etc/fstab
/bin/mount -o remount /tmp >/dev/null 2>&1

# Add "httpd" to /etc/passwd & /etc/shadow & /etc/group for backwards compatibility
/bin/cat /etc/passwd | /bin/grep apache | /bin/sed -e "s/apache/httpd/" >> /etc/passwd
/bin/cat /etc/shadow | /bin/grep apache | /bin/sed -e "s/apache/httpd/" >> /etc/shadow
/bin/cat /etc/group | /bin/grep apache | /bin/sed -e "s/apache/httpd/" >> /etc/group

# Fix all "tmp" directories to point to /tmp
/bin/rm -Rf /var/tmp >/dev/null 2>&1
/bin/rm -Rf /home/tmp >/dev/null 2>&1
/bin/ln -s /tmp /var/tmp >/dev/null 2>&1
/bin/ln -s /tmp /home/tmp >/dev/null 2>&1

# Add some aliases, and fix the ones on the box
/bin/echo "alias rm=\"rm -f\"" >> /etc/profile
/bin/echo "alias lsd=\"ls -ld */\"" >> /etc/profile
/bin/echo "alias pico=\"pico -w\"" >> /etc/profile
/bin/echo "# Source global definitions" > /root/.bashrc
/bin/echo "if [ -f /etc/bashrc ]; then" >> /root/.bashrc
/bin/echo "        . /etc/bashrc" >> /root/.bashrc
/bin/echo "fi" >> /root/.bashrc

# Tell people how to reconfigure network via the CLI
/bin/echo "/bin/echo \"\"" >> /root/.bashrc
/bin/echo "/bin/echo \"To change your network settings from the command line, run\"" >> /root/.bashrc
/bin/echo "/bin/echo \"the command /root/network_settings.sh\"" >> /root/.bashrc
/bin/echo "/bin/echo \"\"" >> /root/.bashrc
/bin/echo "/bin/echo \"To remove this notice, edit /root/.bashrc\"" >> /root/.bashrc
/bin/echo "/bin/echo \"\"" >> /root/.bashrc

# Change MAIL environment variable
/usr/bin/perl -pi -e 's/MAIL=.*/MAIL=\"\$HOME\/mbox\"/' /etc/profile

# Fix a small networking problem.  If you don't enable this, you get route for 169.254/16 network
echo "NOZEROCONF=yes" >> /etc/sysconfig/network

# Turn off IPV6:
/bin/echo "alias net-pf-10 off" >> /etc/modprobe.d/net-pf-10.conf

# Import CentOS7 keys:
if [ -f /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7 ]; then
	/bin/rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7
	/bin/rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-Debug-7
	/bin/rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-Testing-7
fi

# Allow logins via the serial interface for root
/bin/echo "ttyS0" >> /etc/securetty

# Make new pkg install directory, if it doesn't exist!
if [ ! -e /home/.pkg_install_tmp ]; then mkdir -p /home/.pkg_install_tmp; fi

# Allow locate to run:
if [ -f /etc/updatedb.conf ]; then
	/usr/bin/perl -pi -e "s/DAILY_UPDATE=no/DAILY_UPDATE=yes/" /etc/updatedb.conf
fi

# Lets make the crontab file more like the Cobalt use to be!
/bin/mkdir -p /etc/cron.half-hourly
/bin/mkdir -p /etc/cron.quarter-hourly
/bin/mkdir -p /etc/cron.quarter-daily
echo "04,34 * * * * root run-parts /etc/cron.half-hourly" >> /etc/crontab
echo "03,18,33,48 * * * * root run-parts /etc/cron.quarter-hourly" >> /etc/crontab
echo "05 0,6,12,18 * * * root run-parts /etc/cron.quarter-daily" >> /etc/crontab

## Logrotate stuff:

## First, create "new" tmp directory
/bin/mkdir -p /home/.tmp
/bin/chmod 755 /home/.tmp

## Second, determine if they applied the patch manually.
/bin/cat /etc/cron.daily/logrotate | grep "export TMPDIR=/home/.tmp" >/dev/null 2>&1
if [ $? == 1 ]; then
  ## Doesn't look like they made changes.  So, lets make it
  /bin/echo "Apply Logrotate Fix"
  /bin/sed -i -e '/\/usr\/sbin\/logrotate/i export TMPDIR=/home/.tmp\n' \
    -e '/exit 0/i \\nunset TMPDIR\n' /etc/cron.daily/logrotate
else
  echo "Logrotate Fix already applied"
fi

## Fix /etc/ld.so.conf:
LIB=/usr/sausalito/lib
/bin/cp /etc/ld.so.conf /etc/ld.so.conf.bak
/bin/egrep "^$LIB[   ]*$" /etc/ld.so.conf > /dev/null || /bin/echo $LIB >> /etc/ld.so.conf
/sbin/ldconfig

## Enable all needed services:
onservices="cced.init httpd admserv xinetd sendmail mariadb named-chroot network saslauthd"
for service in $services; do
  /sbin/chkconfig $onservices on > /dev/null 2>&1
  /usr/bin/systemctl enable $service.service > /dev/null 2>&1
done

## Restart all network services:
# Ping target: Gatway:
#IRX=`ip r | grep default | cut -d ' ' -f 3`
# Ping Target: Google DNS:
IRX="8.8.8.8"
NETWORK=`ping -q -w 1 -c 1 $IRX > /dev/null && echo ok || echo error`
if [ "$NETWORK" == "ok" ]; then
  #echo "Network OK";
  hupservices="cced.init httpd admserv xinetd sendmail mariadb named-chroot network saslauthd"
else
  #echo "Network NOT OK";
  hupservices="cced.init"
fi

for service in $services; do
  /sbin/service $hupservices restart > /dev/null 2>&1
done

# Create a file in /tmp to show us that we did run:
touch /tmp/initServices.sh.hasrun
