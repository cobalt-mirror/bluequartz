BUILD=$(shell date --rfc-3339=date | sed s/-//g)
PRODUCT=$(shell echo -n "5210")

all:

clean:

install:
	mkdir -p $(PREFIX)/etc/rpm
	install -m 0755 etc/rpm/macros.blueonyx $(PREFIX)/etc/rpm
