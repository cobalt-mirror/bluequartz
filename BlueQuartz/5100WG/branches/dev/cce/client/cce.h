/* $Id: cce.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems Inc.  All rights reserved. */

/*
 * This file is the exported API definition for the CCE client library v1.0
 */

#ifndef CCE_H__
#define CCE_H__

/* an object identifier */
typedef unsigned int cce_oid_t;

/* a "BAD DATA" message */
typedef struct cce_baddata_t {
	cce_oid_t oid;
	char *key;
	char *value;
} cce_baddata_t;

/* the parameter type for CCE_CMD_BYE */
typedef enum cce_bye_t {
	CCE_BYE_NONE = 0,
	CCE_BYE_SUCCESS,
	CCE_BYE_FAIL,
	CCE_BYE_DEFER,
} cce_bye_t;

/* all the CCE commands */
typedef enum cce_cmd_t {
	CCE_CMD_NONE = 0,
	CCE_CMD_ADMIN_DEBUG,
	CCE_CMD_ADMIN_RESUME,
	CCE_CMD_ADMIN_SUSPEND,
	CCE_CMD_AUTH,
	CCE_CMD_AUTHKEY,
	CCE_CMD_BADDATA,
	CCE_CMD_BEGIN,
	CCE_CMD_BYE,
	CCE_CMD_CLASSES,
	CCE_CMD_COMMIT,
	CCE_CMD_CREATE,
	CCE_CMD_DESTROY,
	CCE_CMD_ENDKEY,
	CCE_CMD_FIND,
	CCE_CMD_GET,
	CCE_CMD_INFO,
	CCE_CMD_NAMES,
	CCE_CMD_SCHEMA,
	CCE_CMD_SET,
	CCE_CMD_VALIDATE,
	CCE_CMD_WARN,
	CCE_CMD_WHOAMI,
} cce_cmd_t;

/* an EVENT in the CSCP header */
typedef struct cce_event_t {
	cce_oid_t oid;
	char *event;
} cce_event_t;

/* a CCE connection handle - needed for cce_command */
typedef struct cce_conn_t {
	int state;           /* connection state - used internally */
	int fd_to;           /* connection file descriptors */
	int fd_from;
	int cscp_vmaj;       /* the CSCP version number: 0.101 > 0.50 */
	int cscp_vmin;
	cce_event_t *events; /* array of events (for handlers) */
	char *suspended;     /* are we suspended? why? */
	int rollback;        /* are we in a rollback? */
} cce_conn_t;

/* all possible messages returned from CCE */
typedef enum cce_msg_t {
	CCE_MSG_CSCP = 100,
	CCE_MSG_EVENT = 101,
	CCE_MSG_DATA = 102,
	CCE_MSG_NEWDATA = 103,
	CCE_MSG_OBJECT = 104,
	CCE_MSG_NAMESPACE = 105,
	CCE_MSG_INFO = 106,
	CCE_MSG_CREATED = 107,
	CCE_MSG_DESTROYED = 108,
	CCE_MSG_SESSIONID = 109,
	CCE_MSG_CLASS = 110,
	CCE_MSG_ROLLBACK = 111,
	CCE_MSG_HELP = 112,
	CCE_MSG_SCHEMA = 113,

	CCE_MSG_READY = 200,
	CCE_MSG_OK = 201,
	CCE_MSG_GOODBYE = 202,

	CCE_MSG_UNKOBJECT = 300,
	CCE_MSG_UNKCLASS = 301,
	CCE_MSG_BADDATA = 302,
	CCE_MSG_UNKNAMESPACE = 303,
	CCE_MSG_PERMDENIED = 304,
	CCE_MSG_WARN = 305,
	CCE_MSG_ERROR = 306,
	CCE_MSG_NOMEM = 307,
	CCE_MSG_BADREGEX = 308,
	CCE_MSG_SUSPENDED = 309,
	CCE_MSG_DEPRECATED = 310,
	CCE_MSG_UNKPROP = 311,
	CCE_MSG_UNKTYPE = 312,
	CCE_MSG_UNKSCHEMA = 313,
	CCE_MSG_SYNTAXERR = 314,
	CCE_MSG_CONFLICT = 315,

	CCE_MSG_NOTREADY = 400,
	CCE_MSG_FAIL = 401,
	CCE_MSG_BADCMD = 402,
	CCE_MSG_BADPARAMS = 403,

	CCE_MSG_SHUTDOWN = 998,
	CCE_MSG_BADNOMEM = 999,
} cce_msg_t;

/* a raw CSCP message */
typedef struct cce_cscp_t {
	cce_msg_t msgid;
	char **payload;
} cce_cscp_t;

/* the return status for CCE functions
 * note that zero is not included - this is to discourage the use of this 
 * type as a boolean value
 */
typedef enum cce_err_t {
	CCE_OK = 1,	/* operation succeeded */
	CCE_FAIL,	/* operation received a 400 or 900 from CCE */
	CCE_EINVAL,
	CCE_ENOMEM,
	CCE_ENOENT,
	CCE_ECONNREFUSED,
	CCE_ETIMEDOUT,
	CCE_EPIPE,
	CCE_EBADF,
	CCE_EACCESS,
	CCE_EIO,
} cce_err_t;

/* a single argument to CCE_CMD_GET */
typedef struct cce_get_t {
	cce_oid_t oid;
	char *property;
} cce_get_t;

/*
 * An opaque type for storing/accessing property->value hashes
 * Access the data through cce_props_*() functions
 */
struct cce_props_t;
typedef struct cce_props_t cce_props_t;

