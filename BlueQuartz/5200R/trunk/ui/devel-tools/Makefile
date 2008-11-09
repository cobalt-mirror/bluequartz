include rules/defines.mk

CCEMODULE = sausalito-devel-tools
CCERPMSPEC = sausalito-devel-tools.spec

VERSION=0.5.1
RELEASE=0BQ14

DIRS = scripts libdebug rules templates glue

all dep clean install: 
	for dir in $(DIRS); do \
		make -C $$dir $(MAKECMDGOALS); \
	done

# update_version: FORCE
# 	perl -pi -e 's/^Version:.*/Version: $(VERSION)/;s/^Release:.*/Release: $(RELEASE)/;' sausalito-devel-tools.spec
	

include rules/package.mk

FORCE:

