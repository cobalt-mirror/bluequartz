#
# This is a header to make a dunamic PHP extension for cce.
#

# FIXME: To install this you will need to manually copy dl/phpdl.h from your
# PHP source tree into /usr/local/include/php/

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CCEMODULE = cce-libcce

all:
	make -C src

clean:
	make -C src clean

depend:

install:
	make -C src install
	make -C class install
