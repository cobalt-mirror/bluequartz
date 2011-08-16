/* $Id: cce.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

/*
 * This file provides the mid level commands, e.g. the per command functions.
 */

/*
 * Just as my personal marker of what command have been implemented.....
 Command Name | Written | Tested | It works on my system | Actually Working
 AUTH           X         X
 CREATE         X         X
 SET            X         X
 GET            X         X
 COMMIT         X         X
 NAMES          X         X
 DESTROY        X         X
 FIND           X         X
 BYE            X         X
 */

#include <cce_common.h>
#include <cce.h>

#include <cscp.h>
#include <cscp_cmd_table.h>
#include <stdlib.h>
#include <string.h>
#include <glib.h>

#ifdef DEBUG
#include <stdio.h>
#endif

struct cce_handle_struct {
	cscp_conn_t *conn;
	GSList *rets;
	char *suspended;
};

struct cce_ret_struct {
	/* To quickly check for success or failure */
	int success;
	/* Current pointer for iterating over lists, property lists provide their
	 * own iterators. */
	GSList *curr;
	GSList *list;
	cce_props_t *props;
	/* For returned properties of objects data may either be nothing,
	 * a number of oids from 0 to infinite held in the list, a number of
	 * strings from 0 to infite held in the list, or a property object. */
	enum {
	NONE,
	OIDS,
	STRINGS,
	PROPS
	} data_type;
#ifdef DEBUG
	char *instigator;
#endif
	/* Linked list of errors encountered */
	GSList *errors;
	GSList *curr_error;
};

struct cce_props_struct {
	/* Actually hold the key/vals here */
	GHashTable *stable;
	/* Linked list of all keys for iterating */
	GSList *keys;
	/* Pointer to the next value for iterating */
	GSList *curr;
	cce_props_state_t state;
	GHashTable *changed;
};

static cce_ret_t *cce_handle_cmnd_do(cce_handle_t * handle, cscp_cmnd_t * cmnd);
static void _cce_props_set( cce_props_t *props, char *key, char *val, int new);
static gboolean hash_string_rm(gpointer key, gpointer val, gpointer data);
static void free_whole_g_slist_errors(GSList * list);
static void keylist_append(gpointer key, gpointer val, gpointer list);
static cce_error_t *cce_error_from_line(cscp_line_t * line);
static int cce_ret_add_line(cce_ret_t * ret, cscp_line_t * line);
static int cce_ret_add_info_line(cce_ret_t * ret, cscp_line_t * line);
static int cce_ret_add_warn_line(cce_ret_t * ret, cscp_line_t * line);



/* Static constructors and deconstructors */

static cce_ret_t *cce_ret_new();
static void cce_ret_destroy(cce_ret_t * ret);

static cce_error_t *cce_error_new();
static void cce_error_destroy(cce_error_t * error);

static cce_ret_t *cce_ret_from_resp(cscp_resp_t * resp);

#ifdef DEBUG
static void props_serialise(gpointer key, gpointer val, gpointer list);
#endif

/* Constructors and destructors for our utility objects */
cce_handle_t *
cce_handle_new()
{
	cce_handle_t *handle;

	handle = (cce_handle_t *)malloc(sizeof(cce_handle_t));
	if (!handle) {
		return NULL;
	}

	handle->conn = cscp_conn_new();
	handle->rets = NULL;
	handle->suspended = NULL;

	return handle;
}

void
cce_handle_destroy( cce_handle_t * handle )
{
	GSList *rets;
	rets = handle->rets;

	while ( rets ) {
		cce_ret_destroy( rets->data );
		rets = g_slist_next(rets);
	}
	if (handle->conn) {
		cscp_conn_destroy( handle->conn );
	}
	if (handle->suspended) {
		free(handle->suspended);
	}
	free( handle );
}

static cce_ret_t *
cce_ret_new()
{
	cce_ret_t *ret;
	ret = (cce_ret_t *) malloc( sizeof( cce_ret_t ) );
	ret->list = NULL;
	ret->props = NULL;
	ret->success = 0;
	ret->curr = NULL;
	ret->data_type = NONE;
	ret->errors = NULL;
	ret->curr_error = NULL;
#ifdef DEBUG
	ret->instigator = NULL;
#endif

	return ret;
}