/* the parameters to all cce_cmd_t value for cce_command() */
typedef union cce_params_t {
	struct {
		unsigned long value;
	} admin_debug;
	struct {
		char *reason;
	} admin_suspend;
	struct {
		char *username;
		char *password;
	} auth;
	struct {
		char *username;
		char *sessionkey;
	} authkey;
	struct cce_baddata_t baddata;
	struct {
		cce_bye_t status;
	} bye;
	struct {
		char *classname;
		cce_props_t *props;
	} create;
	struct {
		cce_oid_t oid;
	} destroy;
	struct {
		char *classname;
		cce_props_t *exact_props;
		cce_props_t *regex_props;
		char *sorttype;
		char *sortprop;
	} find;
	struct {
		int nrequests;
		cce_get_t *requests;
	} get;
	struct {
		char *message;
	} info;
	struct {
		char *classname;
		cce_oid_t oid;
	} names;
	struct {
		char *schema;
	} schema_add;
	struct {
		char *schema;
	} schema_remove;
	struct {
		cce_oid_t oid;
		char *namespace;
		cce_props_t *props;
	} set;
	struct {
		enum {VALID_PROPERTY, VALID_TYPEDEF} type;
		char *target;
		char *value;
	} validate;
	struct {
		char *message;
	} warn;
} cce_params_t;

/* the response to a cce_command() */
typedef struct cce_resp_t {
	cce_cscp_t *status;     /* msg num of the closing status msg */
	char *sessionkey;       /* 109 SESSIONID */
	cce_oid_t *oids;        /* 104 OBJECT - terminated by -1 */
	cce_props_t *data_old;  /* 102 DATA */
	cce_props_t *data_new;  /* 103 DATA */
	char **names;           /* 105 NAMESPACE */
	char **classes;         /* 110 CLASS */
	char **schemas;         /* 113 SCHEMAS */
	char **info;            /* 106 INFO */
	char **warn;            /* 305 WARN */
	cce_baddata_t **baddata;/* 302 BAD DATA */
	char **permdenied;      /* 304 PERMISSION DENIED */
	char **deprecated;      /* 310 DEPRECATED */
	char **help_cmds;       /* 112 HELP */
	char **help_text;       /* 112 HELP */
	cce_cscp_t **emergency; /* 9?? */
	cce_cscp_t **warnings;  /* 3xx others */
} cce_resp_t;


/* allocate and prepare a cce_conn_t structure for use */
cce_conn_t *cce_conn_new(void);

/* de-allocate a cce_conn_t structure */
void cce_conn_destroy(cce_conn_t *cce);

/* initiate a connection to CCE */
cce_err_t cce_connect(cce_conn_t *cce);

/* initiate a connection to CCE as a handler */
cce_err_t cce_connect_handler(cce_conn_t *cce);

/* clear out a cce_resp_t structure */
cce_err_t cce_resp_clear(cce_resp_t *resp);

/* execute a CCE command */
cce_err_t cce_command(cce_conn_t *cce, cce_cmd_t cmd, cce_params_t *params,
    cce_resp_t *response);

/*
 * escape a string to a CSCP-safe form
 * NOTE: the escaped string data should be freed by the caller
 */
char *cce_stresc(const char *source);

/*
 * unescape a CSCP string
 * NOTE: the unescaped string data should be freed by the caller
 */
char *cce_strunesc(const char *source);


/* transform a CCE-style boolean string to a native boolean value */
int cce_bool_to_native(const char *source);

/*
 * transform a native boolean value to a CCE-style boolean string
 * NOTE: the boolean string data should be freed by the caller
 */
char *cce_native_to_bool(int source);

/* transform a CCE-style integer string to a native integer value */
int cce_int_to_native(const char *source);

/*
 * transform a native integer value to a CCE-style integer string
 * NOTE: the integer string data should be freed by the caller
 */
char *cce_native_to_int(int source);

/*
 * transform a CCE-style array string to a NULL-terminated native array of
 * strings
 * NOTE: the returned array and each string in it should be freed by the
 * caller
 */
char **cce_array_to_native(const char *source);

/*
 * transform a native array of strings into a CCE-style array string
 * NOTE: the array string should be freed by the caller
 */
char *cce_native_to_array(char *const *source);

/* allocate and initialize a CCE properties structure */
cce_props_t *cce_props_new(void);

/* de-allocate a CCE properties structure */
void cce_props_destroy(cce_props_t *props);

/*
 * fetch the value stored for a given key
 * NOTE: the returned value should NOT be freed by the caller
 */
char *cce_props_get(cce_props_t *props, const char *key);

/*
 * store a value for the given key
 * NOTE: the data passed in will be duplicated internally - the caller may
 * free the key and value
 * NOTE: if value is NULL, the key will be removed
 */
cce_err_t cce_props_set(cce_props_t *props, char *key, char *value);

/* remove a key from a CCE properties structure */
cce_err_t cce_props_unset(cce_props_t *props, char *key);

/* re-initialize (remove all keys from) a CCE properties structure */
cce_err_t cce_props_renew(cce_props_t *props);

/*
 * fetch a specific indexed key/value pair from a CCE properties structure
 * NOTE: if either key or value are NULL, the respective data will not be
 * stored
 * NOTE: the returned key and value data should NOT be freed by the caller
 */
cce_err_t cce_props_index(cce_props_t *props, int idx, char **key, 
    char **value);

/*
 * create a clone of a CCE properties structure
 * NOTE: the new structure should be destroyed with cce_props_destroy()
 */
cce_props_t *cce_props_clone(cce_props_t *props);

/*
 * merge all the properties in base with all the properties in mask into a
 * new CCE properties structure
 * NOTE: the new structure should be destroyed with cce_props_destroy()
 */
cce_props_t *cce_props_merge(cce_props_t *base, cce_props_t *mask);

/* count the number of unique keys in a CCE properties structure */
int cce_props_count(cce_props_t *props);

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
