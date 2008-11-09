.PHONY: perl-handler-utils-rpm 

all:
	make -C perl-handler-utils all

install:
	make -C perl-handler-utils install

rpm: perl-handler-utils-rpm 

perl-handler-utils-rpm:
	tar -zcvf /tmp/perl-handler-utils.tar.gz perl-handler-utils &> /dev/null
	rpmbuild -ta /tmp/perl-handler-utils.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/perl-handler-utils.tar.gz
