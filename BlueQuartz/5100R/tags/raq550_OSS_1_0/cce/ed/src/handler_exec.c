/* $Id: handler_exec.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * code for running handlers of the type "exec"
 * 
 * aka "Thank you W. Richard Stevens!"
 */

#include <cce_common.h>
#include "cce_ed_internal.h"
#include <cce_ed.h>
#include <cce_conf.h>
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

/* FIXME: we should try to clean this up and optimize it */
int 
handler_exec(codb_handle *odb, cce_ed *ed, ed_handler_event *he,
	cscp_ctxt_t context)
{
	int fd[2];
	int pid;
	int status;
	int sigsent = 0;
	int ret = 0;
	char *h_prog;
	struct stat buf;
	char *dirsep;

	h_prog = cce_conf_handler_data(he->handler);

	if (stat(h_prog, &buf)) {
		CCE_SYSLOG("handler_exec: %s does not exist", h_prog);
		return FSM_RET_FAIL;
	}

	if (!(buf.st_mode & (S_IXUSR | S_IXGRP | S_IXOTH))) {
		CCE_SYSLOG("handler_exec: %s is not executable", h_prog);
		return FSM_RET_FAIL;
	}

	/* open socketpair */
	ret = socketpair(AF_UNIX, SOCK_STREAM, 0, fd);
	if (ret) {
		DPRINTF(DBG_ED, "socketpair: %s\n", strerror(errno));
	}

	/* fork child process */
	pid = fork();
	switch (pid) {
		case -1:
			/* error */
			DPRINTF(DBG_ED, "fork: %s\n", strerror(errno));
			break;
		case 0:
			/* I am the child process, I get fd[1] */
			close(fd[0]);
	
			/* re-bind stdin, stdout, stderr */
			if (fd[1] != STDIN_FILENO) {
				if (dup2(fd[1], STDIN_FILENO) != STDIN_FILENO) {
					DPERROR(DBG_ED, "handler_exec: dup2()");
					exit(1);
				}
			}
			if (fd[1] != STDOUT_FILENO) {
				if (dup2(fd[1], STDOUT_FILENO) != STDOUT_FILENO) {
					DPERROR(DBG_ED, "handler_exec: dup2()");
					exit(1);
				}
			}
			close(fd[1]);
			
			/* chdir (child only) if a dir is specified */
			dirsep = strrchr(h_prog, '/');
			if (dirsep) {
				*dirsep = '\0';
				DPRINTF(DBG_ED, "handler_exec: chdir(%s)\n", h_prog);
				chdir(h_prog);
				*dirsep = '/';
			}
		
			/* execl */
			if (execl(dirsep+1, dirsep+1, NULL) < 0) {
				DPERROR(DBG_ED, "handler_exec: execl(%s)", dirsep+1);
				exit(1);
			}
			CCE_SYSLOG("handler_exec: execl(%s): %m",
				h_prog);
			exit(1);
			/* end of child process */
			break;
		default:
	}

	/* I am the big man, I use fd[0], huh! */
	close(fd[1]);

	/* talk to the handler */
	ret = cscp_fsm(context, fd[0], h_prog, odb, ed, he);
	DPRINTF(DBG_ED, "handler_exec: cscp_fsm() returned %d\n", ret);

	/* close socket to child */
	close(fd[0]);
	/* clean up child */
	/* If we hang here, it's no worse than hanging anywhere else in
	 * the whole handler */
	waitpid(pid, &status, 0);
	
	/* if we SUCCEED, but exited badly, or were otherwise killed... */
	if (WIFEXITED(status) && WEXITSTATUS(status)) {
		if (ret == FSM_RET_SUCCESS) {
			ret = FSM_RET_FAIL;
		}
		CCE_SYSLOG("-- handler returned %d", 
			WEXITSTATUS(status));
	} else if (WIFSIGNALED(status) && WTERMSIG(status) != sigsent) {
		if (ret == FSM_RET_SUCCESS) {
			ret = FSM_RET_FAIL;
		}
		CCE_SYSLOG("-- handler terminated with signal %d",
			WTERMSIG(status));
	}

	return ret;
}

/* eof */
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
