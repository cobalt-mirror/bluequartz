include ../rules/defines.mk

MAKEHEADERS=rules.mk package.mk defines.mk module.mk libs.mk

all dep clean:

install: 
	-mkdir -p $(CCEDIR)/devel
	$(INSTALL) $(INSTALL_HEADERFLAGS) $(MAKEHEADERS) $(CCEDIR)/devel
