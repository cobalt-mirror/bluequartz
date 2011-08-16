include ../rules/defines.mk

SCRIPTS=spec.tmpl rpmdefs.tmpl

all dep clean:

install: 
	-mkdir -p $(CCEDIR)/devel/templates
	$(INSTALL) $(INSTALL_HEADERFLAGS) $(SCRIPTS) $(CCEDIR)/devel/templates
