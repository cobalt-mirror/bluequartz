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
	cp $(SCRIPTSDIR).spec  $(PACKAGE_DIR)/SPECS/
	tar --exclude '.svn' -czf $(PACKAGE_DIR)/SOURCES/$(SCRIPTSDIR).tar.gz \
		$(SCRIPTSDIR)
	$(RPMBUILD) -ba $(PACKAGE_DIR)/SPECS/$(SCRIPTSDIR).spec