static void
cce_ret_destroy( cce_ret_t * ret )
{
	switch ( ret->data_type ) {
		case STRINGS:
			cce_list_destroy( ret->list );
			break;
		case OIDS:
			g_slist_free( ret->list );
			break;
		case PROPS:
			cce_props_destroy( ret->props );
			break;
		case NONE:
			break;
	}
#ifdef DEBUG
	if ( ret->instigator ) {
		free( ret->instigator );
	}
#endif
	free_whole_g_slist_errors( ret->errors );
	free( ret );
}

cce_props_t *
cce_props_new()
{
	cce_props_t *props;

	props = (cce_props_t *) malloc( sizeof( cce_props_t ) );
	if (!props) {
		return NULL;
	}

	props->stable = g_hash_table_new( g_str_hash, g_str_equal );
	props->changed = g_hash_table_new( g_str_hash, g_str_equal );
	props->keys = NULL;
	props->curr = NULL;
	props->state = CCE_NONE;

	return props;
}

void
cce_props_destroy( cce_props_t * props )
{
	g_hash_table_foreach_remove( props->stable, hash_string_rm, NULL );
	g_hash_table_foreach_remove( props->changed, hash_string_rm, NULL );
	/* All data in here is just pointers to things in the hash */
	g_hash_table_destroy( props->stable );
	g_hash_table_destroy( props->changed );
	g_slist_free( props->keys );
	free( props );
	return;
}

static gboolean
hash_string_rm( gpointer key, gpointer val, gpointer data )
{
	free( key );
	free( val );

	return TRUE;
}

void
cce_list_destroy( GSList * list )
{
	GSList *curr;

	curr = list;
	while ( curr ) {
		free(curr->data);
		curr = g_slist_next(curr);
	}
	g_slist_free(list);
}

static void
free_whole_g_slist_errors( GSList * list) 
{
	GSList *curr;

	curr = list;
	while ( curr ) {
		cce_error_destroy( curr->data );
		curr = g_slist_next( curr );
	}
	g_slist_free( list );
}

static cce_error_t *
cce_error_new()
{
	cce_error_t *error;
	error = (cce_error_t *) malloc( sizeof( cce_error_t ) );
	if (!error) {
		return NULL;
	}

	error->code = 0;
	error->key = NULL;
	error->message = NULL;
	error->oid = 0;
	return error;
}

static void
cce_error_destroy( cce_error_t * error )
{
	if ( error->key ) {
		free( error->key );
	}
	if ( error->message ) {
		free( error->message );
	}
	free( error );
}

/* Functions for working with cce_rets */

int
cce_ret_success( cce_ret_t * ret )
{
	return ret->success;
}

int
cce_ret_next_int( cce_ret_t * ret )
{
	ulong ret_int;

	if ( ret->data_type != OIDS ) {
		return 0;
	}
	if ( !ret->curr ) {
		return 0;
	}
	ret_int = GPOINTER_TO_INT( ret->curr->data );
	ret->curr = g_slist_next( ret->curr );

	return ret_int;
}

char *
cce_ret_next_str( cce_ret_t * ret )
{
	char *ret_char;

	if ( ret->data_type != STRINGS ) {
		return NULL;
	}

	if (! ret->curr ) {
		return NULL;
	}

	ret_char = ret->curr->data;
	ret->curr = g_slist_next( ret->curr );

	return ret_char;
}

cce_error_t *
cce_ret_next_error( cce_ret_t * ret )
{
	cce_error_t *error;

	if (! ret->curr_error ) {
		return NULL;
	}
	error = (cce_error_t *) ret->curr_error->data;
	ret->curr_error = g_slist_next( ret->curr_error );

	return error;
}

cce_props_t *
cce_ret_get_props( cce_ret_t * ret )
{
	if ( ret->data_type != PROPS ) {
		return NULL;
	}

	return ret->props;
}

void
cce_ret_rewind( cce_ret_t * ret )
{
	/* Just gets everything in order for interating */
	switch ( ret->data_type ) {
		case STRINGS:
		case OIDS:
			ret->curr = ret->list;
			break;
		default:
			break;
	}

	ret->curr_error = ret->errors;
}

