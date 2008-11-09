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
	cd ..; tar --exclude '*CVS*' -zcf base-time-src.tar.gz src;
	cp ../base-time-src.tar.gz /usr/src/redhat/SOURCES;
	cp base-time-src.spec /usr/src/redhat/SPECS;
	rpm -ba /usr/src/redhat/SPECS/base-time-src.spec;

clean:
	rm -f $(PROGRAM) $(OBJS) *~
