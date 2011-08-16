/* $Id: ccelib.h,v 1.4 2001/08/10 22:23:09 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef CCE_LIB_H__
#define CCE_LIB_H__

#include <string.h>
#include <errno.h>

/* the default name for the CCE socket */
#define CCE_SOCKET_NAME		"/usr/sausalito/cced.socket"

/* defaults to zero; set to non-zero to enable debug messages */
extern int cce_debug_flag;

/* the fundamental structure of the library: a CCE connection */
struct cce_conn {
	/* the fd's are separate because of handlers */
	int cc_fdin;	
	int cc_fdout;
	/* the CSCP version number: 0.101 > 0.50 */
	int cc_vmaj;
	int cc_vmin;
};

/* CSCP line classes, as defined in the CSCP spec */
enum cscp_line_type {
	CSCP_LINE_INFO = 1,
	CSCP_LINE_SUCCESS = 2,
	CSCP_LINE_WARN = 3,
	CSCP_LINE_FAIL = 4,
	CSCP_LINE_SERVER = 9,
};

/* CSCP message numbers, as defined in the CSCP spec */
enum cscp_msg_type {
	CSCP_MSG_HEADER = 100,
	CSCP_MSG_EVENT = 101,
	CSCP_MSG_DATA = 102,
	CSCP_MSG_NEWDATA = 103,
	CSCP_MSG_OBJECT = 104,
	CSCP_MSG_NSPACE = 105,
	CSCP_MSG_INFO = 106,
	CSCP_MSG_CREATE = 107,
	CSCP_MSG_DESTROY = 108,
	CSCP_MSG_SESSIONID = 109,
	CSCP_MSG_CLASS = 110,
	CSCP_MSG_READY = 200,
	CSCP_MSG_OK = 201,
	CSCP_MSG_GOODBYE = 202,
	CSCP_MSG_UNKOBJECT = 300,
	CSCP_MSG_UNKCLASS = 301,
	CSCP_MSG_BADDATA = 302,
	CSCP_MSG_UNKNSPACE = 303,
	CSCP_MSG_PERMDENIED = 304,
	CSCP_MSG_WARN = 305,
	CSCP_MSG_ERROR = 306,
	CSCP_MSG_NOMEM = 307,
	CSCP_MSG_NOTREADY = 400,
	CSCP_MSG_FAIL = 401,
	CSCP_MSG_BADCMD = 402,
	CSCP_MSG_BADPARAMS = 403,
	CSCP_MSG_SHUTDOWN = 998,
	CSCP_MSG_ONFIRE = 999,
};

/* a single line of CSCP data */
struct cscp_line {
	enum cscp_line_type cl_line;
	enum cscp_msg_type cl_msg;
	char *cl_data;
};
	

/**
 * @Function: cce_connect()
 * @Description: initiates a connection to CCE
 * @Returns:
 * 	@: A pointer to a struct cce_conn on success
 * 	@: NULL on failure
 * @Errnos:
 *	@: ENOMEM: a memory allocation failed
 * 	@: ECONNREFUSED: the connection was not accepted
 * 	@: ETIMEDOUT: the connection timed out while reading
 * 	@: EPIPE: the connection was closed while reading
 * 	@: EIO: an unknown error occurred while reading
 */
struct cce_conn *cce_connect(void);

/**
 * @Function: cce_connect_to()
 * @Description: initiates a connection to CCE on a named socket
 * @Param: char *sockname
 * @Description: the name of the UNIX domain socket on which to connect, or \
 * 	NULL for default
 * @Returns:
 * 	@: A pointer to a struct cce_conn on success
 * 	@: NULL on failure
 * @Errnos:
 *	@: ENOMEM: a memory allocation failed
 * 	@: ECONNREFUSED: the connection was not accepted
 * 	@: ETIMEDOUT: the connection timed out while reading
 * 	@: EPIPE: the connection was closed while reading
 * 	@: EIO: an unknown error occurred while reading
 */
struct cce_conn *cce_connect_to(const char *sockname);

/**
 * @Function: cscp_read_line()
 * @Description: read a line and turn it into a struct cscp_line
 * @Param: struct cce_conn *cce
 * @Description: a pointer to the current CCE connection
 * @Param: struct cscp_line *cscp
 * @Description: a pointer to the CSCP line structure to populate
 * @Param: int timeout
 * @Description: the maximum timeout in milliseconds, or negative for no \
 * 	timeout
 * @Returns:
 * 	@: 0 on success
 * 	@: -1 on failure
 * @Errnos:
 *	@: ENOMEM: a memory allocation failed
 * 	@: ECONNREFUSED: the connection was not accepted
 * 	@: ETIMEDOUT: the connection timed out while reading
 * 	@: EPIPE: the connection was closed while reading
 * 	@: EIO: an unknown error occurred while reading
 */
/*
 * 	any errors from read_line()
 * 	-EBADMSG if the line read is not CSCP, or is malformed
 * 	-ETIMEDOUT if the timeout expired, even with a partial line
 */
int cscp_line_read(struct cce_conn *cce, struct cscp_line *cscp, int timeout);

#endif /* CCE_LIB_H__ */
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
