# Makefile for common src

SRCS = 			\
	csem.c		\
	tcp_socket.c	\
	ud_socket.c		\
	stresc.c		\
	xml_parse.c		\
	cce_debug_mask.c

OBJS = $(patsubst %.c,%.o,$(SRCS))

all: depend $(LIB)

$(LIB): $(OBJS)
	ar rcs $@ $^
	ranlib $@

depend: .depend

.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

clean:
	rm -f .depend core
	rm -f $(LIB) $(OBJS) 
	rm -f *.o

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
