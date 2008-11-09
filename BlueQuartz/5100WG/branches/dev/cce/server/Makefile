DIRMK_SRCS = sessionmgr.c scalar.c
DIRMK_DIRS = conf ed cscp codb classconf
DIRMK_CLEAN = myclean
DIRMK_ALL = cced

include ../dynamic.mk
include $(CCE_TOPDIR)/dirmake.mk

DEFS += -DCCE_VERSION=\"$(CCE_VERSION)\"
LIBS += -lcrypt -ldl -lpam

SERVER_SRCS = main.c smd.c
SERVER_OBJS = $(SERVER_SRCS:.c=.o)
DAEMON = cced

$(DAEMON): server.o ../common/common.o $(SERVER_OBJS) 
	$(CC) -o $@ $^ $(LIBS)

test:
	$(MAKE) -C test test
	for a in $(DIRMK_DIRS); do $(MAKE) -C $$a test || exit 1; done

myclean:
	$(RM) $(SERVER_OBJS) $(DAEMON)

install: all
	mkdir -p $(CCE_SBINDIR)
	$(INSTALL_SBIN) $(DAEMON) $(CCE_SBINDIR)
	mkdir -p $(CCE_SCHEMADIR)
	$(INSTALL_FILE) basetypes.schema $(CCE_SCHEMADIR)
