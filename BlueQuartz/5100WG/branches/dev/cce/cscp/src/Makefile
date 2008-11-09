LIB = libcscp.a

SRCS = \
	lex.cscp_cmd.c  \
	cscp_cmd_table.c \
	cscp_fsm.c \
	cscp_msgs.c \
	cscp_parse.c \
	cscp_auth.c \
	cscp_write.c

OBJS = $(patsubst %.c,%.o,$(SRCS))


all: depend $(LIB)

$(LIB): $(OBJS)
	ar rcs $@ $^
	ranlib $@

lex.cscp_cmd.c: cscp_cmd.l
	flex -o$@ $^

depend: .depend

.depend: lex.cscp_cmd.c
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

clean:
	rm -f .depend core
	rm -f $(LIB) $(OBJS) 
	rm -f *.o
	rm -f lex.cscp_cmd.c

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
