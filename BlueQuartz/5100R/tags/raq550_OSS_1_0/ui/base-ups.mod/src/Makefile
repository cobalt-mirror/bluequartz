.PHONY: base-ups-am-rpm

all:
	make -C base-ups-am all

install:
	make -C base-ups-am install

rpm: base-ups-am-rpm

base-ups-am-rpm:
	tar -zcvf /tmp/base-ups-am.tar.gz base-ups-am &> /dev/null
	rpm -ta /tmp/base-ups-am.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/base-ups-am.tar.gz
