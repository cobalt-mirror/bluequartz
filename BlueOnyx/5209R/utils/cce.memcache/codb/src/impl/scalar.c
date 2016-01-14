/* $Id: scalar.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <impl.h>
#include <cce_scalar.h>
#include <odb_types.h>
#include <odb_errors.h>

codb_ret
impl_read_objprop(odb_impl_handle *h, odb_oid *oid, const char *prop, 
			cce_scalar *result)
{
	PATHVAR(objpath);
	PATHVAR(proppath);

	objpathof(h, oid, objpath);
	proppathof(objpath, prop, proppath);
	return read_scalar(oid, proppath, prop, result);
}

codb_ret
impl_write_objprop(odb_impl_handle *h, odb_oid *oid, char *prop, 
			 cce_scalar *val)
{
	char *p;
	PATHVAR(objpath);

	if (!impl_obj_exists(h, oid)) {
		return CODB_RET_UNKOBJ;
	}

	/* this is the ugly hack that ensures that plaintext passwords
	 * are never written out to disk.  This is embarassing, but
	 * the schedule is tight.  FIXME: don't do this.
	 */
	p = strrchr(prop, '.');
	if (p && !strncasecmp(p, ".password", 9)) {
		/* pretend we wrote out the value, and return. */
		return CODB_RET_SUCCESS;
	}

	objpathof(h, oid, objpath);

	return write_scalar(oid, objpath, prop, val);
}

/*
 * read_scalar
 *
 * read a scalar from the given file into a scalar structure
 * returns:
 *	CODB_RET_SUCCESS on success
 *	CODB_RET_OTHER if an error occurs
 */
codb_ret
read_scalar(odb_oid *oid, char *path, const char *prop, cce_scalar *result)
{
	int ret;
	PATHVAR(key);
	char *cache_value;

	// try to get value from memcached
	ret = connect_memcached();

	objkeyof(oid, prop, key);
	ret = get_from_memcached(key, result); 

	if (cce_debug_mask & (DBG_MEMCACHED)) {
		cache_value = result->data;
	}

	if (ret <= 0 || (cce_debug_mask & (DBG_MEMCACHED))) {
		if (cce_scalar_from_file(result, path) < 0) {
			DPERROR(DBG_CODB, "read_scalar: cce_scalar_from_file()");
			return CODB_RET_OTHER;
		}
	}

	if (ret > 0 && cce_debug_mask & (DBG_MEMCACHED)) {
		DPRINTF(DBG_MEMCACHED, "Data: [%s] [%s]:[%s]\n", key, cache_value, result->data);
		result->data = cache_value;
	}

	// wite cache data to memcached
	if (result->data != NULL && ret <= 0) {
		ret =  set_to_memcached(key, cce_scalar_string(result)); 
		if (ret) {
			DPRINTF(DBG_MEMCACHED, "failed: set memcached():[%d]\n", ret);
		}
	}

	return CODB_RET_SUCCESS;
}

/*
 * write_scalar
 *
 * write a scalar to a property
 * returns:
 *	CODB_RET_SUCCESS on success
 *	CODB_RET_OTHER on fail
 */
codb_ret
write_scalar(odb_oid *oid, char *path, char *prop, cce_scalar *val)
{
	int ret;
	PATHVAR(proppath);
	PATHVAR(key);

	proppathof(path, prop, proppath);
	DPRINTF(DBG_CODB, "write_scalar : path = %s / %s\n", proppath, val->data);

	objkeyof(oid, prop, key);

	// delete cache data from memcached
	ret = delete_from_memcached(key);
	if (ret) {
		DPRINTF(DBG_MEMCACHED, "failed: delete memcached():[%d]\n");
	}
	
	if (cce_scalar_to_file(val, proppath) < 0) {
		DPERROR(DBG_CODB, "write_scalar: cce_scalar_to_file()");
		return CODB_RET_OTHER;
	}

	// write cache data to memcached
	if (val->data != NULL) {
		ret =  set_to_memcached(key, cce_scalar_string(val));
		if (ret) {
			DPRINTF(DBG_MEMCACHED, "failed: set memcached():[%d]\n", ret);
		}
	}

	return CODB_RET_SUCCESS;
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
