/* $Id: ccelib_internal.h,v 1.4 2001/08/10 22:23:09 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef CCELIB_INTERNAL_H__
#define CCELIB_INTERNAL_H__

#include "cce.h"
#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <sys/poll.h>

extern int cce_debug_indent_;

#define DINDENTN(n)		do { cce_debug_indent_ += (n); } while (0)
#define DINDENT()		DINDENTN(1)
#define DUNDENT()		DINDENTN(-1)
#define DEBPRINTF(fmt, args...) \
	do { \
		if (cce_debug_flag) { \
			int i; \
			fprintf(stderr, "CCEDBG [%s:%d]: ", \
				__FILE__, __LINE__); \
			for (i = 0; i < cce_debug_indent_; i++) { \
				fprintf(stderr, "    "); \
			} \
			fprintf(stderr, fmt, ##args); \
			fprintf(stderr, "\n"); \
		} \
	} while (0)

/* ccelib.c */
int cce_connect_(const char *sockname, struct cce_conn **ccep);
int cscp_line_read_(struct cce_conn *cce, struct cscp_line *cscp, int timeout);

/* safe.c */
int safe_poll(struct pollfd *fds, unsigned int nfds, int timout);
int safe_read(int fd, char *buf, int len);

#endif /* CCELIB_INTERNAL_H__ */
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