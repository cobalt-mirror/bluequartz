ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

install:
	-mkdir -p $(CCEPHPCLASSDIR)
	$(INSTALL) $(INSTALL_LIBFLAGS) CceClient.php $(CCEPHPCLASSDIR)
	$(INSTALL) $(INSTALL_LIBFLAGS) CceError.php $(CCEPHPCLASSDIR)
