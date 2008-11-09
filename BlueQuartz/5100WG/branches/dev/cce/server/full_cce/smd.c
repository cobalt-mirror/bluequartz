/*
 * $Id: smd.c 3 2003-07-17 15:19:15Z will $
 *
 * smd.c
 * this is the Session Manager daemon 
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
#include <odb.h>
#include <libdebug.h>
#include <ud_socket.h>

/* debugging */
#ifdef DEBUG
#undef DPRINTF
#undef DPERROR
#define DPRINTF(f, a...)	fprintf(stderr, "SMD DEBUG: " f, ##a);
#define DPERROR(f)		perror("SMD DEBUG: " f);
#endif

/* for error messages */
#define EPRINTF(f, a...)	do { 							\
					  fprintf(stderr, "smd: " f, ##a); 		\
					  syslog(LOG_ERR, f, ##a);			\
					} while (0);
#define EPERROR(f) 		do {							\
					  perror("smd: " f);				\
					  syslog(LOG_ERR, f " : %s", strerror(errno));\
					} while (0);


/* functions used internally */
static void sighandle(int s);
static void sm_sighandle(int s);
static void exithandle(void);
static void smd_kill_children(void);
static int manage_session(int clisock);

/* used throughout the daemon */
static int children[SMD_MAX_CONNECTIONS];
static int nchildren = 0;

/*
 * this is the session manager
 * It is responsible for accepting a client connection, forking a new copy
 * of itself, and waiting for more connections.
 * It processes incoming CSCP, handles reads with libODB, and compiles
 * and writes into a transaction-object.  When COMMITed, the txn object is
 * sent to the TxnQ Daemon
 */
void
start_smd_thread(void)
{
	int listenfd = 0;
	
	/* prep syslog */
	OPENLOG("smd");
	syslog(LOG_NOTICE, "starting up (pid %d)", getpid());
	DPRINTF("start_smd_thread: starting up (pid %d)\n", getpid());

	/* handle signals */
	signal(SIGHUP, SIG_IGN);
	signal(SIGPIPE, SIG_IGN);
	signal(SIGINT, sighandle);
	signal(SIGQUIT, sighandle);
	signal(SIGTERM, sighandle);
	signal(SIGCHLD, sighandle);

	/* clear the child list */
	memset(children, 0, SMD_MAX_CONNECTIONS * sizeof(int));

	/* setup a UNIX domain socket */
	listenfd = ud_create_socket(SMD_UDS_NAME);
	if (listenfd < 0) {
		EPRINTF("start_smd_thread: create_ud_socket() failed\n");
		exithandle();
		memdebug_dump();
		exit(-1);
	}

	/* main loop - get new connections, spawn new threads */
	while (1) {
		int newsock;
		int i = 0;
		
		/* get the new connection - block here */
		newsock = ud_accept(listenfd);
		if (newsock < 0) {
			/* log and exit if accept_conn() fails */
			EPRINTF("start_smd_thread: ud_accept() failed\n");
			exithandle();
			memdebug_dump();
			exit(-2);
		}

		/* do we have space for it ? */
		if (nchildren >= SMD_MAX_CONNECTIONS) {
			/* kill the connection */
			char *m1 = "Maximum number of connections reached\n"
				     "disconnecting...";
			write(newsock, m1, strlen(m1)); 
			close(newsock);

			/* log it and try again */
			syslog(LOG_INFO, "%s", m1);
			DPRINTF("%s\n", m1);
			continue;
		}

		/* find a spot for it in the list */
		while(children[i] && i < SMD_MAX_CONNECTIONS) {
			i++;
		}
		
		/* did we overshoot ? */
		if (i >= SMD_MAX_CONNECTIONS) {
			/* oops! */
			char *m1 = "Maximum number of connections reached\n"
				     "disconnecting...";
			write(newsock, m1, strlen(m1));
			close(newsock);

			/* log it and try again - this is bad*/
			EPRINTF("start_smd_thread: could not find free"
				"list entry for new connection\n");

			continue;
		}
			
		/* fork a new thread */
		children[i] = fork();
		switch (children[i]) {
			case -1:
                  	EPERROR("start_smd_thread: fork()");
				exithandle();
				memdebug_dump();
                  	exit(-3);
                  	break;
			case 0:
				/* child */
				close(listenfd);
				/* handle signals for the child */
				signal(SIGHUP, SIG_IGN);
				signal(SIGPIPE, SIG_IGN);
				signal(SIGINT, sm_sighandle);
				signal(SIGQUIT, sm_sighandle);
				signal(SIGTERM, sm_sighandle);
				signal(SIGCHLD, sm_sighandle);
				i = manage_session(newsock);
				memdebug_dump();
				exit(i);
				break;
			default:
				nchildren++;
				close(newsock);

				/* log it */
				syslog(LOG_INFO, "client connection accepted (%d)",
					nchildren);
				DPRINTF("client connection accepted (%d)\n", nchildren);
		}
	}

	EPRINTF("start_smd_thread: we should not be here\n");
	exithandle();
	memdebug_dump();
	exit(-4);
}

