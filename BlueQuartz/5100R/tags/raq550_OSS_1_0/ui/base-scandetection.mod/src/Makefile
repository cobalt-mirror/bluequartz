#
# Makefile for creating Misc Scandetection peices
#

NAME = base-scandetection-misc
VERSION = 1.0
RELEASE = 30
METASPECFILE = $(NAME).spec.meta
SPECFILE = $(NAME).spec

.PHONY: base-scandetection-misc-rpm

all: rpm

rpm: specfile base-scandetection-misc-rpm

specfile :
	cd specs; if [ ! -f $(METASPECFILE) ]; then \
	echo "Error: Missing file $(METASPECFILE)"; \
	exit 1; \
	fi
	cd specs; \
		sed -e 's+~~~NAME~~~+$(NAME)+' -e 's+~~~VERSION~~~+$(VERSION)+' -e 's+~~~RELEASE~~~+$(RELEASE)+' $(METASPECFILE) > $(SPECFILE)

base-scandetection-misc-rpm:
	mkdir -p scandetection
	rm -fr /tmp/base-scandetection-misc-$(VERSION)
	mkdir /tmp/base-scandetection-misc-$(VERSION)
	cp -fr init.d /tmp/base-scandetection-misc-$(VERSION)
	cp -fr lcd.d /tmp/base-scandetection-misc-$(VERSION)
	cp -fr swatch /tmp/base-scandetection-misc-$(VERSION)
	cp -fr scandetection /tmp/base-scandetection-misc-$(VERSION)
	cd ./commonlib && make clean
	cd ./paflogd && make clean && make && cd ..
	cp -fr paflogd/paflogd /tmp/base-scandetection-misc-$(VERSION)
	cp -fr paflogd/pafalertd /tmp/base-scandetection-misc-$(VERSION)
	tar -C /tmp/ -c base-scandetection-misc-$(VERSION) -f /usr/src/redhat/SOURCES/base-scandetection-misc.tar.gz > /dev/null
	cp specs/base-scandetection-misc.spec /usr/src/redhat/SPECS
	rpm -ba /usr/src/redhat/SPECS/base-scandetection-misc.spec
	mkdir -p rpms
	cp /usr/src/redhat/RPMS/noarch/base-scandetection-misc-$(VERSION)-$(RELEASE).noarch.rpm rpms/.

clean:
	cd paflogd && make clean && cd ..
	rm .rpmignore
	rm /usr/src/redhat/SOURCES/base-scandetection-misc.tar.gz
	rm /usr/src/redhat/SPECS/base-scandetection-misc.spec
	rm -fr /tmp/base-scandetection-misc-$(VERSION)
