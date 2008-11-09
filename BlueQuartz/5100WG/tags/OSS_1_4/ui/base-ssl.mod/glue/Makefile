# $Id: Makefile 3 2003-07-17 15:19:15Z will $
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
	$(shell find handlers -name \*.pl -type f -print) \
	$(shell find handlers -name \*.pm -type f -print)

debug:
	@echo HANDLERSRC = $(HANDLERSRC) 

install: install_handlers install_conf

install_handlers: $(HANDLERSRC)
	for t in $(HANDLERSRC) ; do \
	  	mkdir -p -m 0700 `dirname $(CCEDIR)/$$t` ;\
	  	$(INSTALL) $$t $(CCEDIR)/$$t ;\
		chmod u+x $(CCEDIR)/$$t ;\
	done

install_etc: 
	tar -C etc . --exclude CVS -cf - | tar -C /etc -xf -

install_initd:
	tar -C init.d . --exclude CVS -cf - | tar -C /etc/rc.d/init.d -xf -

install_conf:
	tar -C conf . --exclude CVS -cf - | tar -C $(CCECONFDIR) -xf -

# used during development: installs symlinks instead of code.
install_links:
	make install INSTALL="ln -sf "