static cce_ret_t *
cce_ret_from_resp( cscp_resp_t * resp )
{
	/* Ohhh. Aren't we tricky, we can automagically turn any response into
	 * a return structure. Well, I bet you think you're bloody good mate. */
	cce_ret_t *ret;
	cscp_line_t *line;

	ret = cce_ret_new();

	ret->success = cscp_resp_is_success( resp );

	line = cscp_resp_nextline( resp );
	while ( line ) {
		cce_ret_add_line( ret, line );
		line = cscp_resp_nextline( resp );
	}
	cscp_resp_rewind( resp );
	return ret;
}

static int
cce_ret_add_line( cce_ret_t * ret, cscp_line_t * line )
{
	switch ( cscp_line_code_status( line ) ) {
		case 1:			/* An informationla/data line */
			return cce_ret_add_info_line(ret, line);
			break;
		case 3:			/* An warning line */
			return cce_ret_add_warn_line(ret, line);
			break;
		default:
			return 0;
	}
}

/* Check that the data type is a, return if it's not set it to it if it's
 * currently NONE. */
#define SET_CHECK(a) \
if ( ret->data_type == NONE ) { \
	ret->data_type = a;\
} else if ( ret->data_type != a ) { \
	return 0;\
}

/* HACK!  this needs to be per-handle */
static int rollback_p;

static int 
cce_ret_add_info_line(cce_ret_t * ret, cscp_line_t * line)
{
	cce_error_t *error;
	gpointer str;
	cscp_oid_t oid;

	char *key;
	char *value;

	int new = 0;

	cce_props_state_t state = CCE_NONE;
	
	switch (cscp_line_code_type(line)) {
		case 3: /* DATA */
			new = 1;
		case 2: /* DADT */
			SET_CHECK(PROPS);

			if ( ret->props == NULL ) {
				ret->props = cce_props_new();
			}

			key = cscp_line_getparam( line, 1 );
			value = cscp_line_getparam( line, 3 );

			if( new ) {
				cce_props_set( ret->props, key, value );
			} else {
				cce_props_set_old( ret->props, key, value );
			}
			return 1;
			break;
		case 1: /* OBJECT */
		case 4: /* OBJECT */
			SET_CHECK( OIDS );
			str = cscp_line_getparam( line, 1 );

			oid = cscp_oid_from_string( str );
			
			ret->list = g_slist_append( ret->list, GINT_TO_POINTER(oid) );
			break;
		case 5: case 9: case 10: /* NAMESPACE | SESSIODID | CLASS */
			str = cscp_line_getparam( line, 1 );
			if( str ) {
				SET_CHECK( STRINGS );
				ret->list = g_slist_append( ret->list, strdup(str) );
			}
			break;
		case 6: /* INFO */
			/* FIXME: How do we return informational messages ?
			 * dump 'em in eith the errors ? */
			error = cce_error_new();
			error->code = 6;
			error->message = copy_message(line);
			ret->errors = g_slist_append( ret->errors, error );
			break;
		case 7: /* CREATED */
			state = CCE_CREATED;
		case 8: /* DESTROYED */
			if( state == CCE_NONE ) {
				state = CCE_DESTROYED;
			}
			SET_CHECK( PROPS );
			if( ret->props == NULL ) {
				ret->props = cce_props_new();
			}
			ret->props->state = state;
			break;
		case 11: /* ROLLBACK */
			rollback_p = 1;
			break;
		default:
			return 0;
	}
	return 1;
}

int
cce_is_rollback(cce_handle_t *cce)
{
	return rollback_p;
}

static int
cce_ret_add_warn_line( cce_ret_t * ret, cscp_line_t * line )
{
	cce_error_t *error;

	error = cce_error_from_line( line );
	if (! error ) {
		return 0;
	} else {
		ret->errors = g_slist_append( ret->errors, error );
	}
	return 1;
}

/* Functions for working with properties. */
char *
cce_props_get( cce_props_t * props, char *key )
{
	char *result;
	result = cce_props_get_new(props, key);
	if( ! result ) {
		return cce_props_get_old(props, key);
	} else {
		return result;
	}
}

char *
cce_props_get_new( cce_props_t *props, char *key )
{
	return g_hash_table_lookup( props->changed, key );
}

char *
cce_props_get_old( cce_props_t *props, char *key )
{
	return g_hash_table_lookup( props->stable, key );
}

void cce_props_set_old( cce_props_t *props, char *key, char *val) {
	_cce_props_set( props, key, val, 0);
}

void cce_props_set( cce_props_t *props, char *key, char *val) {
	_cce_props_set( props, key, val, 1);
}

static void
_cce_props_set( cce_props_t * props, char *in_key, char *in_val, int new )
{
	char *key = NULL;
	char *val = NULL;
	GHashTable *target;

	if( new ) {
		target = props->changed;
	} else {
		target = props->stable;
	}
	
	if ( g_hash_table_lookup_extended( target, in_key,
					 (gpointer) key,
					 (gpointer) val) ) {
		g_hash_table_remove(target, key);
		free( key );
		free( val );
	}

	key = strdup( in_key );
	val = strdup( ( in_val ) ? ( in_val ) : ( "" ) );

	g_hash_table_insert( target, key, val );
}

static void
keylist_append_also( gpointer key, gpointer val, gpointer props_pointer )
{
	cce_props_t *props;
	props = (cce_props_t *) props_pointer;
  if (!g_hash_table_lookup(props->stable, key)) 
		props->keys = g_slist_append( props->keys, key );
}

void
cce_props_reinit( cce_props_t * props )
{
	g_slist_free( props->keys );
	props->keys = NULL;
	g_hash_table_foreach( props->stable, keylist_append, props );
  g_hash_table_foreach( props->changed, keylist_append_also, props);
	props->curr = props->keys;
}

static void
keylist_append( gpointer key, gpointer val, gpointer props_pointer )
{
	cce_props_t *props;

	props = (cce_props_t *) props_pointer;
	props->keys = g_slist_append( props->keys, key );
}

char *
cce_props_nextkey( cce_props_t * props )
{
	char *ret;
	if (! props->curr ) {
		return NULL;
	}
	ret = props->curr->data;
	props->curr = g_slist_next( props->curr );
	return ret;
}

int
cce_props_count(cce_props_t *props)
{
	return g_hash_table_size(props->stable);
}

static void
add_props(cscp_cmnd_t *cmnd, cce_props_t *props, char *delim)
{
	char *key;
	char *value;

	if (props == NULL) {
		return;
	}
	
	cce_props_reinit(props);
	while ((key = cce_props_nextkey(props))) {
		value = cce_props_get(props, key);
		cscp_cmnd_addstr(cmnd, key);
		cscp_cmnd_addstr(cmnd, delim);
		cscp_cmnd_addstr(cmnd, value);
	}
	cce_props_reinit(props);
}

static void
cmnd_add_props(cscp_cmnd_t *cmnd, cce_props_t *props)
{
	return add_props(cmnd, props, "=");
}

static void
cmnd_add_reprops(cscp_cmnd_t *cmnd, cce_props_t *props)
{
	return add_props(cmnd, props, "~");
}

/* Functions for working with errors */

#define MSG(a)    strdup("[[base-cce." a "]]")
#define STRDUP(a) ((a) ? strdup(a) : (NULL))

/*
 * This function will build and error from a cscp_line.
 */
