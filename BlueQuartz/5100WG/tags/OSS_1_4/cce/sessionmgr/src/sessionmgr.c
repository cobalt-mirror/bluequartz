/* $Id: sessionmgr.c 3 2003-07-17 15:19:15Z will $ 
 *
 * Implements session manager functionality.
 *
 * jmayer, thockin (c) Cobalt Networks.
 */

#ifdef DEBUG_SESSION
#	define CCE_ENABLE_DEBUG
#else
#	undef CCE_ENABLE_DEBUG
#endif
#include <cce_debug.h>

#include <cce_common.h>
#include <sessionmgr.h>

#include <stdio.h>
#include <unistd.h>
#include <sys/types.h>
#include <string.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <utime.h>
#include <dirent.h>
#include <time.h>
#include <fcntl.h>
#include <glib.h>


/* some constants */
#define IDLEN 63
#define NAMELEN	31
#define SESSIONDIR CCESESSIONSDIR
#define DEVRAND "/dev/urandom"

/* what's inside a session object */
struct cce_session_struct {
	char session_id[IDLEN+1];
	char username[NAMELEN+1];
};

static void makedirectory(); 
static int isunique(cce_session *s);
static void cce_session_save(cce_session *s);
static cce_session *cce_session_load(char *session_id);
static inline char *session_filename(char *sessid);
static inline int isalphanumeric(char c);

/* our keyspace */
static char *alphanumeric=
	"abcdefghijklmnopqrstuvwxyz"
	"ABCDEFGHIJKLMNOPQRSTUVWXYZ"
	"0123456789";

/* create a new session for the user */
cce_session *
cce_session_new(char *username)
{
	cce_session *s;
	int i, fd;
  
	/* make sure environment is ok */
	makedirectory();

	/* get memory */
	s = (cce_session *)malloc(sizeof(cce_session));
	if (!s) 
		return NULL;

	/* copy in username */
	strncpy(s->username, username, NAMELEN);
	s->username[NAMELEN] = '\0';

	/* get a random string for the session_id */
	fd = open(DEVRAND, O_RDONLY);
	if (!fd) {
		CCE_SYSLOG("can not access %s: %m", DEVRAND);
		free(s);
		return NULL;
	}
   
   	/* were we a valid user of "" (anonymous) */
	if (strcmp(username, "")) {
	   	s->session_id[0] = '\0';
		/* make sure the sessionid is unique */
		while (!isunique(s)) {
			unsigned int seed;

			read(fd, (char *)&seed, sizeof(unsigned int));
			srandom(seed);

			for (i = 0; i < IDLEN; i++) {
				long c = random();
				s->session_id[i] = 
					alphanumeric[c % strlen(alphanumeric)];
			}
			s->session_id[IDLEN] = '\0';
		}

		/* make it valid */
		cce_session_save(s);
	} else {
		for (i = 0; i < IDLEN; i++) {
			s->session_id[i] = '0';
		}
		s->session_id[IDLEN] = '\0';
	}
  
	close(fd);

	return s;
}

/* attempt to reinstate a session */
cce_session *
cce_session_resume(char *username, char *session_id)
{
	struct stat statbuf;
	time_t age;
	char *filename;
	cce_session *s;
  
	filename = session_filename(session_id);

	/* does this session exist? */
	if (stat(filename, &statbuf)) {
		free(filename);
		return NULL;
	}	

	/* load 'er up */ 
	s = cce_session_load(session_id);
	if (!s) {
		free(filename);
		return NULL;
	}

	/* is it the right user? */
	if (strcmp(cce_session_getuser(s), username)) {
		free(filename);
		cce_session_destroy(s);
		return NULL;
	}

	/* has this session expired? */
	age = time(NULL) - statbuf.st_mtime;
	if ((age < 0) || (age > session_timeout)) {
		/* session has expired indeed! */
		cce_session_expire(s);
		cce_session_destroy(s);
		free(filename);
		return NULL;
	}
	
	free(filename);
	return s;
}

/* re-start the timestamp on a session */
void
cce_session_refresh(cce_session *s)
{
	char *filename;

	if (!s) {
		return;
	}
  
	filename = session_filename(cce_session_getid(s));

	/* touch file */
	utime(filename, NULL);
	
	free(filename);
}

/* destroy a session object */
void
cce_session_destroy(cce_session *s)
{
	if (s) {
		free(s);
	}
}

