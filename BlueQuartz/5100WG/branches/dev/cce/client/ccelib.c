/* $Id: ccelib.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems, Inc.  All rights reserved. */
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <ctype.h>
#include <sys/types.h>
#include <sys/stat.h>

#include "cce.h"
#include "ccelib.h"
#include "ud_socket.h"

#define DEFAULT_UD_SOCKET	"/usr/sausalito/cced.socket"

#ifdef CCE_DEBUG_LIB
int cce_debug_indent_;
int cce_debug_flag;
#endif

cce_conn_t *
cce_conn_new(void)
{
	cce_conn_t *cce;

	DPRINTF("cce_conn_new()");
	DINDENT();

	cce = malloc(sizeof(*cce));
	if (!cce) {
		return NULL;
	}

	cce->state = 0;

	DUNDENT();

	return cce;
}

void
cce_conn_destroy(cce_conn_t *cce)
{
	DPRINTF("cce_conn_destroy()");
	DINDENT();

	free(cce);

	DUNDENT();
}

cce_err_t
cce_connect(cce_conn_t *cce)
{
	struct stat statbuf;
	int fd;

	DPRINTF("cce_connect(%p)", cce);
	DINDENT();

	if (!cce) {
		DUNDENT();
		return CCE_EINVAL;
	}

	if (stat(DEFAULT_UD_SOCKET, &statbuf)) {
		DUNDENT();
		return CCE_ENOENT;
	}

	fd = ud_connect(DEFAULT_UD_SOCKET);
	if (fd < 0) {
		cce_err_t r = CCE_EIO;

		if (errno == ECONNREFUSED) {
			r = CCE_ECONNREFUSED;
		}
		if (errno == ETIMEDOUT) {
			r = CCE_ETIMEDOUT;
		}
		if (errno == ENOMEM) {
			r = CCE_ENOMEM;
		}
		if (errno == EACCES) {
			r = CCE_EACCESS;
		}

		DUNDENT();
		return r;
	}

	/* seems like we've connected */
	cce->fd_to = cce->fd_from = fd;

	//FIXME: left off here
	/* parse the header */
	/* beware of EPIPE and ETIMEDOUT */

	DUNDENT();

	return CCE_OK;
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
