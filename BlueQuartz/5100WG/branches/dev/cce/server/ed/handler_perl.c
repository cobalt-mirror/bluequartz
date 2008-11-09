/* $Id: handler_perl.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * code for running handlers of the type "perl"
 */

#include "cce_common.h"
#include "cce_ed_internal.h"
#include "cce_ed.h"
#include "cce_conf.h"
#include <cscp_fsm.h>

#include <signal.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/wait.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <time.h>

#define PPERL	CCEBINDIR "pperl"

/*
 *  This is a bit hackish.  It could open the ud_socket itself, blah blah..
 *  calling pperl works, for now - code reuse, or something.
 */
int 
handler_perl(codb_handle *odb, cce_ed *ed, ed_handler_event *he,
	cscp_ctxt_t context)
{
	int fd[2];
	int ret = 0;
	pid_t perl_pid;
	char *h_prog;
	cce_conf_handler *handler;
	struct stat buf;

	handler = he->handler;
	h_prog = cce_conf_handler_data(handler);

	if (stat(h_prog, &buf)) {
		CCE_SYSLOG("handler_perl: %s does not exist", h_prog);
		return FSM_RET_FAIL;
	}

	/* 
	 * start or use the persistant perl process 
	 */ 
		
	/* open a socketpair */
	ret = socketpair(AF_UNIX, SOCK_STREAM, 0, fd);
	if (ret) {
		DPRINTF(DBG_ED, "socketpair: %s\n", strerror(errno));
	}

	/* fork for pperl process */
	perl_pid = fork();
	switch (perl_pid) {
		case -1:
			/* error */
			CCE_SYSLOG("fork: %s", strerror(errno));
			break;
		case 0:
			/* child process = fd[1] */
			close(fd[0]);

			/* do stdin & stdout, stderr is left alone */
			if (dup2(fd[1], STDIN_FILENO) != 0) {
				DPERROR(DBG_ED, "dup2()");
				exit(1);
			}
			if (dup2(fd[1], STDOUT_FILENO) != 1) {
				DPERROR(DBG_ED, "dup2()");
				exit(1);
			}
			close(fd[1]);

			/* start PPERL */
			if (execl(PPERL, PPERL, h_prog, NULL) < 0) {
				DPERROR(DBG_ED, "execl()\n");
				exit(1);
			}
			CCE_SYSLOG("handler_perl: execl(%s): %m", PPERL);
			exit(1);
			break;
	}
	
	/* fd[0] is the parent's */
	close(fd[1]);

	/* talk to the handler */
	ret = cscp_fsm(context, fd[0], h_prog, odb, ed, he);
	DPRINTF(DBG_ED, "handler_perl: cscp_fsm() returned %d\n", ret);

	/* cleanup */
	close(fd[0]);
	kill(perl_pid, SIGTERM);
	waitpid(perl_pid, NULL, 0);

	return ret;
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
