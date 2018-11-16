BUILD=$(shell date --rfc-3339=date | sed s/-//g)
PRODUCT=$(shell if [ `uname -m` = "x86_64" ]; then  echo -n "5208"; else  echo -n "5207"; fi)

all:

clean:

install:
	mkdir -p $(PREFIX)/etc/rpm
	install -m 0755 etc/rpm/macros.blueonyx $(PREFIX)/etc/rpm
