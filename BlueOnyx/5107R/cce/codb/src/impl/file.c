/* $Id: file.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <impl.h>
#include <cce_scalar.h>
#include <odb_types.h>
#include <odb_errors.h>

codb_ret
impl_create_obj(odb_impl_handle *h, odb_oid *oid)
{
	PATHVAR(path);

	/* does object exist? */
	if (impl_obj_exists(h, oid))
	{
		return CODB_RET_ALREADY;
	}

	/* do the deed */
	objpathof(h, oid, path);

	if (mkdir(path, S_IWUSR|S_IRUSR|S_IXUSR) < 0) {
		switch (errno) {
			case EEXIST: 
				DPRINTF(DBG_CODB, "impl_create_obj: %s exists\n", path);
				return CODB_RET_ALREADY;
			default:
				DPERROR(DBG_CODB, "impl_create_obj: mkdir()");
				return CODB_RET_OTHER;
		}
	}

	return CODB_RET_SUCCESS;
}

codb_ret
impl_destroy_obj(odb_impl_handle *h, odb_oid *oid)
{
	PATHVAR(objpath);
	DIR *objdir;
	struct dirent *d;

	objpathof(h, oid, objpath);
	
	if (!impl_obj_exists(h, oid)) {
		DPRINTF(DBG_CODB, "impl_destroy_obj: object doesn't exist\n");
		return CODB_RET_ALREADY;
	}

	objdir = opendir(objpath);
	if (!objdir) {
		DPERROR(DBG_CODB, "impl_destroy_obj: opendir()");
		return CODB_RET_OTHER;
	}

	while ((d = readdir(objdir)) != NULL) {
		PATHVAR(fpath);
		/* strip '.' and '..' - use strcmp not to catch .??**/
		if (!strcmp(".", d->d_name)
		 || !strcmp("..", d->d_name)) {
			continue;
		}
		proppathof(objpath, d->d_name, fpath);
		unlink(fpath);
      }

	closedir(objdir);
	rmdir(objpath);

	return CODB_RET_SUCCESS;
}

int
impl_objprop_isdefined(odb_impl_handle *h, odb_oid *oid, char *prop)
{
	PATHVAR(objpath);
	PATHVAR(proppath);
	struct stat buf;

	objpathof(h, oid, objpath);
	
	proppathof(objpath, prop, proppath);

	return !stat(proppath, &buf);
	
}

int
get_flock(int fd, int nsecs)
{
	if (flock(fd, LOCK_EX | LOCK_NB)) {
		int i = nsecs * 3;
		while (i--) {
			if (flock(fd, LOCK_EX | LOCK_NB) == 0) {
				break;
			}
			usleep(333333);
		}
		if (i == 0) {
			DPERROR(DBG_CODB, "get_flock: flock()");
			close (fd);
			return 0;
		}
	}

	return 1;
}

void
release_flock(int fd)
{
	flock(fd, LOCK_UN);
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
