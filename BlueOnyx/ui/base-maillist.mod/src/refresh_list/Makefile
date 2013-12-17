# $Id: Makefile 3 2003-07-17 15:19:15Z will $

install: all
	install -m 04755 -o root -g root -s refresh_list /usr/sausalito/bin

all: refresh_list

refresh_list: refresh_list.c
	 gcc -I /usr/sausalito/include/cce -L /usr/sausalito/lib -lcce `glib-config --cflags` -o refresh_list refresh_list.c

clean:
	/bin/rm -f refresh_list

