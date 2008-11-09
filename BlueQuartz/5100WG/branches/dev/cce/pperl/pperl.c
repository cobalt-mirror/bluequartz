/*
 * $Id: pperl.c 3 2003-07-17 15:19:15Z will $
 */

#include <cce_paths.h>
#include <stdio.h>
#include <stdlib.h>
#include <signal.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <sys/time.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <ud_socket.h>

#define SOCKETFILE 	CCEDIR "pperl.socket"
#define PPERLD		CCEBINDIR "pperld"

static void start_pperld(void);
static int safe_write(int fd, const char *buf, int len);

int
main(int argc, char *argv[])
{
	int fd;
	char buf[1024];
	int r;
	fd_set rfds;
	char *runfile;

	if (argc != 2) {
		fprintf(stderr, "usage: %s <filename>\n", argv[0]);
		exit(1);
	}
	if (argv[1][0] != '/') {
		char *pathbuf;

		/* build an absolute path */
		pathbuf = getcwd(NULL, 0);
		runfile = malloc(strlen(pathbuf) + strlen(argv[1]) + 2);
		sprintf(runfile, "%s/%s", pathbuf, argv[1]);
	} else {
		runfile = argv[1];
	}

	fd = ud_connect(SOCKETFILE);
	if (fd < 0) {
		int ntries = 100;

		start_pperld();
		while (ntries-- && (fd = ud_connect(SOCKETFILE)) < 0) {
			usleep(50000);
		}
		
		if (fd < 0) {
			fprintf(stderr, "can't connect to pperld\n");
			exit(2);
		}
	}

	safe_write(fd, runfile, strlen(runfile));
	safe_write(fd, "\n", 1);

	while (1) {
		FD_ZERO(&rfds);
		FD_SET(STDIN_FILENO, &rfds);
		FD_SET(fd, &rfds);

		select(fd+1, &rfds, NULL, NULL, NULL);

		if (FD_ISSET(fd, &rfds)) {
			r = read(fd, buf, sizeof(buf)-1);
			if (r == 0) {
				safe_write(STDOUT_FILENO, "\n", 1);
				break;
			}
			buf[r] = '\0';
			safe_write(STDOUT_FILENO, buf, strlen(buf));
		} else if (FD_ISSET(0, &rfds)) {
			r = read(STDIN_FILENO, buf, sizeof(buf)-1);
			if (r == 0) {
				sprintf(buf, "BYE\n");
				r = 4;
			}
			buf[r] = '\0';
			safe_write(fd, buf, strlen(buf));
		} else {
			break;
		}
	}

	close(fd);

	return 0;
}

static void 
start_pperld(void)
{
	if (!fork()) {
		int i;	
		/* close any fd > STDERR_FILENO */
		for (i = STDERR_FILENO; i < 255; i++) {
			close(i);
		}
		execl(PPERLD, PPERLD, NULL);
	}
}

#define NTRIES		8192
#define PANIC_TRIES	100
static int
safe_write(int fd, const char *buf, int len)
{
	int r;
	int ttl = 0;
	int ntries = NTRIES;

	do {
		r = write(fd, buf+ttl, len-ttl);
		if (r < 0) {
			if (errno != EAGAIN && errno != EINTR) {
				/* a legit error */
				return r;
			}
			ntries--;
			/* we're really low on last chances */
			if (ntries < PANIC_TRIES) {
				/* try letting the target catch up */
				usleep(100000);
			}
		}
		if (r > 0) {
			/* as long as we make forward progress, reset ntries */
			ntries = NTRIES;
			ttl += r;
		}
	} while (ttl < len && ntries);

	if (!ntries) {
		/* crap - we timed out on this write */
		fprintf(stderr, "pperl ERROR: safe_write timed out (%s)\n", 
			strerror(errno));
		return r;
	}

	return ttl;
}
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
