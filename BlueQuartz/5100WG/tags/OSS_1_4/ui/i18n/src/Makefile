ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

include ../version.mk

CCELIBNAME = libi18n
CCESHLIBMAJOR = $(V_MAJ)
CCESHLIBMINOR = $(V_MIN)

DEFDEBUG = -Wall -ggdb $(DEBUG) #-pedantic
LDSHAREDFLAGS = `glib-config --libs`
DEFS = -DVERSION="\"$(VERSION)\""
LIBS = -L. -L$(CCELIBDIR) -L$(CCETOPDIR)/lib -li18n -ldebug `glib-config --libs`
INCLUDES = -I. -I./include -I$(CCEINCDIR) -I/usr/share/gettext/intl
CFLAGS = -O2 $(DEFS) $(INCLUDES) `glib-config --cflags` $(DEFDEBUG)
LDCONFIG = /sbin/ldconfig

I18NTEST = i18ntest
CCETESTBINS = $(I18NTEST)

BINS = i18n_get i18n_translate
BINSRCS = i18n_cmndline.c i18n_translate.c 
PERLSCRIPTS = i18nmail.pl

CCELIBSRC = i18n.c encoding.c i18n_vars.c interpolate.c \
	i18n_negotiate.c i18n_helpers.c
CCESHLIBSRC = $(CCELIBSRC)
CCEDEPSRC = $(CCELIBSRC) $(BINSRCS)
CCEHEADERS = include/i18n.h

all: include/cce dep lib shlib bin

bin: $(BINS)

i18n_translate: i18n_translate.o libi18n.a
	$(CC) $(CFLAGS) -o $@ -static $< $(LIB) $(LIBS)
	strip $@

i18n_get: i18n_cmndline.o libi18n.a
	$(CC) $(CFLAGS) -o $@ -static $< $(LIB) $(LIBS)
	strip $@

$(I18NTEST): test/getstring.c libi18n.a
	$(CC) $(CFLAGS) -DDEBUG -o $(I18NTEST) $^ $(LIBS)

install_bins: $(BINS)
	mkdir -p $(CCEDIR)/bin
	install -m 0755 $^ $(CCEDIR)/bin
	install -m 0755 $(PERLSCRIPTS) $(CCEDIR)/bin

install: install_lib install_shlib install_headers install_bins
	ldconfig

include/cce:
	-ln -s . include/cce

#test1: test1.o lib
#	$(CC) $(CFLAGS) -DDEBUG -o test1 -static test1.o $(LIB) $(LIBS)

test2: test2.o lib
	$(CC) $(CFLAGS) -DDEBUG  -o $@ -static $< $(LIB) $(LIBS)

test3: test3.o lib
	$(CC) $(CFLAGS) -DDEBUG  -o $@ -static $< $(LIB) $(LIBS)

test: test2 test_locale/test_i18n.mo
	./test2

.PHONY: test_locale
test_locale: test_locale/test_i18n.mo
	install -m644 test_locale/test_i18n.mo /usr/share/locale/en/LC_MESSAGES

test_locale/test_i18n.mo: test_locale/test_i18n.po
	msgfmt -e $< -o $@

debug_all: 
	make clean
	make all DEBUG="-DDEBUG"

local_clean:
	rm -f *.o
	rm -f $(BINS)
	rm -f include/cce


ifdef CCETOPDIR
include $(CCETOPDIR)/devel/rules.mk
else
include $(CCEDIR)/devel/rules.mk
endif
