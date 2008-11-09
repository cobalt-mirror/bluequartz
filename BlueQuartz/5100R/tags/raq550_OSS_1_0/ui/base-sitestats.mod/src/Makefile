ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif
 
SCRIPTSDIR=sitestats-scripts

all:

install:
	make -C $(SCRIPTSDIR) install

rpm:
	cp $(SCRIPTSDIR).spec  $(REDHAT_DIR)/SPECS/
	tar --exclude CVS -czf $(REDHAT_DIR)/SOURCES/$(SCRIPTSDIR).tar.gz \
		$(SCRIPTSDIR)
	rpm -ba $(REDHAT_DIR)/SPECS/$(SCRIPTSDIR).spec

