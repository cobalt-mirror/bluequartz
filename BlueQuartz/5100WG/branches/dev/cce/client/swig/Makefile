#
# Makefile for swig module creation.
# May later do perl API creation as well as python.
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

include /usr/lib/swig_lib/Makefile

SWIG=swig
SWIGOPT=-v -I../include/ -shadow
TARGET=cce
INTERFACE=cce.i

INCLUDE= -g \
	-I../include/ \
	-I../../cscp/include/ \
	-I../../codb/include/ \
	-I../../conf/include/ \
	-I../../scalar/include/ \
	-I../../../devel-tools/libdebug/include/ \
	-I../../../i18n/src/include/ \
	`glib-config --cflags` 

LIBS= \
	-lcce -L../src/
SRCS=
TARGET=cce
INTERFACE=cce.i

all:: python

clean:
	rm -f *_wrap* *.o mypython code *.so *.sl *.pm

