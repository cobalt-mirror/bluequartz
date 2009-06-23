ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

LOCALES=$(shell dir=etc/skel; \
                if [ -d $$dir ]; then \
                        cd $$dir; tmp='-I CVS'; \
                        locales=`ls $$tmp | egrep '^..$$|^.._'`; \
                        echo $$locales; \
                fi)

# include defines.mk here before checking to see which target make is doing
include $(CCETOPDIR)/devel/defines.mk

SCRIPTS=sbin/user_gid_fix.pl sbin/fix_user_UID_and_GID.pl

# check PREFIX, because if it is defined we are 'make rpm' and CCETOPDIR
# needs to be redefined appropriately
ifdef PREFIX
CCETOPDIR = $(PREFIX)/usr/sausalito
endif

all:

install:
	for locale in $(LOCALES); do \
		$(INSTALL) -d -o root -m 0755 $(PREFIX)/etc/skel/user/$$locale/web; \
		$(INSTALL) -o root etc/skel/$$locale/user-homedir/web/*.html -m 0644 $(PREFIX)/etc/skel/user/$$locale/web/; \
	done
	$(INSTALL) -d -o root -m 0755 $(PREFIX)/usr/sausalito/sbin; \
	$(INSTALL) -o root -m 0755 $(SCRIPTS) $(PREFIX)//usr/sausalito/sbin/; \

rpm:
