/*
 * $Id: ed.c 3 2003-07-17 15:19:15Z will $
 *
 * ed.c
 * this is the Event Dispatcherdaemon 
 */

#include <stdio.h>
#include <stdlib.h>
#include <signal.h>
#include <unistd.h>
#include <errno.h>
#include <syslog.h>
#include <string.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <cced.h>
#include <libdebug.h>
#include <ud_socket.h>

/* debugging */
#ifdef DEBUG
#undef DPRINTF
#undef DPERROR
#define DPRINTF(f, a...)	fprintf(stderr, "ED DEBUG: " f, ##a);
#define DPERROR(f)		perror("ED DEBUG: " f);
#endif

/* for error messages */
#define EPRINTF(f, a...)	do { 							\
					  fprintf(stderr, "ed: " f, ##a); 		\
					  syslog(LOG_ERR, f, ##a);			\
					} while (0);
#define EPERROR(f) 		do {							\
					  perror("ed: " f);				\
					  syslog(LOG_ERR, f " : %s", strerror(errno));\
					} while (0);


/* functions used internally */
static void sighandle(int s);
static void exithandle(void);


void
start_ed_thread(void)
{
	int listenfd = 0;

	DPRINTF("start_ed_thread: starting\n");

	/* prep syslog */
	OPENLOG("ed");
	syslog(LOG_NOTICE, "starting up (pid %d)", getpid());
	DPRINTF("start_ed_thread: starting up (pid %d)\n", getpid());

	/* handle signals */
	signal(SIGHUP, SIG_IGN);
	signal(SIGPIPE, SIG_IGN);	/* FIXME */
	signal(SIGCHLD, SIG_DFL);
	signal(SIGINT, sighandle);
	signal(SIGQUIT, sighandle);
	signal(SIGTERM, sighandle);

	/* setup a UNIX domain socket */
	listenfd = ud_create_socket(ED_UDS_NAME);
	if (listenfd < 0) {
		EPRINTF("start_ed_thread: create_ud_socket() failed\n");
		exithandle();
		memdebug_dump();
		_exit(-1);
	}

	while (1) {
		;
	}

	EPRINTF("start_ed_thread: we should not be here\n");
	exithandle();
	memdebug_dump();
	_exit(-2);
}

static void 
sighandle(int s)
{
	/* make sure it is a valid signal */
	if (s == SIGINT || s == SIGTERM || s == SIGQUIT) {
		syslog(LOG_NOTICE, "caught signal %d", s);
		DPRINTF("sighandle: caught signal %d\n", s);

		/* exit cleanly */
		exithandle();
		memdebug_dump();
		exit(0);
	} else {
		EPRINTF("sighandle: signal %d caught, but unknown\n", s);
		return;
	}
}

static void 
exithandle(void)
{
	unlink(ED_UDS_NAME);
	
	syslog(LOG_NOTICE, "exiting");
	DPRINTF("exithandle: all done - catch you on the flip side\n");
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
