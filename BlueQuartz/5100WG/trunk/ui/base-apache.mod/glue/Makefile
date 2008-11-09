# $Id: Makefile 255 2003-10-28 14:53:34Z shibuya $
#
# Makefile to install handlers.  Right now, it's a bit perl-specific,
# but it can be easily generalized.
#
# supported targets:
#		all -- does nothing
#		install -- installs everything
#		install_links -- creates links instead of copies of files (for dbging)
#		rpm -- rolls an rpm for this stuff

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

INSTALL = /usr/bin/install -m 0700 -o root 
PWD = $(shell pwd)

HANDLERSRC = \
	$(shell find handlers -name CVS -prune -or -type f -print) 

debug:
	@echo HANDLERSRC = $(HANDLERSRC) 

install: install_etc 

install_handlers: $(HANDLERSRC)
	for t in $(HANDLERSRC) ; do \
	  	mkdir -p -m 0700 `dirname $(CCEDIR)/$$t` ;\
	  	$(INSTALL) $$t $(CCEDIR)/$$t ;\
		chmod u+x $(CCEDIR)/$$t ;\
	done

install_etc: 
	mkdir -p $(PREFIX)/etc/
	install -m 0600 -o root etc/suexec.conf $(PREFIX)/etc/
install_conf:
	tar -C conf . --exclude CVS -cf - | tar -C $(CCECONFDIR) -xf -

# used during development: installs symlinks instead of code.
install_links:
	make install INSTALL="ln -sf "

rpm:
