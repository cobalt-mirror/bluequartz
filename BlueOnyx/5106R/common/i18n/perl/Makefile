ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

PERLSRCS = I18n.pm SendEmail.pm JConv.pl I18nMail.pm

all:
	@echo Nothing to make for all.
install:
	mkdir -p $(CCEPERLDIR) || /bin/true
	for t in $(PERLSRCS) ; do install -o root -g root -m 755 $$t $(CCEPERLDIR) ; done

clean:
	@echo Nothing to do for clean.