static cce_error_t *
cce_error_from_line( cscp_line_t * line )
{
	cce_error_t *error;

	if ( cscp_line_code_status(line) != 3 ) {
		return NULL;
	}
	error = cce_error_new();

	/* The final digit tells us all we need about the class of error. */
	error->code = cscp_line_code_type( line );

	/* FIXME: Should grab these from a define somewhere */
	switch ( error->code ) {
		case 0:			/* UNKNOWN OBJECT oid */
			error->message = MSG("unknownOid");
			error->oid=cscp_oid_from_string(cscp_line_getparam(line, 2));
			break;
		case 1:			/* UNKNOWN CLASS class */
			error->message = MSG("unknownClass");
			error->key = STRDUP( cscp_line_getparam(line, 2) );
			break;
		case 2:			/* BAD DATA oid key value */
			error->oid = cscp_oid_from_string(
				cscp_line_getparam( line, 2 ) );
			error->message = STRDUP( cscp_line_getparam(line, 4) );
			error->key =    STRDUP( cscp_line_getparam(line, 3) );
			break;
		case 3:			/* UNKNOWN NAMESPACE namespace */
			error->message = MSG("unknownNs");
			error->key = STRDUP( cscp_line_getparam(line, 2) );
			break;
		case 4: /* PERMISSION DENIED reason */
			error->message = MSG("permissionDenied");
			error->key = STRDUP(cscp_line_getparam(line, 2) );
			break;
		case 5: /* WARN error */
		case 6: /* ERROR error */
			error->message = copy_message(line); 
			// was: STRDUP(cscp_line_getparam(line,1));
			break;
		case 7:	/* OUT OF MEMORY */
			error->message = MSG("outOfMemory");
			break;
		case 8: /* BAD REGEX regex */
			error->message = MSG("badRegex");
			error->key = STRDUP(cscp_line_getparam(line, 2) );
			break;
		case 9: /* SUSPENDED reason */
			error->message = MSG("suspended");
			error->key = STRDUP(cscp_line_getparam(line, 1) );
			break;
		default:
			error->message = MSG("unknownErrorCode");
	}
	return error;
}

/* Send the command, parse the response into a ret structure and
 * save the ret to be freedn with the handle later */
static cce_ret_t *
cce_handle_cmnd_do( cce_handle_t * handle, cscp_cmnd_t * cmnd )
{
	cce_ret_t *ret;
	cscp_resp_t *resp;

#ifdef DEBUG
	char *resp_str;
	char *ret_str;
	char *cmnd_str;
#endif				/* DEBUG */

#ifdef DEBUG
	cmnd_str = cscp_cmnd_serialise( cmnd );
	fprintf(stderr, "Sending command %s", cmnd_str );
#endif

	cscp_conn_do( handle->conn, cmnd );

	resp = cscp_conn_last_resp( handle->conn );
#ifdef DEBUG
	resp_str = cscp_resp_serialise( resp );
	fprintf(stderr, "Received response\n%s", resp_str );
	free( resp_str );
#endif

	ret = cce_ret_from_resp( resp );

#ifdef DEBUG
	ret->instigator = cmnd_str;
	ret_str = cce_ret_serialise( ret );
	fprintf( stderr, "Returning\n%s", ret_str );
	free( ret_str );
	fprintf( stderr, "\n" );
#endif

	handle->rets = g_slist_prepend( handle->rets, ret );

	return ret;
}

/* COUGHhackCOUGH */
static char *
scan_for_suspended(cce_handle_t *cce)
{
	GSList *errors;

	errors = cce_last_errors_cmnd(cce);
	while (errors) {
		cce_error_t *e = (cce_error_t *)errors->data;
		if (e->code == 9) {
			return e->key;
		}
	}
	return NULL;
}

int
cce_connect_cmnd( cce_handle_t * handle, char *path )
{
	int polled = 0;
	cce_ret_t *ret;
	cscp_resp_t *resp;
	char *suspmsg = NULL;

	if (!path) {
		/* default socket path */
		path = CCEDIR CCESOCKET;
	}
	
	if (! cscp_conn_connect( handle->conn, path ) ) {
		ret = cce_ret_new();
		ret->success = 0;
	} else {
		/* FIXME: All of our standard command to get the response rely on
		 * sending a command first. We just do a bit of hackery here
		 * This will be another place where we have to implement timeouts
		 */
		while (!cscp_conn_is_finished(handle->conn)) {
			polled++;
			cscp_conn_poll(handle->conn);
		}

		resp = cscp_conn_last_resp( handle->conn );
		ret = cce_ret_from_resp( resp );
	}

	handle->rets = g_slist_prepend(handle->rets, ret);
	suspmsg = scan_for_suspended(handle);
	handle->suspended = suspmsg ? strdup(suspmsg) : NULL;

	return ret->success;
}

char *
cce_suspended(cce_handle_t *handle)
{
	return handle->suspended;
}

