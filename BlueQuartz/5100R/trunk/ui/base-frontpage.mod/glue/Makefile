include /usr/sausalito/devel/defines.mk

INSTALLDIR=$(PREFIX)/usr/local/bin

all:

install:
	@/bin/mkdir -p $(INSTALLDIR)
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) perm-script/fix_frontpage_perms.pl \
		$(INSTALLDIR)
