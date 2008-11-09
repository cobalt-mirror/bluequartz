/*
 * Headers for the mid-level commands and structures.
 *
 * Author: Harris Vaegan-Lloyd.
 */

#ifndef _CCE_CCE_H_
#define _CCE_CCE_H_ 1
#include "c6.h"
#include <glib.h>

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

int cce_commit_cmnd(cce_handle_t *handle);

GSList *cce_names_oid_cmnd(cce_handle_t *handle, cscp_oid_t oid);

GSList *cce_names_class_cmnd(cce_handle_t *handle, char *class);

GSList *cce_find_cmnd(cce_handle_t *handle, char *classname, 
	cce_props_t *props);

GSList *cce_find_sorted_cmnd(cce_handle_t *handle, char *classname, 
	cce_props_t *props, char *sortkey, int sorttype);

int cce_endkey_cmnd(cce_handle_t *handle);

int cce_bye_cmnd(cce_handle_t *handle);

cscp_oid_t cce_whoami_cmnd(cce_handle_t *handle);

/* This is a meta commnd, it simply starts up
 * the connection and fetches the CSCP version info */
int cce_connect_cmnd(cce_handle_t *handle, char *path);

/* Another meta command. This returns the last set of errors received. Useful
 * when dealing with APIs like PHP's. */
GSList *cce_last_errors_cmnd(cce_handle_t *handle);

/* Constructors and destructors for our utility objects */
cce_handle_t *cce_handle_new(void);
void cce_handle_destroy(cce_handle_t *handle);

cce_props_t *cce_props_new(void);
void cce_props_destroy(cce_props_t *props);

/* Functions for working with cce_rets */
/* FIXME: These functions are depreceated already. 
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

/*
 * The header file to be used by handlers.
 *
 * Author: Harris Vaegan-Lloyd
 */


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

#endif /* _CCE_H */
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
