/* 
 * `ccewrap' -- A safe (?) way to allow users to run applications through
 * 		scripts.
 * 		written by Mike Waychison <mwaychison@cobalt.com> 2000/07/05
 */

/* standard */
#include <glib.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <unistd.h>

/* cce includes */
#include <cce_common.h>
#include <cce.h>

/* uid/gid stuff */
#include <pwd.h>
#include <grp.h>
#include <sys/types.h>

#include "ccewrap_conf.h"
#include "ccewrap_program.h"

/* apparently "missing" slash is on the #define */
#define PROPER_PROGNAME CCEBINDIR "ccewrap"
#define CONF_FILE "/etc/ccewrap.conf"
#define CONF_DIRECTORY "/etc/ccewrap.d/"

static char *progname = NULL;

/* prototypes  */
void usage(void);
int isSystemAdministrator(cce_handle_t *cce, char *user);
int validateCommand(cce_handle_t *cce, char *, char *username, GList **validUsers);
char **setup_environment(char *user, char *pass, struct passwd *passwd);
int checkValidUser(cce_handle_t *cce, char **pseudoUser, GList *validUsers);

int 
main (int argc, char *argv[]) 
{
	int newUid, newGid;
	cce_handle_t *cce;
	char *pseudoUser;
	char *user, *pass;
	char **environment;
	struct passwd *userPasswd;
	GList *validUsers = NULL;

	progname = argv[0];

	/* check arg length */
	if (argc < 2) {
		usage();
		exit(42);
	}	

	/* set our var pointers */
	user = getenv("CCE_USERNAME");
	if (!user) {
		fprintf(stderr, "can't get CCE_USERNAME\n");
		usage();
		exit(43);
	}
		
	pass = getenv("CCE_SESSIONID");
	if (!pass) {
		pass = getenv("CCE_PASSWORD");
	}
	if (!pass) {
		fprintf(stderr, "can't get CCE_SESSIONID or CCE_PASSWORD\n");
		usage();
		exit(44);
	}

	/* start the logging */
	openlog("ccewrap", LOG_PID, LOG_AUTHPRIV);

	/* start up cce connection */
	cce = cce_handle_new();
	
	if (!cce_connect_cmnd(cce, CCESOCKET)) {
		/* can't connect to CCE */
		fprintf(stderr, "error connecting to CCE\n");
		CCE_SYSLOG("error connecting to CCE");
		exit(47);
	}

	/* connected to CCE */
	/* trying to authenticate */
	if (!(cce_authkey_cmnd( cce, user, pass) 
	 || cce_auth_cmnd(cce, user, pass))) {
		/* FAILED! */
		fprintf(stderr, "authentication failed\n");
		CCE_SYSLOG("authentication failed for a user claiming to be %s", user);
		exit(48);
	}


	/* pass in the current user, pull out a list of users
	 * that we are allowed to run as */
	if (!validateCommand(cce, argv[1], user, &validUsers)) {
		fprintf(stderr, "unknown command (validateCommand)\n");
		exit(51);
	}

        pseudoUser = user;
	if (!checkValidUser(cce, &pseudoUser, validUsers))
	{
		fprintf(stderr, "could not run as requested user\n");
		exit(52);
	}


		/* may proceed - get user uid/gid */
	userPasswd = getpwnam(pseudoUser);
	newUid = userPasswd->pw_uid;
	newGid = userPasswd->pw_gid;
       
	/* set some environment variables */
	environment = setup_environment(user, pass, userPasswd);
        	/* Attempt a change of uid/gid */
	if (initgroups(pseudoUser, newGid)) {
		perror("initgroups()");	
		CCE_SYSLOG("error calling initgroups for user: %s", pseudoUser);
		exit(49);
	}
	
	if (setgid(newGid)) {
		CCE_SYSLOG("error calling setgid for user: %s", pseudoUser);
		perror("setgid()");
		exit(50);
	}

	/* change this perm last! */
	if (setuid(newUid)) {
		CCE_SYSLOG("error calling setuid for user: %s", pseudoUser);
		perror("setuid()");
		exit(51);
	}

	/* log it */
	CCE_SYSLOG("running (%s)", argv[1]);

	/* clear up CCE connection */
	cce_bye_cmnd(cce);
	cce_handle_destroy(cce);

	/* call the app */
	if (execve(argv[1], &argv[1], environment)) {
		fprintf(stderr, "execv(%s): %s", argv[1], strerror(errno));
		return errno;
	}

	return 0;
}

