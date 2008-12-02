LOCALEBASE=/usr/share/locale
LOCALEDIR=$(PREFIX)$(LOCALEBASE)
REENCODE=/usr/sausalito/bin/reencode.pl

LANGS=$(shell dir=.; \
                if [ -d $$dir ]; then \
                        cd $$dir; tmp='-I CVS'; \
                        for i in $(XLOCALEPAT); do \
                                tmp="$$tmp -I $$i"; \
                        done; \
                        locales=`ls $$tmp | egrep '^..$$|^.._'`; \
                        for dir in $$locales; do \
                                if [ -f $$dir/swatch.po ]; then \
                                        list="$$dir $$list"; \
                                fi; \
                        done; \
                        echo $$list; \
                fi)

.PHONY: $(LANGS)

SRCFILE = swatch

all: $(LANGS)

$(LANGS): 
	for a in $(LANGS); do \
		msgfmt $$a/$(SRCFILE).po -o $$a/$(SRCFILE).mo; \
	done

install: all
	for a in $(LANGS); do \
		mkdir $(LOCALEDIR)/$$a; \
		mkdir $(LOCALEDIR)/$$a/LC_MESSAGES; \
		install -m 644 -o root -g root $$a/$(SRCFILE).mo \
			$(LOCALEDIR)/$$a/LC_MESSAGES/$(SRCFILE).mo; \
	done
	
uninstall:
	for a in $(LANGS); do \
		rm -f $(LOCALEDIR)/$$a/LC_MESSAGES/$(SRCFILE).mo; \
	done
	
clean:
	find . -name \*.mo | xargs rm -f


