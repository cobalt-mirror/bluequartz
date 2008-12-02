/* $Id: ud_socket.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * $Id: ud_socket.c 259 2004-01-03 06:28:40Z shibuya $
 */

#include <cce_common.h>
#include <ud_socket.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <fcntl.h>

int 
ud_create_socket(const char *name)
{
	int fd;
	int r;
	struct sockaddr_un uds_addr;

	/* JIC */
	unlink(name);

	fd = socket(AF_UNIX, SOCK_STREAM, 0);
	if (fd < 0) {
		CCE_SYSLOG("ud_create_socket: socket() %s", strerror(errno));
		return fd;
	}

	/* setup address struct */
	memset(&uds_addr, 0, sizeof(uds_addr));
	uds_addr.sun_family = AF_UNIX;
	strcpy(uds_addr.sun_path, name);
	
	/* bind it to the socket */
	r = bind(fd, (struct sockaddr *)&uds_addr, sizeof(uds_addr));
	if (r < 0) {
		CCE_SYSLOG("ud_create_socket: bind() %s", strerror(errno));
		return r;
	}

	/* listen - allow 100 in the backlog */
	r = listen(fd, 100);
	if (r < 0) {
		CCE_SYSLOG("ud_create_socket: listen() %s", strerror(errno));
		return r;
	}

	chmod(name, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP|S_IROTH|S_IWOTH);

	DPRINTF(DBG_COMMON, 
		"ud_create_socket: listening on fd %d (%s)\n", fd, name);

	return fd;
}

int
ud_accept(int listenfd, struct ucred *cred)
{
	while (1) {
		int newsock = 0;
		struct sockaddr_un cliaddr;
		int len = sizeof(struct sockaddr_un);

		newsock = accept(listenfd, (struct sockaddr *)&cliaddr, &len);
		if (newsock < 0) {
			if (errno == EINTR) {
				continue; /* signal */
			}
		
			/* log and exit if accept() fails */
			CCE_SYSLOG("ud_accept: accept() %s", strerror(errno));
		}

		len = sizeof(struct ucred);
		getsockopt(newsock, SOL_SOCKET, SO_PEERCRED, cred, &len);

		return newsock;
	}
}

int
ud_connect(const char *name)
{
	int fd;
	int r;
	struct sockaddr_un addr;

	fd = socket(AF_UNIX, SOCK_STREAM, 0);
	if (fd < 0) {
		CCE_SYSLOG("ud_connect: socket() %s", strerror(errno));
		return fd;
	}

	memset(&addr, 0, sizeof(addr));
	addr.sun_family = AF_UNIX;
	sprintf(addr.sun_path, "%s", name);

	fcntl(fd, F_SETFL, O_NONBLOCK);

	r = connect(fd, (struct sockaddr *)&addr, sizeof(addr));
	if (r < 0) {
		close(fd);
		return r;
	}

	return fd;
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
