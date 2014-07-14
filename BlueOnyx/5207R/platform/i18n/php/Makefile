#
# This is a header to make a dynamic PHP extension for i18n.
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

# check for php-config since this may be on an old system
PHP_BASE = $(shell if [ -x /usr/bin/php-config ]; then \
			echo -n `php-config --prefix`; \
		elif [ -d /usr/local/include/php ]; then \
			echo -n /usr/local; \
		else \
			echo -n /usr; \
		fi)

CCELIBNAME = i18n
CCESHLIBSRC = i18n.c
CCEDEPSRC = $(CCESHLIBSRC)

PHP_INC = $(shell if [ -x /usr/bin/php-config ]; then \
			echo -n `php-config --includes`; \
		else \
			echo -n -I$(PHP_BASE)/include/php -I$(PHP_BASE)/include/php/main \
				-I$(PHP_BASE)/include/php/Zend -I$(PHP_BASE)/include/TSRM; \
		fi)

INC = $(PHP_INC) -I../src/include -I$(CCEINCDIR)
CFLAGS =-Dbool=char -DHAS_BOOL -D_REENTRANT -fPIC $(INC) `glib-config --cflags`
CCESHLIB_LIBS=-L../src -li18n `glib-config --libs`

all: dep shlib

install: i18n.so
	$(INSTALL) -d $(CCEPHPLIBDIR)
	$(INSTALL) -d $(CCEPHPCLASSDIR)
	$(INSTALL) -d $(CCEDIR)/ui/web
	$(INSTALL) -d $(PREFIX)/etc/php.d
	$(INSTALL) $(INSTALL_SHLIBFLAGS) i18n.so $(CCEPHPLIBDIR)
	$(INSTALL) test_i18n.php $(CCEDIR)/ui/web/

local_clean:

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/rules.mk
else
include $(CCEDIR)/devel/rules.mk
endif
