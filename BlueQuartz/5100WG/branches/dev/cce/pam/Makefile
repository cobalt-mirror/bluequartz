#
# Created by Michael Waychison <mwaychison@cobalt.com> 2000/6/30
#

TITLE=pam_cce

DYNAMIC=-DPAM_DYNAMIC
INCLUDEDIR += -I../client/include -I../include/
CFLAGS += -ggdb -Wall `glib-config --cflags` $(INCLUDEDIR)
LD_D=gcc -shared -Xlinker -x -L../client/src/ -lcce
LIBSRC = $(TITLE).c
LIBOBJ = $(TITLE).o
LIBOBJD = $(addprefix dynamic/,$(LIBOBJ))
LIBOBJS = $(addprefix static/,$(LIBOBJ))
SHLIBMODE = 755
INSTALL = install
SECUREDIR = $(PREFIX)/lib/security
MKDIR = mkdir -p
CC = gcc

# for make test
USER=foo
PASS=bar

dynamic/%.o : %.c
	$(CC) $(CFLAGS) $(DYNAMIC) $(CPPFLAGS) $(TARGET_ARCH) -c $< -o $@

static/%.o : %.c
	$(CC) $(CFLAGS) $(STATIC) $(CPPFLAGS) $(TARGET_ARCH) -c $< -o $@


ifdef DYNAMIC
LIBSHARED = $(TITLE).so
endif

ifdef STATIC
LIBSTATIC = lib$(TITLE).a
endif

####################### don't edit below #######################

all: dirs $(LIBSHARED) $(LIBSTATIC) register

dirs: dynamic static

dynamic:
ifdef DYNAMIC
	$(MKDIR) ./dynamic
endif

static:
ifdef STATIC
	$(MKDIR) ./static
endif

register:
ifdef STATIC
	( cd .. ; ./register_static $(TITLE) $(TITLE)/$(LIBSTATIC) )
endif

ifdef DYNAMIC
$(LIBOBJD): $(LIBSRC)

$(LIBSHARED):	$(LIBOBJD)
	$(LD_D) -o $@ $(LIBOBJD)
endif

ifdef STATIC
$(LIBOBJS): $(LIBSRC)

$(LIBSTATIC): $(LIBOBJS)
	$(LD) -r -o $@ $(LIBOBJS)
	ar rcs $@ $^
endif

install: all
	$(MKDIR) $(SECUREDIR) 
ifdef DYNAMIC
	$(INSTALL) -m $(SHLIBMODE) $(LIBSHARED) $(FAKEROOT)$(SECUREDIR)
endif

test: all test_pam_cce
	./test_pam_cce $(USER) $(PASS)

test_pam_cce:
	$(CC) -ggdb -o test_pam_cce test_pam_cce.c -lpam -ldl

remove:
	rm -f $(FAKEROOT)$(SECUREDIR)/$(TITLE).so

clean:
	rm -f $(LIBOBJD) $(LIBOBJS) $(LIBSTATIC) $(LIBSHARED) core *~
	rm -rf dynamic static
	rm -f test_pam_cce

extraclean: clean
	rm -f *.a *.o *.so *.bak dynamic/* static/*

.c.o:	
	$(CC) $(CFLAGS) -c $<

