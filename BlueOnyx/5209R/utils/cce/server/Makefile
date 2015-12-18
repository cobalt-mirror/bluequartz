# $Id: Makefile 1137 2008-06-05 02:31:33Z mstauber $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for CCE Daemon

include ../dynamic.mk

DEFS += -DCCE_VERSION=\"$(CCE_VERSION)\"
LIBS += -lcrypt -ldl -lmemcached #-ldebug 
INCLUDES += -I./include

SRCS = src/main.c src/smd.c
OBJS = $(patsubst %.c,%.o,$(SRCS))

DAEMON = cced
CLIENT = cceclient

#SACPAM=$(shell if [ `uname -m` = "x86_64" ]; then  echo -n "system-auth-cce.pam.64-bit"; else  echo -n "system-auth-cce.pam"; fi)
SACPAM=$(shell if [ `uname -m` = "x86_64" ]; then if [ `cat /etc/build |grep 5209R|wc -l` = "1" ]; then echo -n "system-auth-cce.pam.5209R"; else echo -n "system-auth-cce.pam.64-bit"; fi else echo -n "system-auth-cce.pam"; fi)

all: $(DAEMON) $(CLIENT)
	
$(DAEMON): $(OBJS) ../libcce_common.a
	$(CC) $(CFLAGS) -o $@ $(OBJS) $(LIBS) 

$(CLIENT): src/testcli.c ../libcce_common.a
	$(CC) $(CFLAGS) -o $@ $< $(LIBS)

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_CCED"

depend: .depend
.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

test: all
	./cced >/dev/null 2>&1 &
	./$(CLIENT) /tmp/cce < test/test_input > tmp.out
	egrep "^[12349]" test/test_output | grep -v ^104 > good.out
	egrep "^[12349]" tmp.out | grep -v ^104 > test.out
	killall cced
	@diff -u good.out test.out >/dev/null 2>&1 \
		|| (echo TEST FAILED && false)
	@echo TEST PASSED
	
../libcce_common.a:
	make -C .. libcce_common.a

install: all
	@-mkdir -p $(CCE_SBINDIR)
	@-mkdir -p $(CCE_BINDIR)
	@-mkdir -p $(CCE_SCHEMADIR)
	@-mkdir -p $(SYS_INITDIR)
	@-mkdir -p $(SYS_PAMDIR)
	@-mkdir -p $(SYSTEMD_INITDIR)
	@$(INSTALL) $(INST_SBINFLAGS) $(DAEMON) $(CCE_SBINDIR)
	@echo Installed $(DAEMON) in $(CCE_SBINDIR)
	@$(INSTALL) $(INST_BINFLAGS) cced.init $(CCE_SBINDIR)
	@echo Installed cced.init in $(CCE_SBINDIR)
	@$(INSTALL) $(INST_BINFLAGS) cced.init.service $(SYSTEMD_INITDIR)
	@echo Installed cced.init.service in $(SYSTEMD_INITDIR)
	@$(INSTALL) $(INST_BINFLAGS) $(CLIENT) $(CCE_BINDIR)
	@echo Installed $(CLIENT) in $(CCE_BINDIR)
	@$(INSTALL) $(INST_BINFLAGS) cce_construct $(CCE_BINDIR)
	@echo Installed cce_construct in $(CCE_BINDIR)
	@$(RM) -f $(CCE_SCHEMADIR)/cced.schema
	@$(INSTALL) $(INST_FILEFLAGS) basetypes.schema $(CCE_SCHEMADIR)
	@echo Installed basetypes.schema in $(CCE_SCHEMADIR)
	@$(INSTALL) $(INST_BINFLAGS) cced.init $(SYS_INITDIR)
	@$(INSTALL) $(INST_BINFLAGS) pam_abl $(SYS_INITDIR)
	@echo Installed cced.init in $(SYS_INITDIR)
	@echo Installed pam_abl in $(SYS_INITDIR)
	@$(INSTALL) $(INST_FILEFLAGS) cced.pam $(SYS_PAMDIR)/cced
	@echo Installed cced.pam in $(SYS_PAMDIR)
	@$(INSTALL) $(INST_FILEFLAGS) $(SACPAM)  $(SYS_PAMDIR)/system-auth-cce
	@echo Installed system-auth-cce.pam in $(SYS_PAMDIR)

uninstall:
	$(RM) -f $(CCE_SBINDIR)/$(DAEMON)
	$(RM) -f $(CCE_SBINDIR)/cced.init
	$(RM) -f $(CCE_BINDIR)/$(CLIENT)
	$(RM) -f $(CCE_SCHEMADIR)/cced.schema
	$(RM) -f $(CCE_SCHEMADIR)/basetypes.schema
	$(RM) -f $(SYS_INITDIR)/cced
	$(RM) -f $(SYS_PAMDIR)/cced
	$(RM) -f $(SYS_PAMDIR)/system-auth-cce
	$(RM) -f $(SYSTEMD_INITDIR)/cced.init.service

clean:
	$(RM) -f $(OBJS) $(DAEMON) $(CLIENT) .depend
	$(RM) -f *.out

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