char *
cce_auth_cmnd( cce_handle_t * handle, char *username, char *pass )
{
	char *ret_str;
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_AUTH_CMD );

	cscp_cmnd_addstr( cmnd, username );
	cscp_cmnd_addstr( cmnd, pass );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	cce_ret_rewind( ret );

	ret_str = cce_ret_next_str( ret );
	/* FIXME! WOrk out what to do when we succeed but don't get
	 * a session ID */
	if( ret->success && ret_str == NULL ) {
		return (char *)0x1;
	} else {
		return ret_str;
	}
}

int
cce_authkey_cmnd( cce_handle_t *handle, char *user, char *sessionid )
{
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_AUTHKEY_CMD );

	cscp_cmnd_addstr( cmnd, user );
	cscp_cmnd_addstr( cmnd, sessionid );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy(cmnd);

	return ret->success;
}

cscp_oid_t
cce_whoami_cmnd( cce_handle_t *handle ) {
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_WHOAMI_CMD );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy(cmnd);
	cce_ret_rewind( ret );

	return cce_ret_next_int( ret );
}

int
cce_bye_cmnd( cce_handle_t * handle )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_BYE_CMD );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	cscp_conn_destroy( handle->conn );
	handle->conn = cscp_conn_new();

	return ret->success;
}

int
cce_endkey_cmnd( cce_handle_t * handle )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_ENDKEY_CMD );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->success;
}

cscp_oid_t
cce_create_cmnd( cce_handle_t * handle, char *class, cce_props_t * props )
{
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_CREATE_CMD );

	cscp_cmnd_addstr( cmnd, class );
	cmnd_add_props( cmnd, props );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	cce_ret_rewind(ret);
	
	if (ret->success) {
		return cce_ret_next_int(ret);
	} else {
		return 0;
	}
}

int
cce_set_cmnd
	(
		cce_handle_t * handle,
		cscp_oid_t oid,
		char *namespace,
		cce_props_t * props
	)
{
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_SET_CMD );

	cscp_cmnd_addoid( cmnd, oid, namespace);
	cmnd_add_props( cmnd, props );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->success;
}

cce_props_t *
cce_get_cmnd(
	cce_handle_t * handle, 
	cscp_oid_t oid, 
	char *namespace
	)
{
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();

	cscp_cmnd_setcmnd( cmnd, CSCP_GET_CMD );

	cscp_cmnd_addoid( cmnd, oid, namespace );

	ret = cce_handle_cmnd_do( handle, cmnd );

	cscp_cmnd_destroy( cmnd );

	return ret->props;;
}

int
cce_admin_cmnd( cce_handle_t * handle, char *command, char *argument )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_ADMIN_CMD );
	cscp_cmnd_addstr( cmnd, command );
	if (argument)
		cscp_cmnd_addstr( cmnd, argument );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->success;
}

int
cce_begin_cmnd( cce_handle_t * handle )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_BEGIN_CMD );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->success;
}

int
cce_commit_cmnd( cce_handle_t * handle )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_COMMIT_CMD );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->success;
}

GSList *
cce_names_oid_cmnd( cce_handle_t * handle, cscp_oid_t oid )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_NAMES_CMD );

	cscp_cmnd_addoid( cmnd, oid, NULL );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->list;
}

GSList *
cce_names_class_cmnd( cce_handle_t * handle, char *classname )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_NAMES_CMD );

	cscp_cmnd_addstr( cmnd, classname );

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	return ret->list;
}

/* 
 * The one true find function in this library - all others call it for some 
 * compatibility mode or other. As much as I hate findx as a name, I must 
 * keep in mind the fact that this mess will ALL go away sooner or later.
 * 
 * Call this if you want: 
 *  * regex matches
 *  * new-style sorting (sorttypes) 
 */
GSList *
cce_findx_cmnd(cce_handle_t *handle, char *classname, cce_props_t *props,
	cce_props_t *reprops, char *sorttype, char *sortprop)
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd(cmnd, CSCP_FIND_CMD);

	cscp_cmnd_addstr(cmnd, classname);

	if (sorttype && sortprop) {
		cscp_cmnd_addstr(cmnd, "SORTTYPE");
		cscp_cmnd_addstr(cmnd, sorttype);
		cscp_cmnd_addstr(cmnd, "SORTPROP");
		cscp_cmnd_addstr(cmnd, sortprop);
	}

	cmnd_add_props(cmnd, props);
	cmnd_add_reprops(cmnd, reprops);

	ret = cce_handle_cmnd_do(handle, cmnd);

	cscp_cmnd_destroy(cmnd);

	return ret->list;
}

