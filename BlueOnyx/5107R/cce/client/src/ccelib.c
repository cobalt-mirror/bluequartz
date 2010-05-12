/* $Id: ccelib.c,v 1.5 2001/08/10 22:23:09 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
#include <cce_common.h>
#include "ccelib_internal.h"
#include <stdio.h>
#include <unistd.h>
#include <fcntl.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <ctype.h>
#include <sys/time.h>
#include <sys/poll.h>
#include <ud_socket.h>

#define TMOUT_HEADER		5000  /* msecs */

int cce_debug_indent_;

/* static functions */
static int parse_cscp_header(struct cce_conn *cce);
static int read_line(int fd, char **bufp, int timeout);
static int ms_elapsed(struct timeval *t0, struct timeval *t1);

/*
 * initiate a connection to CCE on the named socket
 */
int
cce_connect_(const char *sockname, struct cce_conn **ccep)
{
	struct cce_conn *cce;
	int r;
	
	cce = malloc(sizeof(*cce));
	if (!cce) {
		DEBPRINTF("malloc(%ld): %s\n", (long)sizeof(*cce), strerror(ENOMEM));
		return -ENOMEM;
	}

	r = ud_connect(sockname);
	if (r < 0) {
		DEBPRINTF("ud_connect(%s): %s\n", sockname, strerror(ECONNREFUSED));
		free(cce);
		return -ECONNREFUSED;
	}
	cce->cc_fdin = cce->cc_fdout = r;

	r = parse_cscp_header(cce);
	if (r < 0) {
		DEBPRINTF("parse_cscp_header(%p): %s\n", cce, strerror(-r));
		close(cce->cc_fdin);
		free(cce);
		return r;
	}

	/* save the data */
	DEBPRINTF("new CCE: %p\n", cce);
	*ccep = cce;

	return 0;
}

/*
 * read a CSCP line and turn it into a struct cscp_line
 */
int
cscp_line_read_(struct cce_conn *cce, struct cscp_line *cscp, int timeout)
{
	char *buf;
	char *p;
	int msg;
	int r;
	struct timeval t0, t1;
	int timeleft = timeout;

	/* note the time at which we started */
	gettimeofday(&t0, NULL);

	/* read until we get a non-blank line */
	do {
		r = read_line(cce->cc_fdin, &buf, timeleft);
		if (r < 0) {
			DEBPRINTF("read_line(%d, %p, %d): %s\n", 
				cce->cc_fdin, &buf, timeleft, strerror(-r));
			return r;
		}
		gettimeofday(&t1, NULL);

		/* adjust timeleft */
		timeleft = timeout - ms_elapsed(&t0, &t1);
		if (timeleft < 0) {
			timeleft = 0;
		}
	} while (r == 0 && timeleft);
	
	/* did we time out? */
	if (!timeleft) {
		DEBPRINTF("!timeleft: %s\n", strerror(ETIMEDOUT));
		return -ETIMEDOUT;
	}

	/* figure out the cl_line and cl_msg */
	if (!isdigit(buf[0]) || !isdigit(buf[1]) || !isdigit(buf[2])) {
		/* CSCP always has the first three chars digits! */
		DEBPRINTF("!isdigit(%c|%c|%c): %s\n", 
			buf[0], buf[1], buf[2], strerror(EBADMSG));
		return -EBADMSG;
	}
	msg = (buf[0] - '0') * 100;
	msg += (buf[1] - '0') * 10;
	msg += (buf[2] - '0');
	DEBPRINTF("msg number = %d\n", msg);

	/* save the payload */
	p = buf + 4;
	switch (msg) {
		case CSCP_MSG_OK:
		case CSCP_MSG_FAIL:
		case CSCP_MSG_CREATE:
		case CSCP_MSG_DESTROY:
		case CSCP_MSG_READY:
		case CSCP_MSG_GOODBYE:
		case CSCP_MSG_NOMEM:
		case CSCP_MSG_NOTREADY:
		case CSCP_MSG_BADCMD:
		case CSCP_MSG_BADPARAMS:
		case CSCP_MSG_ONFIRE:
		case CSCP_MSG_SHUTDOWN:
			p = NULL;
			break;
		case CSCP_MSG_HEADER:
		case CSCP_MSG_DATA:
		case CSCP_MSG_NEWDATA:
		case CSCP_MSG_INFO:
		case CSCP_MSG_WARN:
			p += 5;
			break;
		case CSCP_MSG_EVENT:
		case CSCP_MSG_CLASS:
		case CSCP_MSG_ERROR:
			p += 6;
			break;
		case CSCP_MSG_OBJECT:
			p += 7;
			break;
		case CSCP_MSG_BADDATA:
			p += 9;
			break;
		case CSCP_MSG_NSPACE:
		case CSCP_MSG_SESSIONID:
			p += 10;
			break;
		case CSCP_MSG_UNKCLASS:
			p += 14;
			break;
		case CSCP_MSG_UNKOBJECT:
			p += 15;
			break;
		case CSCP_MSG_UNKNSPACE:
		case CSCP_MSG_PERMDENIED:
			p += 18;
			break;
		default: 
			return -EBADMSG;
	}
	/* make sure we're in bounds */
	if (p > (buf + strlen(buf))) {
		DEBPRINTF("out of bounds (%p > %p): %s\n", 
			p, (buf + strlen(buf)), strerror(EBADMSG));
		return -EBADMSG;
	}

	/* save the payload */
	if (p) {
		char *q;
		q = strdup(p);
		if (!q) {
			DEBPRINTF("strdup(%s): %s\n", p, strerror(ENOMEM));
			return -ENOMEM;
		}
		p = q;
	}

	/* success is imminent, store the data */
	cscp->cl_line = buf[0] - '0';
	cscp->cl_msg = msg;
	cscp->cl_data = p;

	return 0;
}




