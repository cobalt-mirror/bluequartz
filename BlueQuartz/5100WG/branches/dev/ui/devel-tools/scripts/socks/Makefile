# $Id: Makefile 201 2003-07-18 19:11:07Z will $

CC      = gcc
CFLAGS  = -O2
BINDIR  = /usr/sbin
CFGDIR  = /etc
TUNNEL  = socks-tunnel
CONFIG  = proxy.conf
PROFILE = $(CFGDIR)/profile

.PHONY: all
all: $(TUNNEL)

.PHONY: debian
debian: all
	$(shell perl -pi -e 's/^socksWrapper=.*/socksWrapper=socksify/' $(CONFIG))

.PHONY: redhat
redhat: all
	$(shell perl -pi -e 's/^socksWrapper=.*/socksWrapper=runsocks/' $(CONFIG))

socks-tunnel: %: %.c
	$(CC) $(CFLAGS) -o $@ $^
	strip $@

.PHONY: install
install: all
	install -m755 $(CONFIG) $(CFGDIR)/$(CONFIG)
	echo "[ -s $(CFGDIR)/$(CONFIG) ] && . $(CFGDIR)/$(CONFIG)" >> $(PROFILE)
	install -m755 $(TUNNEL) $(BINDIR)/$(TUNNEL)
	install -m755 rssh $(BINDIR)
	ln $(BINDIR)/rssh $(BINDIR)/rscp

.PHONY: clean
clean:
	rm -f $(TUNNEL) *.o

