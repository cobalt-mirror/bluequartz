.SUFFIXES: .lo

CCELIBOBJS = $(CCELIBSRC:.c=.o)
CCESHLIBOBJS = $(CCESHLIBSRC:.c=.lo)
CCEBINOBJS = $(CCEBINSRC:.c=.o)
CCELIB = $(CCELIBNAME).a
CCESHLIB_MAJ = $(CCELIBNAME).so.$(CCESHLIBMAJOR)
CCESHLIB = $(CCESHLIB_MAJ).$(CCESHLIBMINOR)
CCESHLIB_NOVERS = $(CCELIBNAME).so

.c.o:
	$(CC) $(CFLAGS) $(CCECDEFS) -c $<

.c.lo: 
	$(CC) $(CFLAGS) $(CCESHAREDCDEFS) -c $< -o $*.lo

$(CCELIB): $(CCELIBOBJS)
	@if [ -z '$(CCELIBNAME)' ]; then \
		echo "CCELIBNAME not specified."; \
		exit -1; \
	fi
	$(AR) crv $(CCELIB) $(CCELIBOBJS)
	$(RANLIB) $(CCELIB)

$(CCESHLIB): $(CCESHLIBOBJS)
	@if [ -z '$(CCELIBNAME)' ]; then \
		echo "CCELIBNAME not specified."; \
		exit -1; \
	fi
	$(CC) $(CCESHAREDLDDEFS) $(LDSHAREDFLAGS) -Wl,-soname,$(CCESHLIB_MAJ) -o $(CCESHLIB) $(CCESHLIBOBJS) -Wl,-rpath,$(CCELIBDIR) -L$(CCELIBDIR) $(CCESHLIB_LIBS)
	-ln -sf $(CCESHLIB) $(CCESHLIB_MAJ)
	-ln -sf $(CCESHLIB) $(CCELIBNAME).so

$(CCESHLIB_NOVERS): $(CCESHLIBOBJS)
	@if [ -z '$(CCELIBNAME)' ]; then \
		echo "CCELIBNAME not specified."; \
		exit -1; \
	fi
	$(CC) $(CCESHAREDLDDEFS) $(LDSHAREDFLAGS) $(CCESHLIBOBJS) -o $(CCESHLIB_NOVERS) -Wl,-rpath,$(CCELIBDIR) -L$(CCELIBDIR) $(CCESHLIB_LIBS)

$(CCEBIN): $(CCEBINOBJS)
	@if [ -z '$(CCEBINOBJS)' ]; then \
		echo "CCEBINOBJS not specified."; \
		exit -1; \
	fi
	$(CC) $(LDFLAGS) -o $(CCEBIN) $(CCEBINOBJS) -Wl,-rpath,$(CCELIBDIR) -L$(CCELIBDIR) $(LIBS)

.depend: $(CCEDEPSRC)
	@if test -z '$(CCEDEPSRC)'; then \
		echo "CCEDEPSRC is empty."; \
		exit -1; \
	fi
	$(CC) -E $(CFLAGS) $(CCECDEFS) -M $(CCEDEPSRC) > .depend

lib: $(CCELIB)

ifdef CCESHLIBMAJOR
shlib: $(CCESHLIB)
else
shlib: $(CCESHLIB_NOVERS)
endif

bin: $(CCEBIN)

dep: .depend

install_headers: $(CCEHEADERS)
	mkdir -p $(CCEINCDIR)/cce
	$(INSTALL) $(INSTALL_HEADERFLAGS) $(CCEHEADERS) $(CCEINCDIR)/cce

install_bin: $(CCEBIN)
	mkdir -p $(CCEBINDIR)
	$(INSTALL) $(INSTALL_BINFLAGS) $(CCEBIN) $(CCEBINDIR)

install_lib: $(CCELIB)
	mkdir -p $(CCELIBDIR)
	$(INSTALL) $(INSTALL_LIBFLAGS) $(CCELIB) $(CCELIBDIR)

install_shlib: $(CCESHLIB)
	mkdir -p $(CCELIBDIR)
	$(INSTALL) $(INSTALL_SHLIBFLAGS) $(CCESHLIB) $(CCELIBDIR)
	ln -sf $(CCESHLIB) $(CCELIBDIR)/$(CCESHLIB_MAJ)
	ln -sf $(CCESHLIB_MAJ) $(CCELIBDIR)/$(CCELIBNAME).so

clean:
	-make local_clean
	-rm -rf $(CCEOBJS) $(CCELIBOBJS) $(CCESHLIBOBJS)
	-rm -rf $(CCELIB) $(CCELIBNAME).so $(CCEBIN)
ifdef CCESHLIBMAJOR
	-rm -rf $(CCESHLIB) $(CCESHLIB_MAJ)
else
	-rm -rf $(CCESHLIB_NOVERS)
endif
ifdef CCETESTBINS
	-rm -rf $(CCETESTBINS)
endif
	-rm -rf \.depend *~ #* 

# dependency
ifeq (.depend, $(wildcard .depend))
include .depend
endif
