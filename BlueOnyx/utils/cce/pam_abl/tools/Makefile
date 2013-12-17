# Makefile

include ../../dynamic.mk

CFLAGS=-Wall
LIBS=-ldb -lpthread
TARGET=pam_abl
OBJ=log.o config.o rule.o pam_abl.o
INSTDIR=$(PREFIX)/usr/bin

all : $(TARGET)

$(TARGET) : $(OBJ)
	cc $(LIBS) -o $@ $^

install : $(TARGET)
	mkdir -p $(INSTDIR)
	install --mode=755 --strip $(TARGET) $(INSTDIR)

clean :
	rm -f $(TARGET) $(OBJ)

depend :
	cc -MM *.c > deps

include deps
