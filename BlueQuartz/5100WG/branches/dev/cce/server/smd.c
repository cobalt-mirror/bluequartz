/* $Id: smd.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2002 Sun Microsystems, Inc.  All rights reserved. */
/*
 * smd.c
 * this is the core of the Session Manager Daemon
 */

#include "cce_common.h"
#include "cced.h"
#include <stdio.h>
#include <stdlib.h>
#include <signal.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include "codb.h"
#include "cscp_fsm.h"
#include "ud_socket.h"
#include "csem.h"
#include "cce_ed.h"

#define CCEF_USER		CODBF_USER
#define CCEF_ADMIN		CODBF_ADMIN

/* functions used internally */
static int manage_session(int cli_fd, char *idstr, struct ucred *creds);
static void smd_sighandle(int s);
static void smd_child_sighandle(int s);
static void smd_exithandle(int n);
static void smd_kill_children(void);
static void smd_do_hup(void);

/* used throughout the daemon */
static cce_conf *conf = NULL;
static int children[SMD_MAX_CONNECTIONS];
static int nchildren = 0;
static int write_sem = -1;
static int listenfd = 0;

/*
 * this is the session manager
 * It is responsible for accepting a client connection, forking a new copy
 * of itself, and waiting for more connections.
 */
void
start_smd_thread(void)
{
	sigset_t sigblockset;
	sigset_t sigoldset;

	closelog();
	OPENLOG("cced(smd)");
	/* 
	 * FIXME: eventually, this will be a new process from the watcher
	 * syslog(LOG_NOTICE, "starting up (pid %d)", getpid());
	 * DPRINTF(DBG_CCED, "main: starting up (pid %d)\n", getpid());
	 */ 

	/* handle signals */
	signal(SIGHUP, smd_sighandle);
	signal(SIGINT, smd_sighandle);
	signal(SIGTERM, smd_sighandle);
	signal(SIGQUIT, smd_sighandle);	
	
#ifdef DEBUG_CCED
	atexit(memdebug_dump);
#endif

	/* if we are forking for connections */
	if (!nfflag) {
		signal(SIGCHLD, smd_sighandle);

		/* clear the child list */
		memset(children, 0, SMD_MAX_CONNECTIONS * sizeof(int));
	}

	/* setup a UNIX domain socket */
	listenfd = ud_create_socket(CCESOCKET);
	if (listenfd < 0) {
		CCE_SYSLOG("start_smd_thread: create_ud_socket() failed");
		smd_exithandle(-1);
	}

	/* get the global configuration */
	if (!nhflag && !roflag) {
		conf = cce_conf_get_configuration(CCECONFDIR, CCEHANDLERDIR);
		if (!conf) {
			CCE_SYSLOG("error loading handler configuration");
			smd_exithandle(-2);
		}
	} else {
		/* don't use handlers */
		conf = cce_conf_get_configuration(NULL, "");
	}

	/* run any odb inits */
	if (codb_init(CCESCHEMASDIR) < 0) {
		CCE_SYSLOG("error initializing database");
		smd_exithandle(-3);
	}
	if (roflag) {
		/* set it read-only */
		codb_set_ro(1);
	}

	/*
	 * Once here, startup is imminent
	 */
	unlink(CCEMSGFILE);

	/* get a semaphore for write operations */
	write_sem = csem_get(1);

	/* initialize sigblockset to sigchild */
	sigemptyset(&sigblockset);
	sigaddset(&sigblockset, SIGCHLD);

	/* main loop - get new connections */
	while (1) {
		int newsock;
		struct ucred creds;
		char buf[16];
		int i = 0;
		
		/* get the new connection - block here */
		newsock = ud_accept(listenfd, &creds);
		if (newsock < 0) {
			/* log and exit if accept_conn() fails */
			CCE_SYSLOG("start_smd_thread: ud_accept() failed");
			smd_exithandle(-4);
		}

		/* set the new socket to close on exec */
		fcntl(newsock, F_SETFD, 1);

		/* format the credentials */
		snprintf(buf, sizeof(buf), "[%d:%d]", 
			creds.uid, creds.pid);

		/* log it */
		DPRINTF(DBG_SESSION, "client connection accepted from %s\n", buf);

		/* do a non-forking version? */
		if (nfflag) {
			manage_session(newsock, buf, &creds);
			close(newsock);
			continue;
		}

		/* forking version: do we have space for it ? */
		if (nchildren >= SMD_MAX_CONNECTIONS) {
			/* kill the connection */
			char *m1 = "Maximum number of connections reached\n"
					"disconnecting...";
			write(newsock, m1, strlen(m1));
			close(newsock);

			/* log it and try again */
			CCE_SYSLOG("%s", m1);
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
			CCE_SYSLOG("start_smd_thread: could not find free"
				"list entry for new connection\n");

			continue;
		}		

		sigprocmask(SIG_BLOCK, &sigblockset, &sigoldset);

		/* fork a new thread */
		children[i] = fork();
		switch (children[i]) {
			case -1:
			CCE_SYSLOG("start_smd_thread: fork() %s", strerror(errno));
				smd_exithandle(-4);
			break;
			case 0:
				/* child */
				close(listenfd);
				manage_session(newsock, buf, &creds);
				break;
			default:
				nchildren++;
				sigprocmask(SIG_SETMASK, &sigoldset, NULL);
				close(newsock);
		}
	}

	CCE_SYSLOG("start_smd_thread: we should not be here");
	smd_exithandle(-5);
}

