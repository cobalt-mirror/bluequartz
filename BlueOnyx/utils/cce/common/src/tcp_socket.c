/* $Id: tcp_socket.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * $Id: tcp_socket.c 259 2004-01-03 06:28:40Z shibuya $
 */

#include <cce_common.h>
#include <tcp_socket.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <netinet/in.h>

int 
tcp_create_socket(int port)
{
	int fd;
	int r;
	struct sockaddr_in addr;
	int val;

	fd = socket(AF_INET, SOCK_STREAM, 0);
	if (fd < 0) {
		CCE_SYSLOG("tcp_create_socket: socket() %s", strerror(errno));
		return fd;
	}

	/* set socket options */
	val = 1;
	r = setsockopt(fd, SOL_SOCKET, SO_REUSEADDR, &val, sizeof(val));
	if (r < 0) {
		CCE_SYSLOG("tcp_create_socket: setsockopt(SO_REUSEADDR) %s", strerror(errno));
		return r;
	}
	r = setsockopt(fd, SOL_SOCKET, SO_KEEPALIVE, &val, sizeof(val));
	if (r < 0) {
		CCE_SYSLOG("tcp_create_socket: setsockopt(SO_KEEPALIVE) %s", strerror(errno));
		return r;
	}

	/* setup address struct */
	memset(&addr, 0, sizeof(addr));
	addr.sin_family = AF_INET;
	addr.sin_addr.s_addr = htonl(INADDR_ANY);
	addr.sin_port = htons(port);
	
	/* bind it to the socket */
	r = bind(fd, (struct sockaddr *)&addr, sizeof(addr));
	if (r < 0) {
		CCE_SYSLOG("tcp_create_socket: bind() %s", strerror(errno));
		return r;
	}

	/* listen - allow 5 in the backlog */
	r = listen(fd, 5);
	if (r < 0) {
		CCE_SYSLOG("tcp_create_socket: listen() %s", strerror(errno));
		return r;
	}

	DPRINTF(DBG_COMMON, 
		"tcp_create_socket: listening on fd %d (port %d)\n", fd, port);

	return fd;
}

int
tcp_accept(int listenfd, struct sockaddr_in *cliaddr)
{
	while (1) {
		int newsock = 0;
		int len;

		newsock = accept(listenfd, (struct sockaddr *)cliaddr, &len);
		if (newsock < 0) {
			if (errno == EINTR) {
				continue; /* signal */
			}
		
			/* log and exit if accept() fails */
			CCE_SYSLOG("tcp_accept: accept() %s", strerror(errno));
		}

		return newsock;
	}
}

int
tcp_connect(char *ip, int port)
{
	int fd;
	int r;
	struct sockaddr_in addr;

	memset(&addr, 0, sizeof(addr));
	addr.sin_family = AF_INET;
	addr.sin_addr.s_addr = inet_addr(ip);
	addr.sin_port = htons(port);
	
	fd = socket(AF_INET, SOCK_STREAM, 0);
	if (fd < 0) {
		CCE_SYSLOG("tcp_connect: socket() %s", strerror(errno));
		return fd;
	}

	r = connect(fd, (struct sockaddr *)&addr, sizeof(addr));
	if (r < 0) {
		CCE_SYSLOG("tcp_connect: connect() %s", strerror(errno));
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
