CC = gcc

V_MAJ = 0
V_MIN = 2
VERSION = $(V_MAJ).$(V_MIN)

DEBUG = -ggdb -DDEBUG -DUSE_LIBDEBUG=1 #-pedantic
DEFS = -DVERSION="\"$(VERSION)\""
LIBDIRS = -L../codb -L../libscalar -L../cscp -L../../libdebug -L../conf
LIBS = $(LIBDIRS) \
	-lcodb -lcce_scalar -lcscp -ldebug -lcce_conf `glib-config --libs`
LIBINCLUDES = -I../codb/include -I../libscalar/include -I../cscp/include \
		  -I../../libdebug/include -I../conf/include
INCLUDES = -I. -I./include $(LIBINCLUDES)

CFLAGS = -O2 -Wall $(DEBUG) $(DEFS) $(INCLUDES) `glib-config --cflags`

SRCS = 			\
	main.c		\
	smd.c			\
	txnq.c		\
	ed.c
	
OBJS = \
	main.o		\
	smd.o			\
	txnq.o		\
	ed.o

CCED = cced

all: $(CCED) testcli

$(CCED): $(OBJS)
	$(CC) -o $@ $^ $(LIBS)

testcli: testcli.c
	$(CC) $(CFLAGS) -o $@ $^ $(LIBS)

clean:
	rm -f $(OBJS)
	rm -f $(CCED)
	rm -f testcli
