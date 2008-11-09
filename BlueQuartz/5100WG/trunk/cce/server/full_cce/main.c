/*
 * $Id: main.c 3 2003-07-17 15:19:15Z will $
 *
 * main.c
 * this is the main routine and watcher daemon for cced
 *
 * author: Tim Hockin <thockin@cobalt.com>
 */

#include <stdio.h>
#include <stdlib.h>
#include <signal.h>
#include <unistd.h>
#include <errno.h>
#include <syslog.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <cced.h>

/* debugging */
#ifdef DEBUG
#undef DPRINTF
#undef DPERROR
#define DPRINTF(f, a...)	fprintf(stderr, "CCED DEBUG: " f, ##a);
#define DPERROR(f)		perror("CCED DEBUG: " f);
#endif

/* for error messages */
#define EPRINTF(f, a...)	do { 							\
					  fprintf(stderr, "cced: " f, ##a); 	\
					  syslog(LOG_ERR, f, ##a);			\
					} while (0);
#define EPERROR(f) 		do {							\
					  perror("cced: " f);				\
					  syslog(LOG_ERR, f " : %s", strerror(errno));\
					} while (0);


/* functions used internally */
static void sighandle(int s);
static void exithandle(void);
static void kill_children(void);
static int daemonize(void);
static void start_threads(void);
static int start_one_thread(int *pid, void (*fn)(void));
static void handle_cmdline(int argc, char *argv[]);
static void usage(void);
static void version(void);


/* these get used outside main by sighandle()... */
static int smd_pid;
static int txnq_pid;
static int ed_pid;

static char *progname;
static int ndflag = 1;
static int smflag = 0;
static int txnqflag = 0;
static int edflag = 0;


struct cmdline_opt {
	char *opt;		/* flag as passed on cmdline */
	int *flag;		/* address to store flag result */
	char *help;		/* for usage() */
};
static struct cmdline_opt opts[] = {
	{ "-nd    ", &ndflag, "do not daemonize" },
	{ "-sm    ", &smflag, "immediately become the Session Manager" },
	{ "-txnq  ", &txnqflag, "immediately become the Transaction Queue" },
	{ "-ed    ", &edflag, "immediately become the Event Dispatcher" },
	{ NULL, NULL, NULL }
};

/*
 * This is the 'watcher' daemon - the parent and Borg collective.  As this
 * is the hive mind, it controls all other processes - kill this, and they
 * should all die.  Kill one, and this should restart it.
 */
int
main(int argc, char *argv[])
{
	handle_cmdline(argc, argv);
	
	/* daemonize this process */
	if (!ndflag && daemonize()) {
		EPRINTF("main: daemonize() failed\n");
		exit(-1);
	}

	/* prep syslog for the watched daemon */
	OPENLOG("cced");
	syslog(LOG_NOTICE, "starting up (pid %d)", getpid());
	DPRINTF("main: starting up (pid %d)\n", getpid());
	
	/* here begins the system proper */
	start_threads();

	/* handle signals */
	signal(SIGHUP, sighandle);
	signal(SIGINT, sighandle);
	signal(SIGTERM, sighandle);
	signal(SIGQUIT, sighandle);

	/* main loop - monitor children */
	while (1) {
		int status;
		int pid;
		
		pid = wait(&status);

		if (pid == smd_pid) {
			syslog(LOG_NOTICE, "smd (pid %d) died - restarting", pid);
			DPRINTF("main: restarting smd\n");
			start_one_thread(&smd_pid, start_smd_thread);
		} else if (pid == txnq_pid) {
			syslog(LOG_NOTICE, "txnq (pid %d) died - restarting", pid);
			DPRINTF("main: restarting txnq\n");
			start_one_thread(&txnq_pid, start_txnq_thread);
		} else if (pid == ed_pid) {
			syslog(LOG_NOTICE, "ed (pid %d) died - restarting", pid);
			DPRINTF("main: restarting ed\n");
			start_one_thread(&ed_pid, start_ed_thread);
		} else {
			EPRINTF("main: wait() returned %d\n", pid);
			exit(-1);
		}
	}

	return 0;
}

static void
start_threads(void)
{
	int r = 0;

	DPRINTF("start_threads: starting...\n");

	/* handle any "immediately become" cmdline opts */
	if (smflag) {
		start_smd_thread();
	} else if (txnqflag) {
		start_txnq_thread();
	} else if (edflag) {
		start_ed_thread();
	}
		
	smd_pid = txnq_pid = ed_pid = 0;

	/* start all three */
	r = (start_one_thread(&smd_pid, start_smd_thread)
		|| start_one_thread(&txnq_pid, start_txnq_thread) 
		|| start_one_thread(&ed_pid, start_ed_thread));
	if (r) {
		EPRINTF("start_threads: error starting threads\n");
	} else {
		syslog(LOG_NOTICE, "daemon threads started");
		DPRINTF("start_threads: threads started\n");
	}
}

