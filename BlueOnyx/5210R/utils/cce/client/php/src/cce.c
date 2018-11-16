/* $Id: cce.c 348 2004-04-15 13:49:39Z anders $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Here be dragons.
 */
#include <php.h>
#include "ext/standard/info.h"

#include <cce.h>
#include <cce_common.h>
#include <glib.h>

#include <php_cce.h>

zend_function_entry ccephp_functions[] = {
	PHP_FE(ccephp_auth, NULL)
	PHP_FE(ccephp_suspend, NULL)
	PHP_FE(ccephp_resume, NULL)
	PHP_FE(ccephp_authkey, NULL)
	PHP_FE(ccephp_bye, NULL)
	PHP_FE(ccephp_connect, NULL)
	PHP_FE(ccephp_suspended, NULL)
	PHP_FE(ccephp_begin, NULL)
	PHP_FE(ccephp_commit, NULL)
	PHP_FE(ccephp_create, NULL)
	PHP_FE(ccephp_destroy, NULL)
	PHP_FE(ccephp_endkey, NULL)
	PHP_FE(ccephp_errors, NULL)
	PHP_FE(ccephp_find, NULL)
	PHP_FE(ccephp_findx, NULL)
	PHP_FE(ccephp_get, NULL)
	PHP_FE(ccephp_names, NULL)
	PHP_FE(ccephp_new, NULL)
	PHP_FE(ccephp_set, NULL)
	PHP_FE(ccephp_whoami, NULL)
	PHP_FE(ccephp_is_rollback, NULL)

	/* Handler only functions */
	PHP_FE(ccephp_handler_get, NULL)
	
	{NULL, NULL, NULL}
};

zend_module_entry ccephp_module_entry = {
	/* Added for PHP >= 4.1 */
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER, /* Standard Recommended Header */
#endif
	"cce", /* Name */
	ccephp_functions, /* Array of functions */
	PHP_MINIT(ccephp), /* Init functions. */
	NULL, /* Shutdown function */
	NULL, /* Dunno */
	NULL, /* Dunno */
	PHP_MINFO(ccephp), /* Information for php_info */
#if ZEND_MODULE_API_NO >= 20010901
	NO_VERSION_YET, /* Version String.  None yet. */
#endif
	STANDARD_MODULE_PROPERTIES /* Properties */
};

/* Our list identifier */
static int handle_list;

/* Helper functions */
static cce_handle_t *get_handle( long index );

/* PHP -> CCE functions */
static cce_props_t * php_hash_to_props ( HashTable *ht );

/* CCE -> PHP functions */
static int cce_props_to_zval ( cce_props_t *props, zval *php_hash );
static int glist_ints_to_zval( GSList *list, zval *z_list );
static int glist_errors_to_zval( GSList *list, zval *z_list );
static int glist_strs_to_zval( GSList *list, zval *z_list);

#define GET_HANDLE(a,b) \
a = get_handle( b ); \
if ( a == NULL ) { \
	RETURN_FALSE; \
}


DLEXPORT zend_module_entry *get_module(void) { return &ccephp_module_entry; }

PHP_MINIT_FUNCTION(ccephp)
{
	/* Create a list of object that will be got rid of using the
	 * ccephp_handle_destroy function */
	handle_list = register_list_destructors(cce_handle_destroy,NULL);
	return SUCCESS;
}

/* Create a new handle, dump it into the list and return it's index */
PHP_FUNCTION( ccephp_new )
{
	int index;
	cce_handle_t *handle;

	handle = cce_handle_new();

	
	index = zend_list_insert(handle, handle_list);
	RETURN_LONG( index );
}

PHP_FUNCTION( ccephp_connect )
{
	zval *index, *socket;
	cce_handle_t *handle;
	int argc;

	argc = ARG_COUNT(ht);

	if ( argc != 2 ) {
		WRONG_PARAM_COUNT;
	}

	if ( ! zend_get_parameters(ht, argc, &index, &socket) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_string(socket);

	GET_HANDLE(handle,index->value.lval);

	if ( cce_connect_cmnd(handle, socket->value.str.val ) ) {
		RETURN_TRUE;
	} else {
		RETURN_FALSE;
	}
}

PHP_FUNCTION(ccephp_suspended)
{
	cce_handle_t *handle;
	zval *index;
	char *reason;

	if (ARG_COUNT(ht) != 1) {
		WRONG_PARAM_COUNT;
	}

	if (zend_get_parameters(ht, 1, &index) == FAILURE) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle, index->value.lval);

	reason = cce_suspended(handle);
	if (reason) {
		RETURN_STRING(reason, 1);
	} else {
		RETURN_FALSE;
	}
}

PHP_FUNCTION( ccephp_auth )
{
	zval *index, *user, *pass;
	int argc;
	char *sessionId;
	cce_handle_t *handle;

		
	argc = ARG_COUNT(ht);
	if( argc != 3 || zend_get_parameters( ht, argc, &index, &user, &pass ) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);
	convert_to_string(user);
	convert_to_string(pass);

	GET_HANDLE(handle, index->value.lval);

	sessionId = cce_auth_cmnd(handle, user->value.str.val,
	                                  pass->value.str.val);
	if( sessionId ) {
		RETURN_STRING( sessionId, 1);
	} else {
		RETURN_FALSE;
	}
}

PHP_FUNCTION( ccephp_authkey )
{
	zval *index, *user, *sessionId;
	int argc;
	int ret;
	cce_handle_t *handle;
	
	argc = ARG_COUNT(ht);
	if( argc != 3 || zend_get_parameters( ht, argc, &index, &user, &sessionId ) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);
	convert_to_string(user);
	convert_to_string(sessionId);

	GET_HANDLE(handle, index->value.lval);

	ret = cce_authkey_cmnd(handle, 
		user->value.str.val, 
		sessionId->value.str.val);
	if (ret) {
		RETURN_TRUE;
	} else {
		RETURN_FALSE;
	}
	// RETURN_LONG( cce_authkey_cmnd(handle, user->value.str.val, sessionId->value.str.val) );
}


PHP_FUNCTION( ccephp_get )
{
	zval *index, *oid, *space;
	cce_handle_t *handle;
	cce_props_t *props;

	char *space_str;
	
	if ( ARG_COUNT(ht) != 3 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 3, &index, &oid, &space ) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);
	convert_to_long(oid);
	convert_to_string(space);

	if( ! strlen( space->value.str.val ) ) {
		space_str = NULL; 
	} else {
		space_str = space->value.str.val;
	}

	GET_HANDLE(handle, index->value.lval);

	props = cce_get_cmnd(handle, oid->value.lval, space_str);

	if ( ! cce_props_to_zval(props, return_value) ) {
		RETURN_FALSE;
	}
	/* Er.. I think that just by setting return value we return that.. */
}

PHP_FUNCTION( ccephp_handler_get )
{
	zval *index, *oid, *space;
	cce_handle_t *handle;
	cce_props_t *props;

	char *space_str;
	
	if ( ARG_COUNT(ht) != 3 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 3, &index, &oid, &space ) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);
	convert_to_long(oid);
	convert_to_string(space);

	if( ! strlen( space->value.str.val ) ) {
		space_str = NULL; 
	} else {
		space_str = space->value.str.val;
	}

	GET_HANDLE(handle, index->value.lval);

	props = cce_get_cmnd(handle, oid->value.lval, space_str);

	if ( ! cce_props_to_zval(props, return_value) ) {
		RETURN_FALSE;
	}

	/* Set the state as the special value STATE */

	switch( cce_props_state( props ) ) {
		case CCE_CREATED:
			add_assoc_long(return_value,"CREATED",1);
			break;
		case CCE_DESTROYED:
			add_assoc_long(return_value,"DESTROYED",1);
			break;
		default:
			break;
	}

}

PHP_FUNCTION(ccephp_find)
{
	zval		*index, *classname, *props, *sortkey, *sorttype;
	cce_handle_t	*handle;
	cce_props_t 	*cce_props;
	GSList		*result;
	char		*class_str;
	char		*sortkey_str;
	int		sorttype_int;
	
	if( ARG_COUNT(ht) != 5 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 5, 
		&index, &classname, &props, &sortkey, &sorttype) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_string(classname);
	convert_to_string(sortkey);
	convert_to_long(sorttype);

	if( props->type != IS_ARRAY ) {
		php_error(E_WARNING,"Passed a non array as an array in ccephp_find");
		RETURN_FALSE;
	}

	GET_HANDLE(handle,index->value.lval);
	
	cce_props = php_hash_to_props(props->value.ht);

	if( ! strlen( classname->value.str.val ) ) {
		php_error(E_WARNING,"ccephp_find: invalid class name");
		RETURN_FALSE;
	} else {
		class_str = classname->value.str.val;
	}

	sortkey_str = sortkey->value.str.val;
	sorttype_int = sorttype->value.lval;

	if (strlen(sortkey->value.str.val)) {
		result = cce_find_sorted_cmnd(handle, class_str, cce_props, 
			sortkey_str, sorttype_int);
	} else {
		result = cce_find_cmnd(handle, class_str, cce_props);
	}

	if( ! glist_ints_to_zval(result, return_value) ) {
		php_error(E_WARNING,"Could not init return value in ccephp_find");
	}

	cce_props_destroy(cce_props);
}

PHP_FUNCTION(ccephp_findx)
{
	zval		*index, *classname, *props, *reprops, 
			*sorttype, *sortkey;
	cce_handle_t	*handle;
	cce_props_t 	*cce_props;
	cce_props_t 	*cce_reprops;
	char		*class_str;
	char		*sorttype_str = NULL;
	char		*sortkey_str = NULL;
	GSList		*result;
	
	if (ARG_COUNT(ht) != 6) {
		WRONG_PARAM_COUNT;
	}

	if (zend_get_parameters(ht, 6, &index, &classname, &props, &reprops, 
	 &sorttype, &sortkey) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_string(classname);
	convert_to_string(sorttype);
	convert_to_string(sortkey);

	if (props->type != IS_ARRAY || reprops->type != IS_ARRAY) {
		php_error(E_WARNING,"Passed a non array as an array in ccephp_findx");
		RETURN_FALSE;
	}

	GET_HANDLE(handle, index->value.lval);
	
	cce_props = php_hash_to_props(props->value.ht);
	cce_reprops = php_hash_to_props(reprops->value.ht);

	if (!strlen(classname->value.str.val)) {
		php_error(E_WARNING,"ccephp_findx: invalid class name");
		RETURN_FALSE;
	} else {
		class_str = classname->value.str.val;
	}

	if (strlen(sorttype->value.str.val)) {
		sorttype_str = sorttype->value.str.val;
		sortkey_str = sortkey->value.str.val;
	}

	result = cce_findx_cmnd(handle, class_str, cce_props, cce_reprops,
		sorttype_str, sortkey_str);

	if( ! glist_ints_to_zval(result, return_value) ) {
		php_error(E_WARNING,"Could not init return value in ccephp_find");
	}

	cce_props_destroy(cce_props);
	cce_props_destroy(cce_reprops);
}


PHP_FUNCTION(ccephp_begin)
{
	zval *index;
	cce_handle_t *handle;
	
	if( ARG_COUNT(ht) != 1 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 1, &index) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle,index->value.lval);

	RETURN_BOOL( cce_begin_cmnd(handle) );
}

PHP_FUNCTION(ccephp_commit)
{
	zval *index;
	cce_handle_t *handle;
	
	if( ARG_COUNT(ht) != 1 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 1, &index) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle,index->value.lval);

	RETURN_BOOL( cce_commit_cmnd(handle) );
}

PHP_FUNCTION(ccephp_destroy)
{
	zval *index, *oid;
	cce_handle_t *handle;
	
	if( ARG_COUNT(ht) != 2 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 2, &index, &oid) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_long(oid);
	
	GET_HANDLE(handle, index->value.lval);

	RETURN_BOOL( cce_destroy_cmnd( handle, oid->value.lval ) );
}

PHP_FUNCTION(ccephp_errors)
{
	zval *index;
	cce_handle_t *handle;
	GSList *errors;

	if( ARG_COUNT(ht) != 1 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 1, &index) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle, index->value.lval);

	errors = cce_last_errors_cmnd(handle);
	if( ! glist_errors_to_zval(errors, return_value) ) {
		RETURN_FALSE;
	}
}

PHP_FUNCTION( ccephp_create )
{
	zval *index, *class, *z_props;
	cce_handle_t *handle;
	cce_props_t *props;
	char *class_str;
	
	cscp_oid_t oid;

	
	if( ARG_COUNT(ht) != 3 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 3, &index, &class, &z_props) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle, index->value.lval);

	convert_to_string( class );
	if (! strlen(class->value.str.val) ) {
		class_str = NULL;
	} else {
		class_str = class->value.str.val;
	}

	
	if ( z_props->type != IS_ARRAY ) {
		php_error(E_WARNING, "Arg 3 for ccephp_create must be an array");
		RETURN_FALSE;
	}
	
	props = php_hash_to_props(z_props->value.ht);

	oid = cce_create_cmnd(handle, class_str, props);

	cce_props_destroy(props);
	
	RETURN_LONG( oid );
}

PHP_FUNCTION( ccephp_set )
{
	zval *index, *oid, *namespace, *z_props;

	char *name_str;
	cce_handle_t *handle;
	cce_props_t *props;

	int ret;
	
	if( ARG_COUNT(ht) != 4 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 4, &index, &oid, &namespace, &z_props) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_long(oid);
	convert_to_string(namespace);

	if( z_props->type != IS_ARRAY ) {
		php_error(E_WARNING, "Fourth arg to ccephp_set must be an array");
		RETURN_FALSE;
	}

	props = php_hash_to_props(z_props->value.ht);

	if( strlen(namespace->value.str.val) == 0 ) {
		name_str = NULL;
	} else {
		name_str = namespace->value.str.val;
	}

	GET_HANDLE(handle, index->value.lval);

	ret = cce_set_cmnd(handle, oid->value.lval, name_str, props);

	cce_props_destroy(props);

	RETURN_BOOL(ret);
}

PHP_FUNCTION( ccephp_names ) 
{
	zval *index, *arg;
	cce_handle_t *handle;
	GSList *result;

	
	if ( ARG_COUNT(ht) != 2 ) {
		WRONG_PARAM_COUNT;
	}

	if ( zend_get_parameters(ht, 2, &index, &arg) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);

	GET_HANDLE(handle, index->value.lval);
	
	if( arg->type == IS_STRING ) {
		result = cce_names_class_cmnd(handle, arg->value.str.val);
	} else if (arg->type == IS_LONG ) {
		result = cce_names_oid_cmnd(handle, arg->value.lval);
	} else {
		php_error(E_WARNING,"Second arg passed to cce names must be a long or a string.");
		RETURN_FALSE;
	}
	if( array_init(return_value) == FAILURE ) {
		php_error(E_ERROR,"Could not initialise array");
		RETURN_FALSE;
	}
	if(! glist_strs_to_zval(result, return_value) ) {
		RETURN_FALSE;
	}
}

PHP_FUNCTION( ccephp_bye )
{
	cce_handle_t *handle;
	zval *index;

	if( ARG_COUNT(ht) != 1 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 1, &index) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle, index->value.lval );

	RETURN_BOOL( cce_bye_cmnd( handle ) );
}

PHP_FUNCTION( ccephp_endkey )
{
	cce_handle_t *handle;
	zval *index;

	if( ARG_COUNT(ht) != 1 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 1, &index) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle, index->value.lval );

	RETURN_BOOL( cce_endkey_cmnd( handle ) );
}


PHP_FUNCTION( ccephp_whoami )
{
	cce_handle_t *handle;
	zval *index;

	if( ARG_COUNT(ht) != 1 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 1, &index) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	GET_HANDLE(handle, index->value.lval );

	RETURN_LONG( cce_whoami_cmnd( handle ) );
}


PHP_FUNCTION( ccephp_bye_handle )
{
	zval *index, *reason, *message;
	cce_handle_t *handle;
	char *message_str;
	
	if( ARG_COUNT(ht) != 3 ) {
		WRONG_PARAM_COUNT;
	}

	if( zend_get_parameters(ht, 3, &index, &reason, &message) ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_long(reason);
	convert_to_string(message);
	
	GET_HANDLE(handle, index->value.lval );

	message_str = message->value.str.val;

	if( strlen(message_str) == 0 ) {
		message_str = NULL;
	}

	RETURN_BOOL(cce_bye_handler_cmnd(handle, reason->value.lval, message_str));
}

PHP_FUNCTION( ccephp_bad_data )
{
	zval *index, *oid, *space, *key, *reason;
	cce_handle_t *handle;
	
	if( ARG_COUNT(ht) != 5 ) {
		WRONG_PARAM_COUNT;
	}

	if(zend_get_parameters(ht, 5, &index, &oid, &space, &key, &reason) == FAILURE) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(index);
	convert_to_long(oid);
	convert_to_string(space);
	convert_to_string(key);
	convert_to_string(reason);

	GET_HANDLE(handle, index->value.lval);
	
	RETURN_BOOL(
		cce_bad_data_cmnd(handle, oid->value.lval, space->value.str.val,
				key->value.str.val, reason->value.str.val )
	);
}

PHP_FUNCTION( ccephp_suspend )
{
	zval *index, *reason;
	int argc;
	cce_handle_t *handle;

	argc = ARG_COUNT(ht);
	if (argc != 2 || zend_get_parameters(ht, argc, &index, &reason) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);
	convert_to_string(reason);

	GET_HANDLE(handle, index->value.lval);
	if (cce_admin_cmnd(handle, "SUSPEND", reason->value.str.val))
	{
		RETURN_TRUE;
	} else {
		RETURN_FALSE;
	}
}

PHP_FUNCTION( ccephp_resume )
{
	zval *index;
	int argc;
	cce_handle_t *handle;

	argc = ARG_COUNT(ht);
	if (argc != 1 || zend_get_parameters(ht, argc, &index) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);

	GET_HANDLE(handle, index->value.lval);
	if (cce_admin_cmnd(handle, "RESUME", NULL))
	{
		RETURN_TRUE;
	} else {
		RETURN_FALSE;
	}
}

PHP_FUNCTION( ccephp_is_rollback )
{
	zval *index;
	int argc;
	cce_handle_t *handle;

	argc = ARG_COUNT(ht);
	if (argc != 1 || zend_get_parameters(ht, argc, &index) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}
	convert_to_long(index);

	GET_HANDLE(handle, index->value.lval);
	RETURN_BOOL(cce_is_rollback(handle));
}

static cce_handle_t *
get_handle( long index )
{
	int type;
	cce_handle_t *handle;

	handle = (cce_handle_t *) zend_list_find(index, &type);

	if ( ! handle ) {
		php_error(E_WARNING, "Index %ld invalid", index);
		return NULL;
	}

	if ( type != handle_list ) {
		php_error(E_WARNING, "Index %ld was not of type %d", index, type);
		return NULL;
	}

	return handle;
}

/* PHP hash <-> cce hash */
static cce_props_t * php_hash_to_props ( HashTable *ht )
{
	cce_props_t *props;
	int num_fields;
	int i;
	int keytype;
	ulong keylength;
	char *keyname;
	zval *keydata, **keydataptr;

	props = cce_props_new();

	if ( ht == NULL ) {
		return props;
	}

	if(! (num_fields = zend_hash_num_elements(ht) ) ) {
		return props;
	}

	zend_hash_internal_pointer_reset(ht);
	for( i = 0; i < num_fields; i++ ) {
		keytype = zend_hash_get_current_key(ht, &keyname,
		    &keylength, 1);
		zend_hash_get_current_data(ht, (void **) &keydataptr);
		keydata = *keydataptr;

		convert_to_string(keydata);

		cce_props_set(props, keyname, keydata->value.str.val);
		pefree(keyname, ht->persistent);
		zend_hash_move_forward(ht);
	}

	return props;
}

static int
cce_props_to_zval ( cce_props_t *props, zval *php_hash )
{
	char *key, *val;
	zval *old_vals;

	ALLOC_ZVAL(old_vals);
	array_init(old_vals);
	INIT_PZVAL(old_vals);

	/* Huh! ? This should break it but it works .. */
	/* When the code is filled with comments like the one above from
	 * the author you know something is wrong. */
	if( array_init(php_hash) == FAILURE || props == NULL ) {
		return 0;
	}
	
	cce_props_reinit( props );
	while ( ( key = cce_props_nextkey( props ) ) ) {
		val = cce_props_get(props, key);
		add_assoc_string(php_hash, key, val, 1);
		if( ( val = cce_props_get_old(props, key) ) ) {
			add_assoc_string(old_vals, key, val, 1);
		}
	}

	/* FIXME: wtf?  this sucks */
	zend_hash_update(php_hash->value.ht, "OLD", sizeof("OLD")+1,
		(void *) &old_vals, sizeof(zval *), NULL);
	return 1;
}

/* PHP Array Ints <-> Glist */
static int glist_ints_to_zval( GSList *list, zval *z_list )
{
	if( array_init( z_list ) == FAILURE )
		return 0;

	while( list ) {
		add_next_index_long(z_list, GPOINTER_TO_INT(list->data));
		list = g_slist_next(list);
	}

	return 1;
}

static int glist_strs_to_zval( GSList *list, zval *z_list )
{
	while( list ) {
		add_next_index_string(z_list, list->data, 1);
		list = g_slist_next(list);
	}
	return 1;
}

/* List of errors -> array */
static int glist_errors_to_zval( GSList *list, zval *z_list )
{
	zval *error;
	cce_error_t *cce_error;


	if ( array_init(z_list) == FAILURE ) {
		return 0;
	}
	while(list) {
		ALLOC_ZVAL(error);
	
		if(array_init(error) == FAILURE) {
			php_error(E_ERROR,"Unable to initialie array");
			return 0;
		}

		INIT_PZVAL(error);

		cce_error = (cce_error_t *)list->data;
		
		add_assoc_long(error, "code", cce_error->code);
		add_assoc_long(error, "oid", cce_error->oid);

		if ( cce_error->key ) {
			add_assoc_string(error, "key", cce_error->key, 1);
		}

		if ( cce_error->message ) {
			add_assoc_string(error, "message", cce_error->message, 1);
		}

		zend_hash_next_index_insert(z_list->value.ht, &error, sizeof(zval *), NULL);
		list = g_slist_next(list);
	}
	return 1;
}

PHP_MINFO_FUNCTION(ccephp)
{
	php_info_print_table_start();
	php_info_print_table_row(2, "Cobalt CCE Support", "enabled");
	php_info_print_table_end();
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
