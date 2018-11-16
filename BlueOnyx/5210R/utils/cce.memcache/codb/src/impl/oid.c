/* $Id: oid.c,v 1.3 2001/08/10 22:23:10 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Contains the functions for working with list of oids used
 */

#include <cce_common.h>
#include <impl.h>
#include <odb_types.h>
#include <odb_errors.h>
#include <intspan.h>
#include <sys/stat.h>
#include <unistd.h>
#include <sys/file.h>

void
flock_wait(int fd)
{
     int i;

     for (i = 0; i < 360; i++)
     {
         if (flock(fd, LOCK_EX | LOCK_NB) == 0)
         {
             /* file locked successfully */
     	     /* CCE_SYSLOG("LOCKDEBUG: Locked file %s: %m"); */
             return;
         }
         usleep(i * 1E6 / 100);    /* a tenth of a second */
     	 /* CCE_SYSLOG("LOCKDEBUG: Unable to lock file %s: %m - sleeping"); */
     }
     /* Could not lock - return nonetheless, which is suboptimal */
     /* CCE_SYSLOG("LOCKDEBUG: Unable to lock file %s: %m - returning"); */
     return;    /* failure, but continue */
}

codb_ret
impl_grab_an_oid(odb_impl_handle * h, odb_oid * oid)
{
	int fd;
	cce_intspan *used_oids;
	guint new_oid;

	/* read intspan from disk */
	{
		gchar *buf;
		struct stat statbuf;
		char oidfile[MAX_PATHLEN];

		snprintf(oidfile, MAX_PATHLEN, "%s/codb.oids", h->db_path);
		fd = open(oidfile, O_RDWR | O_CREAT | O_SYNC,
			S_IRUSR | S_IWUSR);
		if (!fd)
		{
			CCE_SYSLOG("Could not open %s: %m", oidfile);
			return CODB_RET_OTHER;	/* failure */
		}

		flock_wait(fd);

		fstat(fd, &statbuf);
		buf = malloc(statbuf.st_size + 1);
		if (!buf)
		{
			CCE_SYSLOG("Out of memory.");
			return CODB_RET_NOMEM;
		}

		memset(buf, 0, statbuf.st_size + 1);
		read(fd, buf, statbuf.st_size);
		// DPRINTF(DBG_CODB, "read: %s\n", buf);

		used_oids = intspan_new();
		intspan_unserialize(used_oids, buf);
		free(buf);
	};

	new_oid = intspan_find_any_avail(used_oids);
	if (new_oid)
	{
		intspan_set(used_oids, new_oid);
	};

	/* write back out to disk */
	{
		GString *buf;
		size_t count;
		buf = g_string_new("");
		intspan_serialize(used_oids, buf);
		lseek(fd, SEEK_SET, 0);
		count = strlen(buf->str);
		write(fd, buf->str, count);
		// DPRINTF(DBG_CODB, "grb ==> usedoids = %s\n", buf->str);
		ftruncate(fd, count);
		fsync(fd);
		flock(fd, LOCK_UN);
		close(fd);
		g_string_free(buf, 1);
	};

	intspan_destroy(used_oids);

	DPRINTF(DBG_CODB, "Allocated OID: %d\n", new_oid);
	oid->oid = new_oid;

	return CODB_RET_SUCCESS;
}

codb_ret
impl_release_an_oid(odb_impl_handle * h, odb_oid * oid)
{
	int fd;
	cce_intspan *used_oids;

	/* read intspan from disk */
	{
		gchar *buf;
		struct stat statbuf;
		char oidfile[MAX_PATHLEN];

		snprintf(oidfile, MAX_PATHLEN, "%s/codb.oids", h->db_path);
		fd = open(oidfile, O_RDWR | O_CREAT | O_SYNC,
			S_IRUSR | S_IWUSR);
		if (!fd)
		{
			// h->lasterr = ERR_INTERNAL;
			return CODB_RET_OTHER;	/* failure */
		}

		flock_wait(fd);

		fstat(fd, &statbuf);
		buf = malloc(statbuf.st_size + 1);
		if (!buf)
		{
			// h->lasterr = ERR_INTERNAL;
			return CODB_RET_OTHER;
		}

		memset(buf, 0, statbuf.st_size + 1);
		read(fd, buf, statbuf.st_size);

		used_oids = intspan_new();
		intspan_unserialize(used_oids, buf);
		free(buf);
	}

	intspan_clear(used_oids, oid->oid);

	/* write back out to disk */
	{
		GString *buf;
		size_t count;
		buf = g_string_new("");
		intspan_serialize(used_oids, buf);
		lseek(fd, SEEK_SET, 0);
		count = strlen(buf->str);
		write(fd, buf->str, count);
		// DPRINTF(DBG_CODB, "rel ==> usedoids = %s\n", buf->str);
		ftruncate(fd, count);
		fsync(fd);
		flock(fd, LOCK_UN);
		close(fd);
		g_string_free(buf, 1);
	}

	intspan_destroy(used_oids);

	DPRINTF(DBG_CODB, "Released OID: %ld\n", oid->oid);

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
