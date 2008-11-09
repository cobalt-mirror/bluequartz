# This Makefile is for the MHonArc software package.

CHMOD		= /bin/chmod
RM		= /bin/rm
PREFIX		= /home/mhonarc
PRGS		= mhonarc mha-dbrecover mha-dbedit mha-decode
PERL		= perl
INSTALLPRG	= install.me

.PHONY		= default install install-ask pod2man test clean

all:

default: pod2man
	$(CHMOD) a+x $(PRGS)
	$(CHMOD) -R a+r,a+X .

install:
	$(PERL) $(INSTALLPRG) -prefix=$(PREFIX) -batch    

install-ask:
	$(PERL) $(INSTALLPRG)    

pod2man:
	test -d man || mkdir man
	@for i in $(PRGS) ; \
	    do( \
		echo "Manifying $$i ..."; \
		pod2man $$i > man/$$i.1; \
	    ); \
	done

test:
	@echo "No tests"

clean:
	@echo "Nothing to clean"

