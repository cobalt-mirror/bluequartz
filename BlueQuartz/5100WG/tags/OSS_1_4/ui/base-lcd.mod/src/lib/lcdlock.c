/* lock functions for the panel utils 
 *
 * NOTE: as we want to make sure that all of the lcd utils know
 *       about each other, this is almost intentionally 
 *       single-threaded.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <errno.h>

#define __USE_GNU
#include <fcntl.h>

#include "lcdutils.h"

#define LOCKFILE "/etc/locks/.lcdlock"
static int lockfd = -1;

extern int errno;

/* lcd locking */
int lcd_lock(void)
{
	char buf[16];
	struct flock lock;
	struct stat st1, st2;

	if (lockfd > -1) /* already locked */
		return -2;

	/*
	 * verify file before opening it
	 */

	if (lstat(LOCKFILE, &st1) < 0) {
		/*
		 * does not exist - create
		 */
		lockfd = open(LOCKFILE, O_RDWR | O_TRUNC | O_EXCL | O_CREAT, 0600);
		if (lockfd < 0) {
			fprintf(stderr, "ERROR: unable to open file '%s' for write: %s\n",
				LOCKFILE, strerror(errno));
			return lockfd = -1;
		}
	}
	else {
		/*
		 * exists - allow only regular file, not links
		 */
		if (!S_ISREG(st1.st_mode)) {
			fprintf(stderr, "ERROR: file '%s' has invalid mode: %d\n",
				LOCKFILE, st1.st_mode);
			return lockfd = -1;
		}

		/*
		 * allow only files with 1 link (itself)
		 */
		if (st1.st_nlink != 1) {
			fprintf(stderr, "ERROR: file '%s' has invalid link count: %d != 1\n",
				LOCKFILE, st1.st_nlink);
			return lockfd = -1;
		}

		/*
		 * open it for write, overwrite existing file
		 */
		lockfd = open(LOCKFILE, O_RDWR | O_NOFOLLOW | O_TRUNC, 0600);
		if (lockfd < 0) {
			fprintf(stderr, "ERROR: unable to overwrite file '%s': %s\n",
				LOCKFILE, strerror(errno));
			return lockfd = -1;
		}

		/*
		 * stat again and verify inode/owner/link count
		 */
		fstat(lockfd, &st2);
		if (st2.st_ino != st1.st_ino || st2.st_uid != st1.st_uid || st2.st_nlink != 1) {
			fprintf(stderr, "ERROR: unable to verify file '%s'\n", LOCKFILE);
			close(lockfd);
			return lockfd = -1;
		}
	}	  

	/*
	 * establish a fcntl lock on it
	 * so it dies when the process dies
	 */
	memset(&lock, 0, sizeof(lock));
	lock.l_type = F_WRLCK;
	if (fcntl(lockfd, F_SETLK, &lock) < 0) {
		fprintf(stderr, "ERROR: unable to establish fcntl lock on file '%s'\n",
			LOCKFILE);
		close(lockfd);
		return lockfd = -1;
	}

	memset(&buf[0], 0, sizeof(buf));
	sprintf(buf, "%d", getpid());
	if (write(lockfd, buf, strlen(buf)) < 0) {
		fprintf(stderr, "ERROR: unable to write PID '%s' to file '%s'\n",
			buf, LOCKFILE);
		close(lockfd);
		return lockfd = -1;
	}

	return 0;
}

/* this assumes that every call to lcd_unlock really means it. */
void lcd_unlock(void)
{
	if (lockfd > -1) {
		close(lockfd);
		lockfd = -1;
	}
	unlink(LOCKFILE);
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
