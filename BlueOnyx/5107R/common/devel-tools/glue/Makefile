BUILD=20081203
PRODUCT=$(shell \
if [ -f /etc/fedora-release ] && [ "`rpm -q --qf=%{version} fedora-release`" = "1" ]; then echo '5100'; fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "3" ]; then echo '5101'; fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "4" ]; then \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "i386" ]; then echo '5102'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "x86_64" ]; then echo '5103'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "ppc" ]; then echo '5104'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "alpha" ]; then echo '5104'; fi; \
fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "5" ]; then echo '5106'; fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "6" ]; then echo '5107'; fi; \
)

all:

clean:

install:
	mkdir -p $(PREFIX)/etc/rpm
	install -m 0755 etc/rpm/macros.blueonyx $(PREFIX)/etc/rpm