/* plain old, unsorted find */
GSList *
cce_find_cmnd(cce_handle_t *handle, char *classname, cce_props_t *props)
{
	return cce_findx_cmnd(handle, classname, props, NULL, NULL, NULL);
}

/* old-style sort syntax - this is for compatibility only */
GSList *
cce_find_sorted_cmnd(cce_handle_t *handle, char *classname, cce_props_t *props,
	char *sortkey, int sorttype)
{
	return cce_findx_cmnd(handle, classname, props, NULL, 
		sorttype ? "old_numeric" : "ascii", sortkey);
}

int
cce_destroy_cmnd( cce_handle_t * handle, cscp_oid_t oid )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd( cmnd, CSCP_DESTROY_CMD );

	cscp_cmnd_addoid( cmnd, oid, NULL );

	ret = cce_handle_cmnd_do( handle, cmnd );

	cscp_cmnd_destroy( cmnd );

	return ret->success;
}

GSList *
cce_last_errors_cmnd( cce_handle_t *handle )
{
	cce_ret_t *ret;
	if( handle->rets && handle->rets->data ) {
		ret = (cce_ret_t *)handle->rets->data;
	} else {
		return NULL;
	}
	return ret->errors;
}
	
char *
cce_error_serialise( cce_error_t * error )
{
	GString *result;
	char *ret;

	result = g_string_new( "\tError Code: " );

	g_string_sprintfa( result, "\t%u\n", error->code );
	g_string_sprintfa( result, "\tOid: %lu\n", error->oid );

	if ( error->key ) {
		g_string_append( result, "\tKEY:" );
		g_string_append( result, error->key );
		g_string_append_c( result, '\n' );
	}
	if ( error->message ) {
		g_string_append( result, "\tMESSAGE:" );
		g_string_append( result, error->message );
		g_string_append_c( result, '\n' );
	}
	ret = strdup( result->str );
	g_string_free( result, 1 );
	return ret;
}


cscp_oid_t 
cce_connect_handler_cmnd( cce_handle_t *handle )
{
	cce_ret_t *ret;
	cscp_oid_t oid;

	cscp_conn_connect_stdin(handle->conn);

	while (! cscp_conn_is_finished( handle->conn ) ) {
		cscp_conn_poll( handle->conn );
	}

	ret = cce_ret_from_resp( cscp_conn_last_resp( handle->conn ) );
	cce_ret_rewind( ret );
	oid = cce_ret_next_int( ret);
	cce_ret_destroy(ret);
	return oid;
}

int
cce_bye_handler_cmnd( cce_handle_t *handle, cce_handler_ret cond, char *reason )
{
	cscp_cmnd_t *cmnd;
	cce_ret_t *ret;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd(cmnd, CSCP_BYE_CMD);

	switch ( cond ) {
		case CCE_SUCCESS:
			cscp_cmnd_addstr(cmnd, "SUCCESS");
			break;
		case CCE_DEFER:
			cscp_cmnd_addstr(cmnd, "DEFER");
			break;
		case CCE_FAIL:
			cscp_cmnd_addstr(cmnd, "FAIL");
			cscp_cmnd_addstr(cmnd, reason);
			break;
	}

	ret = cce_handle_cmnd_do( handle, cmnd );
	cscp_cmnd_destroy( cmnd );

	handle->rets = g_slist_append(handle->rets, ret);
	return cce_ret_success(ret);
}

int
cce_bad_data_cmnd( cce_handle_t *handle, cscp_oid_t oid, char *namespace, char *key, char *reason )
{
	cce_ret_t *ret;
	cscp_cmnd_t *cmnd;

	cmnd = cscp_cmnd_new();
	cscp_cmnd_setcmnd(cmnd, CSCP_BADDATA_CMD);

	cscp_cmnd_addoid(cmnd, oid, namespace);
	cscp_cmnd_addstr(cmnd, key);
	cscp_cmnd_addstr(cmnd, reason);

	ret = cce_handle_cmnd_do(handle, cmnd);
	cscp_cmnd_destroy(cmnd);

	return cce_ret_success(ret);
}

