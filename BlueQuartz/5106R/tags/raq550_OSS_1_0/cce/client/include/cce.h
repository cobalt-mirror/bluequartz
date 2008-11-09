/* $Id: cce.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * The CCE library interface
 */

#ifndef CCE_H__
#define CCE_H__ 1

/*
 * The following definitions are for the second version of the CCE library
 * Use these, if at all possible
 */
#include <string.h>
#include <errno.h>
#include <glib.h>

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


/*
 * The following definitions are for the first version of the CCE library
 * These will be deprecated
 */
#include "c6.h"

typedef struct cce_ret_struct cce_ret_t;
typedef struct cce_props_struct cce_props_t;
typedef struct cce_handle_struct cce_handle_t;

/* The error struct is a transperant one.. */
typedef struct cce_error_t {
	unsigned int code;
	cscp_oid_t oid;
	char *key;
	char *message;
} cce_error_t;

/* 
 * Commands with a direct mapping to a cscp_command 
 */

char *cce_auth_cmnd(cce_handle_t *handle, char *user, char *pass);
int cce_authkey_cmnd(cce_handle_t *handle, char *user, char *sessionid);
cscp_oid_t cce_create_cmnd(cce_handle_t *handle, char *class, 
	cce_props_t *props);
int cce_destroy_cmnd(cce_handle_t *handle, cscp_oid_t oid);
int cce_set_cmnd(cce_handle_t *handle, cscp_oid_t oid, char *namespace, 
	cce_props_t *props);
cce_props_t * cce_get_cmnd(cce_handle_t *handle, cscp_oid_t oid, 
	char *namespace);
int cce_begin_cmnd(cce_handle_t *handle);
int cce_commit_cmnd(cce_handle_t *handle);
GSList *cce_names_oid_cmnd(cce_handle_t *handle, cscp_oid_t oid);
GSList *cce_names_class_cmnd(cce_handle_t *handle, char *class);
GSList *cce_find_cmnd(cce_handle_t *handle, char *classname, 
	cce_props_t *props);
GSList *cce_find_sorted_cmnd(cce_handle_t *handle, char *classname, 
	cce_props_t *props, char *sortkey, int sorttype);
GSList *cce_findx_cmnd(cce_handle_t *handle, char *classname, 
	cce_props_t *props, cce_props_t *reprops, char *sorttype, 
	char *sortprop);
int cce_endkey_cmnd(cce_handle_t *handle);
int cce_bye_cmnd(cce_handle_t *handle);
cscp_oid_t cce_whoami_cmnd(cce_handle_t *handle);

/* This is a meta commnd, it simply starts up
 * the connection and fetches the CSCP version info */
int cce_connect_cmnd(cce_handle_t *handle, char *path);

/* If CCE is suspended, return the reason, otherwise NULL */
char *cce_suspended(cce_handle_t *handle);

/* if we are in rollback mode, this returns true */
int cce_is_rollback(cce_handle_t *cce);

/* Another meta command. This returns the last set of errors received. Useful
 * when dealing with APIs like PHP's. */
GSList *cce_last_errors_cmnd(cce_handle_t *handle);

/* this is a wrapper around the CSCP "ADMIN" command */
int cce_admin_cmnd(cce_handle_t *handle, char *command, char *argument);

/* Constructors and destructors for our utility objects */
cce_handle_t *cce_handle_new(void);
void cce_handle_destroy(cce_handle_t *handle);

cce_props_t *cce_props_new(void);
void cce_props_destroy(cce_props_t *props);

/* Functions for working with cce_rets */
/* FIXME: These functions are deprecated already. 
 * in the future all functions will return glib objects or plain strings rather
 * than using rets. However this will just be returning sub parts of rets to
 * the user */
int cce_ret_success(cce_ret_t *ret);
int cce_ret_next_int(cce_ret_t *ret);
char *cce_ret_next_str(cce_ret_t *ret);
cce_props_t *cce_ret_get_props(cce_ret_t *ret);
void cce_ret_rewind(cce_ret_t *ret);

/* Mark what kind of state the object represented by the hash is in. */
typedef enum {
	CCE_NONE = 0,
	CCE_MODIFIED,
	CCE_CREATED,
	CCE_DESTROYED
} cce_props_state_t;

/* Functions for working with properties. */
char *cce_props_get(cce_props_t *, char *key);
char *cce_props_get_new(cce_props_t *, char *key);
char *cce_props_get_old(cce_props_t *, char *key);
void cce_props_set(cce_props_t *, char *key, char *value);
void cce_props_set_old(cce_props_t *, char *key, char *value);
/* Rewind the pointer for the current key */
void cce_props_reinit(cce_props_t *);
/* Get the next key */
char *cce_props_nextkey(cce_props_t *);
/* count items */
int cce_props_count(cce_props_t *);

cce_props_state_t cce_props_state(cce_props_t *);
GSList *cce_props_changed(cce_props_t *);

/* Turn an amp delimeted string into a GSList */
GSList *cce_array_deserial(char *str);
/* Free the resulting string. */
void cce_list_destroy(GSList *list);

#ifdef DEBUG
/* Just to give users and easy way to check */
char *cce_error_serialise(cce_error_t *);
char *cce_props_serialise(cce_props_t *);
char *cce_ret_serialise(cce_ret_t *);
#endif /* DEBUG */

/* handlers can say good bye in three different ways.. */
typedef enum {
	CCE_SUCCESS = 0,
	CCE_FAIL,
	CCE_DEFER
} cce_handler_ret;

/*
 * Start up the cce connection to stdin/stout and grab the handler info
 * being feed to us.
 */
cscp_oid_t cce_connect_handler_cmnd( cce_handle_t *handle);

/*
 * Say goodbye nicely, tell cce how we finished, and a message if we failed
 */
int cce_bye_handler_cmnd(cce_handle_t *handle, cce_handler_ret status,
	char *message);

/*
 * Report bad data.
 */
int cce_bad_data_cmnd(cce_handle_t *handler, cscp_oid_t oid, char *namespace,
	char *key, char *reason);

#endif /* CCE_H__ */
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