static void
smd_sighandle(int s)
{
	/* do we know about this signal? */
	if (s == SIGCHLD) {
		int i;
		int pid;

		DPRINTF(DBG_CCED, "smd_sighandle: caught SIGCHLD\n");

		while (1) {
			/* handle child dying */
			pid = waitpid(-1, NULL, WNOHANG);

			/* exit if no children are ready or exist */
			if (pid <= 0) {
				if (pid < 0 && errno != ECHILD) {
					CCE_SYSLOG("smd_sighandle: wait() %s", strerror(errno));
				}
				break;
			}

			/* log that a child exited, and repeat */
			DPRINTF(DBG_EXCESSIVE, "child (pid %d) exited\n", pid);

			/* clear it's list entry */
			for (i = 0; i < SMD_MAX_CONNECTIONS; i++) {
				if (children[i] == pid) {
					children[i] = 0;
					nchildren--;
					break;
				}
			}
		}
	} else if (s == SIGHUP || s == SIGINT || s == SIGTERM || s == SIGQUIT) {
		switch (s) {
			case SIGHUP:
			    CCE_SYSLOG("caught SIGHUP: restarting");
			    atexit(smd_do_hup);
			    break;
			case SIGINT:
			    CCE_SYSLOG("caught SIGINT: cleaning up");
			    break;
			case SIGTERM:
			    CCE_SYSLOG("caught SIGTERM: cleaning up");
			    break;
			case SIGQUIT:
			    CCE_SYSLOG("caught SIGTERM: cleaning up");
			    break;
		}
		/* no more connections */
		close(listenfd);

		/* exit cleanly */
		smd_exithandle(0);
	} else {
		CCE_SYSLOG("smd_sighandle: signal %d caught, but unknown", s);
	}
}

static void
smd_exithandle(int n)
{
	/* disconnect clients */
	signal(SIGCHLD, SIG_DFL);
	smd_kill_children();

	/* do some cleanup */
	unlink(CCESOCKET);

	codb_uninit();

	if (conf) {
		cce_conf_destroy(conf);
	}

	if (write_sem >= 0) {
		csem_destroy(write_sem);
	}

	free(progdir);

	DPRINTF(DBG_CCED, "smd_exithandle: goodbye, cruel world\n");

	CCE_SYSLOG("exiting");
	exit(n);
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
			CCE_SYSLOG("child (pid %d) exited", pid);
		} else {
			CCE_SYSLOG("smd_kill_children: wait() %s", strerror(errno));
		}
		children[i] = 0;
	}
}