/* get the session_id */
char *
cce_session_getid(cce_session *s)
{
	if (s) {
		return s->session_id;
	} else {
		return "";
	}
}

/* get the username */
char *
cce_session_getuser(cce_session *s)
{
	if (s) {
		return s->username;
	} else {
		return "";
	}
}

/* end a session's valid lifespan */
int
cce_session_expire(cce_session *s)
{
	char *filename;
	int r;

	if (!s) {
		return -1;
	}
  
	filename = session_filename(s->session_id);

	r = unlink(filename);

	free(filename);	

	return r;
}

/* cleanup old sessions */
void
cce_session_cleanup(void)
{
	DIR *dir;
	struct dirent *dirent;
	char *file = NULL;
	struct stat buf;

	dir = opendir(SESSIONDIR);
	if (!dir) {
		CCE_SYSLOG("could not open session directory %s", SESSIONDIR);
		return;
	}

	/* for each session file */
	while ((dirent = readdir(dir))) {
		int len;
		int age;

		if (dirent->d_name[0] == '.') {
			continue;    /* skip dot files */
		}

		len = strlen(dirent->d_name) + strlen(SESSIONDIR) + 2;
		file = (char *)malloc(len);
		snprintf(file, len, "%s/%s", SESSIONDIR, dirent->d_name);

		if (stat(file, &buf)) {
			continue;
		}

		age = time(NULL) - buf.st_mtime;
		if ((age < 0) || (age > session_timeout)) {
			unlink(file);
		}
		free(file);
	}
	closedir(dir);
}

/*
 * helper functions 
 */

/* write a session to disk, essentially making it valid */
static void
cce_session_save(cce_session *s)
{
	int fd;
	char *filename;

	if (!s) {
		return;
	}

	filename = session_filename(s->session_id);

	fd = open(filename, O_RDWR | O_CREAT | O_TRUNC, 0600);
	free(filename);

	if (!fd) 
		return;
	write(fd, s->username, strlen(s->username));
	
	close(fd);
}

/* load a session from disk */
static cce_session *
cce_session_load(char *session_id)
{
	int fd;
	char *filename;
	cce_session *s;
	int r; 
	
	s = (cce_session *)malloc(sizeof(cce_session));
	if (!s) 
		return NULL;
	
	filename = session_filename(session_id);
	fd = open(filename, O_RDONLY, 0600);
	free(filename);
	
	if (!fd) 
		return NULL;

	r = read(fd, s->username, NAMELEN); 
	s->username[r] = '\0';
	strncpy(s->session_id, session_id, IDLEN);
	s->session_id[IDLEN] = '\0';

	close(fd);

	return s;
}

/* 
 * makedirectory: makes sure the appropriate directories exist and
 * have the right permissions.
 */
static void 
makedirectory()
{
	struct stat statbuf;

	if (stat(SESSIONDIR, &statbuf)) {
		mkdir(SESSIONDIR, S_IRWXU);
	}
	chmod(SESSIONDIR, S_IRWXU);
}

/* tell me if a session is unique enough */
static int 
isunique(cce_session *s)
{
	struct stat buf;
	char *filename;
	int r;
  
  	if (!s || !s->session_id[0]) {
		return 0;
	}

	filename = session_filename(s->session_id);

	/* if stat succeeds ( == 0), we fail (==0) */
	r = (stat(filename, &buf));

	free(filename);

	return r;
}

static inline char *
session_filename(char *sessid)
{
	GString *filename;
	char *r;

	if (!sessid) {
		return NULL;
	}

        /*
         * Make sure the key is in our keyspace.  i.e. reject
         * keys that looks like "../../../../tmp/file". 
         */
        for (r = sessid; *r != '\0'; r++) {
                if (! isalphanumeric(*r))
                        return NULL;
        }

	filename = g_string_sized_new(strlen(SESSIONDIR) + 1 + IDLEN);
	g_string_append(filename, SESSIONDIR);
	g_string_append_c(filename, '/');
	g_string_append(filename, sessid);

	r = filename->str;
	g_string_free(filename, 0);

	return r;
}

/* Is the character in our keyspace? */
static inline int
isalphanumeric(char c)
{
	char *p;

	for (p = alphanumeric; *p != '\0'; p++) {
		if (*p == c)
			return 1;
	}
	return 0;
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
