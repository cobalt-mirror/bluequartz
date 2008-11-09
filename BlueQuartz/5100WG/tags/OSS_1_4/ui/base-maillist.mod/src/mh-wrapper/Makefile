# $Id: Makefile 3 2003-07-17 15:19:15Z will $
#

INSTALL_DIR=/home/mhonarc

all: mh-wrapper

install: mh-wrapper-install

mh-wrapper: mh-wrapper.c
	gcc -O2 -o mh-wrapper mh-wrapper.c

mh-wrapper-install: mh-wrapper
	install -D -g daemon -o mail -m 04770 -s mh-wrapper \
	  $(INSTALL_DIR)/bin/mh-wrapper
	# should test to make sure /usr/adm/sm.bin exists first
	# otherwise automation is a pain
	test -d /usr/adm/sm.bin || mkdir -p /usr/adm/sm.bin
	test -f /usr/adm/sm.bin/mh-wrapper && rm /usr/adm/sm.bin/mh-wrapper \
		|| /bin/true
	ln -s $(INSTALL_DIR)/bin/mh-wrapper /usr/adm/sm.bin/mh-wrapper