static int 
start_one_thread(int *pid, void (*fn)(void))
{
	*pid = fork();
	switch(*pid) {
		case -1:
			/* damn */
			EPERROR("start_one_thread: fork()");
			return -1;
			break;
		case 0:
			/* child */
			closelog();
			/* start thread */
			fn();
			EPRINTF("start_one_thread: returned from thread\n");
			_exit(-1);
			break;
		default:
			/* parent */
			DPRINTF("created thread (pid %d)\n", *pid);
			syslog(LOG_NOTICE, "created thread (pid %d)", *pid);
			return 0;
	}
}

static int
daemonize(void)
{
	int pid;

	/* fork and keep child */
	pid = fork();
	switch(pid) {
		case -1:
			/* damn */
			EPERROR("daemonize: fork()");
			return -1;
			break;
		case 0:
			/* child will continue below */
			break;
		default:
			/* parent should die */
			exit(0);
	}

	/* become session leader */
	setsid();

	/* clear up */
	chdir("/");
	umask(0);

	return 0;
}


static void
sighandle(int s)
{
	/* do we know about this signal? */
	if (s == SIGHUP || s == SIGINT || s == SIGTERM || s == SIGQUIT) {
		syslog(LOG_NOTICE, "caught signal %d", s);
		DPRINTF("sighandle: caught signal %d\n", s);
	
		if (s == SIGHUP) {
			/* kill all children */
			kill_children();
			DPRINTF("sighandle: all children killed\n");

			/* restart them */
			start_threads();
		} else {
			/* exit cleanly */
			exithandle();
			exit(0);
		}
	} else {
		EPRINTF("sighandle: signal %d caught, but unknown\n", s);
	}
}


static void
exithandle(void)
{
	/* kill 'em */
	kill_children();

	syslog(LOG_NOTICE, "exiting");
	DPRINTF("exithandle: all children killed - the end is here\n");
}

static void
kill_children(void)
{
	int pid;

	/* kill() and wait() for any children that are still alive */
	if (smd_pid > 0) {
		DPRINTF("kill_children: killing smd\n");
		kill(smd_pid, SIGTERM);
		pid = wait(NULL);
		if (pid > 0) {
			syslog(LOG_NOTICE, "killed and reaped smd (pid %d)", pid);
		} else {
			EPRINTF("kill_children: wait() returned %d (%s)\n", pid,
				strerror(errno));
		}
		smd_pid = 0;
	}

	if (txnq_pid > 0) {
		DPRINTF("kill_children: killing txnq\n");
		kill(txnq_pid, SIGTERM);
		pid = wait(NULL);
		if (pid > 0) {
			syslog(LOG_NOTICE, "killed and reaped txnq (pid %d)", pid);
		} else {
			EPRINTF("kill_children: wait() returned %d (%s)\n", pid,
				strerror(errno));
		}
		txnq_pid = 0;
	}

	if (ed_pid > 0) {
		DPRINTF("kill_children: killing ed\n");
		kill(ed_pid, SIGTERM);
		pid = wait(NULL);
		if (pid > 0) {
			syslog(LOG_NOTICE, "killed and reaped ed (pid %d)", pid);
		} else {
			EPRINTF("kill_children: wait() returned %d (%s)\n", pid,
				strerror(errno));
		}
		ed_pid = 0;
	}
}

static void
handle_cmdline(int argc, char *argv[])
{
	int i = 0;

	progname = argv[0];
	argc--; argv++;

	/* handle cmdline */
	while (argc) {
		if (!strcmp(argv[0], "-v")) {
			version();
			exit(0);
		}

		while (opts[i].opt) {
			if (!strncmp(argv[0], opts[i].opt, strlen(argv[0]))) {
				*(opts[i].flag) = 1;
				break;
			}
			i++;
		}

		if (!opts[i].opt) {
			/* option not known */
			usage();
			exit(-1);
		}

		/* next item */
		argc--; argv++;
	}
}

static void 
usage(void)
{
	int i = 0;

	version();
	
	fprintf(stderr, "usage: %s [options]\n", progname);
	fprintf(stderr, "  options:\n");
	fprintf(stderr, "    -v     \tdisplay version info and exit\n");
	
	while (opts[i].opt) {
		fprintf(stderr, "    %s\t%s\n", opts[i].opt, opts[i].help);
		i++;
	}
	
	fprintf(stderr, "\n");
}

static void
version(void)
{
	fprintf(stderr, "CCEd version %s, "
		"Copyright (c) Cobalt Networks, 2000\n", VERSION);
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
