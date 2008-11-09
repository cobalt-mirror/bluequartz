# Some magic to make subdirectory builds with good dependencies
#
# To use this, specify some 'magic' variables in your Makefile, then include
# this:
#     DIRMK_SRCS	: the list of source files
#     DIRMK_DIRS	: a list of subdirectories to recurse into
#     DIRMK_LIBS	: a string appended to the linking of the dir object
#     DIRMK_PRE_ALL	: a list of targets to be made before 'make all'
#     DIRMK_ALL		: a list of targets to be made after 'make all'
#     DIRMK_CLEAN	: a list of targets to be made during 'make clean'
#
# The results of a dirmake.mk build are 2 files: a directory object file
# (.o), and a directory archive (.a), each of which is comprised of the
# object files for all the $(DIRMK_SRCS) and the directory objects for all
# the $(DIRMK_DIRS).

OBJS_ := $(DIRMK_SRCS:.c=.o)
DIROBJS_ := $(foreach dir, $(DIRMK_DIRS), $(dir)/$(dir).o)
DIRTARGETO_ := $(notdir $(CURDIR)).o
DIRTARGETA_ := $(notdir $(CURDIR)).a
CLEANS_ := $(OBJS_) core $(DIRTARGETO_) $(DIRTARGETA_)
CFLAGS_ = $(CFLAGS)

.SUFFIXES:
.c.o:
	$(CC) $(CFLAGS_) -c -o $@ $<
.SUFFIXES: .c .o

.PHONY: all
all: $(DIRMK_PRE_ALL) $(DIRMK_DIRS) $(DIRTARGETO_) $(DIRTARGETA_) $(DIRMK_ALL)

$(DIRTARGETO_): $(DIROBJS_) $(OBJS_)
	$(LD) -r -o $@ $^ $(DIRMK_LIBS)

$(DIRTARGETA_): $(DIROBJS_) $(OBJS_)
	$(AR) rcs $@ $^

$(DIROBJS_):
	$(MAKE) -C $(@D)

.PHONY: $(DIRMK_DIRS)
$(DIRMK_DIRS):
	$(MAKE) -C $@

.PHONY: clean
clean: $(DIRMK_CLEAN)
	$(RM) $(CLEANS_) .depend
	for a in $(DIRMK_DIRS); do $(MAKE) -C $$a clean || exit 1; done
	[ -d test ] && $(MAKE) -C test clean || true

.PHONY: depend dep
depend dep: $(DIRMK_SRCS)
	$(RM) .depend; touch .depend
	[ -n "$^" ] && $(CC) $(CFLAGS) -MM $^ > .depend
	for a in $(DIRMK_DIRS); do \
		$(MAKE) -C $$a depend || exit 1; \
	done

ifeq (.depend,$(wildcard .depend))
include .depend
endif
