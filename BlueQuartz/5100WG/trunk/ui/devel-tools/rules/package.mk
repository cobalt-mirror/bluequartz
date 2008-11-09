FTP_PUT=/usr/sausalito/bin/ftp_put
RPM_DIR=/fargo/rpms/i386
SRPM_DIR=/fargo/srpms

.PHONY: rpm rpm_upload update_version

rpm: $(CCERPMSPEC)
	@if [ x'$(CCEMODULE)' = x -o x'$(CCERPMSPEC)' = x ]; then \
		echo "CCEMODULE or CCERPMSPEC not specified."; \
		exit -1; \
	fi
	@echo "building rpm..."
	-rm -rf $(CCETMPDIR)/$(CCEMODULE)
	make clean
	-mkdir -p $(CCETMPDIR)/$(CCEMODULE)
	tar --exclude '*CVS*' --create --file - * | (cd $(CCETMPDIR)/$(CCEMODULE); tar xBf -)
	(cd $(CCETMPDIR); tar czBf /usr/src/redhat/SOURCES/$(CCEMODULE).tar.gz $(CCEMODULE))
	rm -rf $(CCETMPDIR)/$(CCEMODULE)
	cp $(CCERPMSPEC) /usr/src/redhat/SPECS
	touch /tmp/time.stamp
	@/bin/rm -rf rpms srpms || /bin/true
	@mkdir rpms srpms || /bin/true
ifeq ($(SLEEP), yes)
	sleep 10 ; # same problem as in module.mk
endif
	rpmbuild -ba /usr/src/redhat/SPECS/$(CCERPMSPEC)
	find /usr/src/redhat/RPMS -follow -type f -name \*.rpm -newer /tmp/time.stamp \
		-exec /bin/cp \{} rpms \; -print
	find /usr/src/redhat/SRPMS -follow -type f -name \*.rpm -newer /tmp/time.stamp \
		-exec /bin/cp \{} srpms \; -print
	find rpms -type f -printf "RPM: %P\n" > packing_list

update_version:
	perl -pi -e 's/^Version:.*/Version: $(VERSION)/;s/^Release:.*/Release: $(RELEASE)/;' $(CCERPMSPEC)

rpm_upload:
	(cd rpms && $(FTP_PUT) -d $(RPM_DIR) * )
	#(cd srpms && $(FTP_PUT) -d $(SRPM_DIR) * )