/* FIXME - this is going to be the devil.... */
static int 
manage_session(int cli_fd)
{
	//char buf[1024];
	int r;
	int txnq_fd;
	odb_handle *odbh;

	signal(SIGHUP, SIG_IGN);
	signal(SIGPIPE, SIG_IGN);
	signal(SIGINT, SIG_DFL);
	signal(SIGQUIT, SIG_DFL);
	signal(SIGTERM, SIG_DFL);
	signal(SIGCHLD, SIG_DFL);

	/* connect to txnq */
	txnq_fd = ud_connect(TXNQ_UDS_NAME);
	if (txnq_fd < 0) {
		EPRINTF("manage_session: unable to connect to txnq\n");
		close(cli_fd);
		return txnq_fd;
	}

	odbh = odb_connect();

	r = cscp_fsm(cli_fd, txnq_fd, odbh);

/*
	r = read(cli_fd, buf, 1023);
	while(r) {
		buf[r] = '\0';
		DPRINTF("manage_session: read %s\n", buf);
		r = read(cli_fd, buf, 1023);
	}
*/
		
	odb_disconnect(odbh);
	close(cli_fd);
	close(txnq_fd);

	return 0;
}

		
static void 
sighandle(int s)
{
	/* make sure it is a valid signal */
	if (s == SIGINT || s == SIGTERM || s == SIGQUIT || s == SIGCHLD) {
		syslog(LOG_NOTICE, "caught signal %d", s);
		DPRINTF("sighandle: caught signal %d\n", s);

		if (s == SIGCHLD) {
			int i;
			int pid;

			/* handle child dying */
			pid = wait(NULL);
			if (pid > 0) {
				syslog(LOG_NOTICE, "child (pid %d) exited", pid);
			} else {
				EPRINTF("sighandle: wait() returned %d (%s)\n",
					pid, strerror(errno));
			}

			/* clear it's list entry */
			for (i = 0; i < SMD_MAX_CONNECTIONS; i++) {
				if (children[i] == pid) {
					children[i] = 0;
					nchildren--;
				}
			}
		} else {
			/* exit cleanly */
			exithandle();
			memdebug_dump();
			exit(0);
		}
	} else {
		EPRINTF("sighandle: signal %d caught, but unknown\n", s);
		return;
	}
}

static void 
sm_sighandle(int s)
{
	/* make sure it is a valid signal */
	if (s == SIGINT || s == SIGTERM || s == SIGQUIT || s == SIGCHLD) {
		syslog(LOG_NOTICE, "caught signal %d", s);
		DPRINTF("sm_sighandle: caught signal %d\n", s);

		/* exit cleanly */
		memdebug_dump();
		exit(0);
	} else {
		EPRINTF("sm_sighandle: signal %d caught, but unknown\n", s);
		return;
	}
}

static void 
exithandle(void)
{
	smd_kill_children();
	unlink(SMD_UDS_NAME);
	
	syslog(LOG_NOTICE, "exiting");
	DPRINTF("exithandle: all children killed - goodbye, cruel world\n");
}

static void
smd_kill_children(void)
{
	int i;

	/* kill each child */
	for (i = 0; i < SMD_MAX_CONNECTIONS; i++) {
		int pid;

		if (!children[i]) {
			continue;
		}
		
		kill(children[i], SIGTERM);
		pid = wait(NULL);
		if (pid > 0) {
			syslog(LOG_NOTICE, "killed and reaped child (pid %d)", pid);
		} else {
			EPRINTF("smd_kill_children: wait() returned %d (%s)\n", pid,
				strerror(errno));
		}
		children[i] = 0;
	}
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
