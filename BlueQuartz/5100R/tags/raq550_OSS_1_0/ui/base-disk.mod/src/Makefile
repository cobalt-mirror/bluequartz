.PHONY: base-disk-am-rpm

all:
	make -C base-disk-am all

install:
	make -C base-disk-am install

rpm: base-disk-am-rpm

base-disk-am-rpm:
	tar -zcvf /tmp/base-disk-am.tar.gz base-disk-am &> /dev/null
	rpm -ta /tmp/base-disk-am.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/base-disk-am.tar.gz
