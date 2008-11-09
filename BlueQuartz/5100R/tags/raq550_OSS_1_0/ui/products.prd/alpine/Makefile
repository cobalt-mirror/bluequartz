ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CCERELEASESPEC=*.tmpl

checkout:
	@echo -n "enter build directory: ";  \
	read BUILDDIR; \
	echo "checking out product modules..."; \
	echo "================================"; \
	$(CCEDIR)/bin/checkout_product.pl --product=alpine --build-dir=$$BUILDDIR

clean rpm all install:
	@echo -n "enter build directory: ";  \
	read DIR; \
	DIR=`echo $$DIR | sed -e s'/;//g'`; \
	DIR=`eval echo $$DIR`; \
	cd $$DIR; \
	for dir in *.mod; do \
		(cd $$dir; make $(MAKECMDGOALS)); \
	done 

release_spec:
	@$(CCEDIR)/bin/make_release_spec --process-all $(CCERELEASESPEC)
