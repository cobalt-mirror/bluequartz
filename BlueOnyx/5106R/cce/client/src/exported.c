/* $Id: exported.c,v 1.4 2001/08/10 22:23:09 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
#include <cce_common.h>
#include "ccelib_internal.h"
#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <cce_paths.h>

int cce_debug_flag = 0;

/* 
 * cce_connect_to()
 */
struct cce_conn *
cce_connect_to(const char *sockname)
{
	struct cce_conn *cce;
	int r;

	DEBPRINTF("cce_connect_to(%s)", sockname ? sockname : "NULL");
	DINDENT();

	if (!sockname) {
		sockname = CCESOCKET;
	}
	
	r = cce_connect_(sockname, &cce);
	if (r < 0) {
		errno = -r;
		cce = NULL;
	}

	DEBPRINTF("done");
	DUNDENT();

	return cce;
}

/* 
 * cce_connect()
 */
struct cce_conn *
cce_connect(void)
{
	struct cce_conn *cce;
	int r;

	DEBPRINTF("cce_connect()");
	DINDENT();

	r = cce_connect_(CCESOCKET, &cce);
	if (r < 0) {
		errno = -r;
		cce = NULL;
	}

	DEBPRINTF("done");
	DUNDENT();

	return cce;
}

/*
 * cscp_line_read() 
 */
int
cscp_line_read(struct cce_conn *cce, struct cscp_line *cscp, int timeout)
{
	int r;

	DEBPRINTF("cscp_line_read(%p, %p, %d)", cce, cscp, timeout);
	DINDENT();

	r = cscp_line_read_(cce, cscp, timeout);
	if (r < 0) {
		errno = -r;
		r = -1;
	}

	DEBPRINTF("done");
	DUNDENT();

	return r;
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
