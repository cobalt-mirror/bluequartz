ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CFLAGS=-O3 -fomit-frame-pointer
PROGRAM=epochdate
OBJS=epochdate.o
SRCS=$(OBJS:.o=.c)

$(PROGRAM): $(OBJS)
	$(CC) $(LDFLAGS) $< -o $(PROGRAM)

install: $(PROGRAM)
	-mkdir -p $(CCESBINDIR)
	$(INSTALL) $(INSTALL_SBINFLAGS) setTime $(CCESBINDIR)
	$(INSTALL) $(INSTALL_SBINFLAGS) epochdate $(CCESBINDIR)

rpm:
	cd ..; tar --exclude '.svn' -zcf base-time-src.tar.gz src;
	cp ../base-time-src.tar.gz $(PACKAGE_DIR)/SOURCES;
	cp base-time-src.spec $(PACKAGE_DIR)/SPECS;
	$(RPMBUILD) -ba $(PACKAGE_DIR)/SPECS/base-time-src.spec;

clean:
	rm -f $(PROGRAM) $(OBJS) *~
