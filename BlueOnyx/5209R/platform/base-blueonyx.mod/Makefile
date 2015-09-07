# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=blueonyx

SUBVER=BX27
BUILD=$(shell date --rfc-3339=date | sed s/-//g)$(SUBVER)

VERSION=$(shell if [ `uname -m` = "x86_64" ]; then  echo -n "5209R"; else  echo -n "5209R"; fi)
RELEASE=4.$(BUILD)

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch cyrus-sasl sendmail-cf m4 sendmail cronie crontabs perl perl-Switch perl-CGI sed dialog sausalito-cce-server sausalito-cce-client quota perl-Quota net-tools selinux-policy which rsyslog logrotate zip patch unzip bzip2 elfutils rsync xml-common libtar deltarpm diffutils fuse-sshfs
REQUIRES_GLUE=base-email-capstone base-time-capstone base-user-capstone at dovecot php base-admserv-capstone base-support-capstone base-network-capstone whois traceroute

include /usr/sausalito/devel/module.mk