static void
smd_do_hup(void)
{
	sigset_t ss;

	/* for now (maybe it will change later) handle a HUP by re-execing 
	 * myself.  This guarantees that all dynamic structures get
	 * re-initialized in all cases
	 */

	/* clear my own signal mask - POSIX says it gets inherited! */
	sigemptyset(&ss);
	sigprocmask(SIG_SETMASK, &ss, NULL);

	DPRINTF(DBG_CCED, "smd_do_hup: restarting myself\n");

	chdir(progdir);
	execv(progname, progargv);

	/* we only get here if something strange happens */
	CCE_SYSLOG("execv(%s): %m", progname);
	exit(42);
}

/*
 * code below here is only run by the smd_children
 * it is all single-threaded, and will be for the forseeable future, 
 * so global state is OK.
 */

/* a flag to signify if we are in CSCP right now */
static int in_cscp = 0;
static int time_to_exit = 0;

static int 
manage_session(int cli_fd, char *idstr, struct ucred *creds)
{
	int r;
	codb_handle *odbh;
	cce_ed *ed;
	struct sigaction oldhup;
	struct sigaction oldint;
	struct sigaction oldterm;
	struct sigaction oldquit;

	/* if we are forking */
	if (nfflag) {
		/* save the old term handler */
		sigaction(SIGHUP, NULL, &oldhup);
		sigaction(SIGINT, NULL, &oldint);
		sigaction(SIGTERM, NULL, &oldterm);
		sigaction(SIGQUIT, NULL, &oldquit);
	}

	/* smd_child: handle signals */
	signal(SIGPIPE, SIG_IGN);
	signal(SIGCHLD, SIG_DFL); /* POSIX says not to set it to SIG_IGN */
	signal(SIGHUP, smd_child_sighandle);
	signal(SIGINT, smd_child_sighandle);
	signal(SIGQUIT, smd_child_sighandle);
	signal(SIGTERM, smd_child_sighandle);

	odbh = codb_handle_new(CCEDBDIR, conf);
	if (!odbh) {
		CCE_SYSLOG("manage_session: codb_handle_new() failed");
		exit(-1);
	}

	/* root connections are magic */
	if (creds->uid == 0) {
		codb_handle_addflags(odbh, CODBF_ADMIN);
		CCE_SYSLOG("client %s has admin rights", idstr);
	}

	ed = cce_ed_new(conf);
	if (!ed) {
		CCE_SYSLOG("manage_session: cce_ed_new() failed");
		exit(-1);
	}

	/* the bulk of the work lives here */
	in_cscp = cli_fd;
	r = cscp_fsm(CTXT_CLIENT, cli_fd, idstr, odbh, ed, write_sem);
	in_cscp = 0;

	DPRINTF(DBG_SESSION, "client %s disconnected\n", idstr);

	/* cleanup */
	codb_handle_destroy(odbh);
	cce_ed_destroy(ed);

	close(cli_fd);

	if (nfflag && !time_to_exit) {
		/* restore the old term handler */
		sigaction(SIGHUP, &oldhup, NULL);
		sigaction(SIGINT, &oldint, NULL);
		sigaction(SIGTERM, &oldterm, NULL);
		sigaction(SIGQUIT, &oldquit, NULL);
		return r;
	} else {
		exit(r);
	}
}

static void
smd_child_sighandle(int s)
{
	if (s == SIGHUP || s == SIGTERM || s == SIGINT || s == SIGQUIT) {
		switch (s) {
			case SIGHUP:
				DPRINTF(DBG_CCED, "smd_child_sighandle: caught SIGHUP\n");
				if (nfflag) {
					atexit(smd_do_hup);
				}
				break;
			case SIGTERM:
				DPRINTF(DBG_CCED, "smd_child_sighandle: caught SIGTERM\n");
				break;
			case SIGINT:
				DPRINTF(DBG_CCED, "smd_child_sighandle: caught SIGINT\n");
				break;
			case SIGQUIT:
				DPRINTF(DBG_CCED, "smd_child_sighandle: caught SIGQUIT\n");
				break;
		}

		if (nfflag) {
			/* no more connections */
			close(listenfd);
		}

		if (in_cscp) {
			CCE_SYSLOG("client shutdown");
			cscp_shutdown();
			time_to_exit = 1;
		} else {
			exit(0);
		}
	} else {	
		CCE_SYSLOG("smd_child_sighandle: signal %d caught, but unknown", s);
		return;
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
