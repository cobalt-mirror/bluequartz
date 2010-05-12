# Makefile

include ../../dynamic.mk

CFLAGS=-Wall
LIBS=-ldb -lpthread
TARGET=pam_abl_gui
OBJ=log.o config.o rule.o pam_abl.o
INSTDIR=$(PREFIX)/usr/sausalito/bin

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