cce_props_state_t
cce_props_state( cce_props_t *props ) {
	return props->state;
}

GSList *
cce_array_deserial( char *list_cp )
{
        GSList *entries = NULL;
        char *p;
        char *list;
        char *orig_list;
        
        if( ! list_cp ) {
                return entries;
        }

        list = strdup( list_cp );
        orig_list = list;

        /* Skip the first & */
        list++;
        p = list;
        
        while( *list ) {
                if( *list == '&' ) {
                        /* Set that an as eof so that p which is back on teh frist
                         * charachter of this string can work as a strdup*/
                        *list = '\0';
                        entries = g_slist_append(entries, strdup( p ) );
                        /* Advance list to the next charachter */
                        list++;
                        /* Set p onto the first charachter of this string as well */
                        p = list;
                } else {
                        /* Skip on to next char */
                        list++;
                }
        }

        free( orig_list );

        return entries;
}


#ifdef DEBUG

char *
cce_ret_serialise( cce_ret_t * ret )
{
	GString *result;
	cce_error_t *error;
	char *ret_str;
	unsigned int oid;
	char *str;

	result = g_string_new("");

	if ( cce_ret_success( ret ) ) {
		g_string_append( result, "\tSuccess\n" );
	} else {
		g_string_append( result, "\tFailure\n" );
	}

	cce_ret_rewind( ret );

	switch ( ret->data_type ) {
		case NONE:
			break;
		case OIDS:
			while ( ( oid = cce_ret_next_int( ret ) ) ) {
				g_string_sprintfa( result, "\tOID: %u\n", oid );
			}
			break;
		case STRINGS:
			while ( ( str = cce_ret_next_str( ret ) ) ) {
				g_string_sprintfa( result, "\tSTR: ''%s''\n", str );
			}
			break;
		case PROPS:
			str = cce_props_serialise( cce_ret_get_props( ret ) );
			g_string_append( result, "\tPROPS: " );
			g_string_append( result, str );
			free( str );
			break;
	}

	cce_ret_rewind( ret );

	error = cce_ret_next_error( ret );
	while ( error ) {
	g_string_sprintfa( result, "\tError No: %u Oid: %lu Key: %s Message: %s\n",
			  error->code,
			  error->oid,
			  error->key,
			  error->message
	);
	error = cce_ret_next_error( ret );
	}
	ret_str = strdup( result->str );
	g_string_free( result, 1 );
	return ret_str;
}

char *
cce_props_serialise( cce_props_t * props )
{
	GString *props_str;
	char *ret;

	props_str = g_string_new( "\tStable: " );
	if( props ) {
		g_hash_table_foreach( props->stable, props_serialise, props_str );
	} else {
		g_string_append(props_str,"Empty stable props");
	}

	g_string_append(props_str, "\n\tChanged: ");

	if( props ) {
		g_hash_table_foreach( props->changed, props_serialise, props_str );
	} else {
		g_string_append(props_str,"Empty changed props");
	}

	g_string_append_c(props_str,'\n');

	g_string_append(props_str,"\tState: ");

	switch( cce_props_state(props) ) {
		case CCE_NONE:
			g_string_append(props_str, "None\n");
			break;
		case CCE_MODIFIED:
			g_string_append(props_str, "Modified\n");
			break;
		case CCE_CREATED:
			g_string_append(props_str, "Created\n");
			break;
		case CCE_DESTROYED:
			g_string_append(props_str, "Destroyed\n");
			break;
	}
	g_string_append(props_str,"\tChanged: ");

	ret = strdup( props_str->str );
	g_string_free( props_str, 1 );

	return ret;
}

static void
props_serialise( gpointer key, gpointer val, gpointer pointer_string )
{
	GString *string;

	string = (GString *) pointer_string;

	g_string_sprintfa( string, "%s = `%s', ", (char *) key, (char*)val );
}

#endif				/* DEBUG */

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