int
checkValidUser(cce_handle_t *cce, char **pseudoUser, GList *validUsers) 
{
	/* iterate through and see if if we are allowed
	 * to run as the requested user */
	char *requested;
	GList *p;
	int found = 0;

	/* pick up the requested username */
	requested = getenv("CCE_REQUESTUSER");

	/* We are a system administator, we can run this 
	 * program */
	if (isSystemAdministrator(cce, *pseudoUser)) {
		/* check if the request is set */
		if (requested == NULL || !strcmp(requested, "")) {
			/* nothing was set */
			*pseudoUser = "root";
		} else {
			/* A user was requested, allow this
			 * request to go through */
			*pseudoUser = requested; 
		}
		return 1; /* a match was found */
	}
		
	/* get the defaults */
	if (requested == NULL || !strcmp(requested,"")) {
		  requested = *pseudoUser;
	}

	p = validUsers;
	while (p) {
	  char *valid = (char *)p->data;
	  if (
	    /* the request is exactly the same
	     * as what's offered */
	    !strcmp(requested, valid) ||
	    /* I specifically asked for my 
	     * username and the conf says I 
	     * can run as myself */
	    (!strcmp(valid, "") 
	    && (!strcmp(requested, *pseudoUser)))) {
	    found = 1;
	    break;
  	          }

	  p = g_list_next(p);
	}

	*pseudoUser = requested;

	if (!found) {
		CCE_SYSLOG("could not run as requested user: %s (validateCommand)", requested);
	}

	return found;
}
	


char **
setup_environment(char *user, char *pass, struct passwd *userPasswd)
{
	GString *envstr;
	char **environment;
	int i = 0;
	char *p;

	/* malloc the environment..  we shouldn't have to worry
	 * about freeing this.  Notice that you may need to make
	 * the size of this array larger accordingly */
	environment = malloc(sizeof(char *) * 20);

	envstr = g_string_new("CCE_USERNAME=");
	g_string_append(envstr, user);
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("CCE_SESSIONID=");
	g_string_append(envstr, pass);
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("HOME=");
	g_string_append(envstr, userPasswd->pw_dir);
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("USER=");
	g_string_append(envstr, userPasswd->pw_name);
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("USERNAME=");
	g_string_append(envstr, userPasswd->pw_name);
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	p = getenv("UID");
	if (p) {
		envstr = g_string_new("UID=");
		g_string_append(envstr, p);
		environment[i++] = envstr->str;
		g_string_free(envstr, 0);
	}

	p = getenv("USERID");
	if (p) {
		envstr = g_string_new("USERID=");
		g_string_append(envstr, p);
		environment[i++] = envstr->str;
		g_string_free(envstr, 0);
	}

	envstr = g_string_new("SHELL=/bin/sh");
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("PATH=/bin:/usr/bin:/usr/local/bin");
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("OSTYPE=Linux");
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	envstr = g_string_new("MAIL=/var/spool/mail/");
	g_string_append(envstr, userPasswd->pw_name);
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	p = getenv("LANG");
	if (p) {
		envstr = g_string_new("LANG=");
		g_string_append(envstr, p);
		environment[i++] = envstr->str;
		g_string_free(envstr, 0);
	}

	p = getenv("LANGUAGE");
	if (p) {
		envstr = g_string_new("LANGUAGE=");
		g_string_append(envstr, p);
		environment[i++] = envstr->str;
		g_string_free(envstr, 0);
	}

	envstr = g_string_new("PERL_BADLANG=0");
	environment[i++] = envstr->str;
	g_string_free(envstr, 0);

	p = getenv("PWD");
	if (p) {
		envstr = g_string_new("PWD=");
		g_string_append(envstr, p);
		environment[i++] = envstr->str;
		g_string_free(envstr, 0);
	}

	environment[i] = NULL;

	return environment;
	
};



int validateCommand(cce_handle_t *cce, char *prog, char *username, GList **validUsers) {
	FILE *fd;
	char *buf;
	int bool = 0;
	ccewrapconf conf;

	buf = (char *)malloc(sizeof(char)*1024);

	conf = ccewrapconf_new(cce, username);

	/* take care of the new format */
	ccewrapconf_parse_dir(conf, CONF_DIRECTORY);


	/* take care of the old /etc/ccewrap.conf format */
	fd = fopen(CONF_FILE, "r");
	if (!fd) {
		/* file doesn't exist? */
		CCE_SYSLOG("Warning: %s does not exist\n", CONF_FILE);
		return 1;
	}
	while ((buf = (char *)fgets((char *)buf, 1023, fd))) {
		sscanf(buf, "%s\n", buf);
		if (buf[0] != '#') {
			ccewrapconf_program program;
			program = ccewrapconf_program_new();
			ccewrapconf_program_setname(program, strdup(buf));
			ccewrapconf_program_setfree_name(program, 1);
			ccewrapconf_addprogram(conf, program);
		}
	}
	fclose(fd);

	bool = ccewrapconf_checkprogram(conf, prog, validUsers);

	free(buf);
	ccewrapconf_free(conf);
	return bool;
}

void usage(void) {
	fprintf(stderr, "usage: %s program [arguments, ...]\n", progname);
	fprintf(stderr, "\treads environment variables:\n");
	fprintf(stderr, "\tCCE_USERNAME and one of CCE_SESSIONID, CCE_PASSWORD\n");
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
