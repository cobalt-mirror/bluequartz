# $Id: Makefile 229 2003-07-18 20:22:20Z will $

include ../dynamic.mk

TESTS = alphabeta baddata sorting #security

.PHONY: $(TESTS)
$(TESTS):
	$(MAKE) -C $@ test
	
clean:
	for a in $(TESTS); do $(MAKE) -C $$a clean || exit 1; done