/* FIXME: this doesn't handle the hacky "max connections" error */
/* 
 * parse the (hopefully) spec-compliant CCE header 
 * returns:
 * 	0 on success
 * 	-EBADMSG if the message appears to be mangled
 */
static int
parse_cscp_header(struct cce_conn *cce)
{
	struct cscp_line cl;
	char *p, *q;
	int r;
	unsigned long maj, min;
	
	/* get the CSCP version number */
	r = cscp_line_read(cce, &cl, TMOUT_HEADER);
	if (r < 0) {
		return r;
	}
	maj = strtoul(cl.cl_data, &p, 0);
	if (p == cl.cl_data) {
		//FIXME: free data
		return -EBADMSG;
	}
	min = strtoul(p+1, &q, 0);
	if (q == p) {
		//FIXME: free data
		return -EBADMSG;
	}

	/* get a 200 READY message */

	return 0;
}

/* 
 * read a line (up to, but discarding '\n') in locally allocated memory
 * get rid of leading spaces
 * store the pointer in *bufp
 * NOTE: callers MUST NOT free() the buffer
 * returns:
 *	the number of characters stored on success
 * 	-ENOMEM if an allocation fails
 * 	-ETIMEDOUT on a timeout
 * 	-EPIPE if the connection closed
 * 	-EIO if any other IO error happens
 */
static int
read_line(int fd, char **bufp, int timeout)
{
	int n = 0;
	int r;
	struct timeval t0, t1;
	int timeleft = timeout;
	int done = 0;
	static char *buf;
	static int buflen;

	/* note the time at which we started */
	gettimeofday(&t0, NULL);

	/* clear any leftovers from the last call to this fn() */
	if (buf) {
		free(buf);
		buf = NULL;
		buflen = 0;
	}
	*bufp = NULL;
	
	/* let's play 'find the newline' */
	while (!done) {
		/* get or grow the buffer */
		if (buflen == 0) {
			/* minimum size */
			buf = malloc(32);
		} else {
			char *newbuf;
			newbuf = realloc(buf, buflen * 2);
			if (!newbuf) {
				free(buf);
			}
			buf = newbuf;
		}
		if (!buf) {
			return -ENOMEM;
		}

		/* fill the buffer */
		while (n < buflen && timeleft) {
			struct pollfd fds[1];

			/* poll for data */
			fds[0].fd = fd;
			fds[0].events = POLLIN;
			fds[0].revents = 0;

			/* poll can have lots of error cases */
			r = safe_poll(fds, 1, timeleft);
			if (r == 0) {
				return -ETIMEDOUT;
			}
			if (r < 0) {
				return -errno;
			}
			if (fds[0].revents & POLLHUP) {
				return -EPIPE;
			}
			if (fds[0].revents & (POLLERR | POLLNVAL)) {
				return -EIO;
			}

			/*
	 		 * whew!  Now that we have data to read...
	 		 */

			/* read one character at a time */
			r = safe_read(fd, buf + n, 1);
			if (r < 0) {
				return -errno;
			}
	
			if (buf[n] == '\n') {
				done = 1;
				break;
			} else if (n > 0 || !isspace((int)buf[n])) {
				/* 
				 * only move ahead if we have hit a first
				 * non-whitespace character
				 */
				n++;
			}

			/* adjust timeleft */
			gettimeofday(&t1, NULL);
			timeleft = timeout - ms_elapsed(&t0, &t1);
			if (timeleft < 0) {
				timeleft = 0;
			}
			
		}
	}

	buf[n] = '\0';
	*bufp = buf;
	return n;
}

/* return the number of milliseconds elapsed between two struct timevals */
static int
ms_elapsed(struct timeval *t0, struct timeval *t1)
{
	int msecs;

	/* the elapsed seconds + elapsed msecs, handles wraps */
	msecs = (t1->tv_sec - t0->tv_sec) * 1000;
	msecs += (t1->tv_usec - t0->tv_usec) / 1000;
	msecs += ((t1->tv_usec - t0->tv_usec) % 1000 >= 500) ? 1 : 0;

	return msecs;
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
