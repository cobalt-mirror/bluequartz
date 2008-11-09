.PHONY: base-winshare-am-rpm

all:
	make -C base-winshare-am all

install:
	make -C base-winshare-am install

rpm: base-winshare-am-rpm

base-winshare-am-rpm:
	tar -zcvf /tmp/base-winshare-am.tar.gz base-winshare-am &> /dev/null
	rpmbuild -ta /tmp/base-winshare-am.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/base-winshare-am.tar.gz
