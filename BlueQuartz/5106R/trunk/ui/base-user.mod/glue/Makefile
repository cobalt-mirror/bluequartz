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
	$(INSTALL) -d -o root -m 0755 $(PREFIX)/usr/lib/php4; \
	$(INSTALL) -o root lib/php4/crack.so -m 0755 $(PREFIX)/usr/lib/php4/; \

	$(INSTALL) -d -o root -m 0755 $(PREFIX)/usr/share/dict; \
	$(INSTALL) -o root share/dict/cracklib-words -m 0644 $(PREFIX)/usr/share/dict/; \
	$(INSTALL) -o root share/dict/pw_dict.hwm -m 0644 $(PREFIX)/usr/share/dict/; \
	$(INSTALL) -o root share/dict/pw_dict.pwd -m 0644 $(PREFIX)/usr/share/dict/; \
	$(INSTALL) -o root share/dict/pw_dict.pwi -m 0644 $(PREFIX)/usr/share/dict/; \

rpm:

