/* $Id: scalar.c 3 2003-07-17 15:19:15Z will $ 
 */

#include <impl.h>
#include <cce_scalar.h>
#include <odb_types.h>
#include <odb_errors.h>

codb_ret
impl_read_objprop(odb_impl_handle *h, odb_oid *oid, char *prop, 
			cce_scalar *result)
{
	PATHVAR(objpath);
	PATHVAR(proppath);

	objpathof(oid, objpath);
	proppathof(objpath, prop, proppath);

	return read_scalar(proppath, result);
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

	objpathof(oid, objpath);

	return write_scalar(objpath, prop, val);
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
read_scalar(char *path, cce_scalar *result)
{
	if (cce_scalar_from_file(result, path) < 0) {
		DPERROR(DBG_CODB, "read_scalar: cce_scalar_from_file()");
		return CODB_RET_OTHER;
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
write_scalar(char *path, char *prop, cce_scalar *val)
{
	PATHVAR(proppath);

	proppathof(path, prop, proppath);
	
	if (cce_scalar_to_file(val, proppath) < 0) {
		DPERROR(DBG_CODB, "write_scalar: cce_scalar_to_file()");
		return CODB_RET_OTHER;
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
