/*
 *  $Source$
 *  $Revision: 3 $
 *  $Date: 2003-07-17 15:19:15 +0000 (Thu, 17 Jul 2003) $
 *  $Author: will $
 *  $State$
 *
 *  $Locker$
 *  
 */

#ifndef lint
static char rcs_header[] = "$Header$";
#endif

#include <stdio.h>
#include <sysexits.h>
#include <unistd.h>
#include <pwd.h>
#include <grp.h>
#include <sys/types.h>

#if defined(sun) && defined(sparc)
#include <stdlib.h>
#endif


#ifndef STRCHR
#  include <string.h>
#  define STRCHR(s,c) strchr(s,c)
#endif

#ifndef BIN
#  define BIN "/usr/local/mail/majordomo"
#endif

#ifndef PATH
#  define PATH "PATH=/bin:/usr/bin:/usr/ucb"
#endif

#ifndef HOME
#  define HOME "HOME=/usr/local/mail/majordomo"
#endif

#ifndef SHELL
#  define SHELL "SHELL=/bin/sh"
#endif

char * new_env[] = {
    HOME,		/* 0 */
    PATH,		/* 1 */
    SHELL,		/* 2 */
#ifdef MAJORDOMO_CF
    MAJORDOMO_CF,	/* 3 */
#endif
    0,		/* possibly for USER or LOGNAME */
    0,		/* possible for LOGNAME */
    0,          /* possibly for timezone */
    0,          /* possibly for fully qualified domainname (FQDN) */
    0,          /* possibly for virtual tag (VIRTUAL) */
    0
};
    
void usage(argv)
    char *argv[];
{
    fprintf(stderr, "USAGE: %s [-f <fqdn>] [-v <virtual>] program [<arg> ...]\n", argv[0]);
    fprintf(stderr, "\twhere <virtual> is the virtualizing tag for the virtual host <fqdn>\n");
    exit(EX_USAGE);
}

void merror(argv)
    char *argv[];
{
    fprintf(stderr, "%s: error: malloc failed\n", argv[0]);
    exit(EX_OSERR);
}

int new_env_size = 9;			/* to prevent overflow problems */

main(argc, argv, env)
    int argc;
    char * argv[];
    char * env[];

{
    char * prog;
    int e, i;
    extern int errno;
    char *fqdn;
    char *virtual;
    int first;

    if (argc < 2) usage(argv);

    /* get the options */
    fqdn = virtual = NULL;
    for (i = 1; i < 4; i += 2) {
	if (argv[i][0] != '-') break;
	if (argc < i+3) usage(argv);

	if (!strcmp(argv[i], "-f")) {
            if ((fqdn = (char *) malloc(strlen(argv[i+1]) + 6)) == NULL)
		merror(argv);
	    sprintf (fqdn, "FQDN=%s", argv[i+1]);
	}
	else if (!strcmp(argv[i], "-v")) { 
            if ((virtual = (char *) malloc(strlen(argv[i+1]) + 9)) == NULL)
		merror(argv);
	    sprintf (virtual, "VIRTUAL=%s", argv[i+1]);
	}
	else usage(argv);
    }
    first = i;

    /* if the command contains a /, then don't allow it */
    if (STRCHR(argv[first], '/') != (char *) NULL) {
	/* this error message is intentionally cryptic */
	fprintf(stderr, "%s: error: insecure usage\n", argv[0]);
	exit(EX_NOPERM);
    }

    if ((prog = (char *) malloc(strlen(BIN) + strlen(argv[first]) + 2)) == NULL)
	merror(argv);

    sprintf(prog, "%s/%s", BIN, argv[first]);

    /*  copy the "USER=" and "LOGNAME=" envariables into the new environment,
     *  if they exist.
     */

    /* FQDN and VIRTUAL: commandline overrides environment variables;
       environment variables override any computation */
    if (fqdn == NULL || virtual == NULL) {
        for (i = 0 ; env[i] != NULL; i++) {
	    if (!fqdn && !strncmp(env[i], "FQDN=", 5)) {
		if ((fqdn = (char *) malloc(strlen(env[i]) + 1)) == NULL)
		    merror(argv);
		strcpy (fqdn, env[i]);
	    }
	    if (!virtual && !strncmp(env[i], "VIRTUAL=", 8)) {
		if ((virtual = (char *) malloc(strlen(env[i]) + 1)) == NULL)
		    merror(argv);
		strcpy (virtual, env[i]);
	    }
	}
    }

    /* compute fqdn if it is still unset */
    if (!fqdn) {
	char hostname[200], domainname[200];
	extern int errno;

	if (gethostname (hostname, 199)) {
	    fprintf(stderr, "%s: error: cannot find hostname\n", argv[0]);
	    exit(errno);
	}
	if (getdomainname (domainname, 199)) {
	    fprintf(stderr, "%s: error: cannot find domainname\n", argv[0]);
	    exit(errno);
	}

	if ((fqdn = (char *) malloc(strlen(hostname) + strlen(domainname) + 7)) == NULL)
	    merror(argv);
	if (strcmp (domainname, "(none)"))
	    sprintf (fqdn, "FQDN=%s.%s", hostname, domainname);
	else
	    sprintf (fqdn, "FQDN=%s", hostname);
    }

#ifdef MAJORDOMO_CF
    e = 4; /* the first unused slot in new_env[] */
#else
    e = 3; /* the first unused slot in new_env[] */
#endif
    for (i = 0 ; env[i] != NULL && e <= new_env_size; i++) {
	if ((strncmp(env[i], "USER=", 5) == 0) ||
	    (strncmp(env[i], "TZ=", 3) == 0) ||
	    (strncmp(env[i], "LOGNAME=", 8) == 0)) {
	    new_env[e++] = env[i];
	}
    }
    if (fqdn && e <= new_env_size) new_env[e++] = fqdn;
    if (virtual && e <= new_env_size) new_env[e++] = virtual;


#if defined(SETGROUP)
/* renounce any previous group memberships if we are running as root */
    if (geteuid() == 0) { /* Should I exit if this test fails? */
    char *setgroups_used = "setgroups_was_included"; /* give strings a hint */
#if defined(MAIL_GNAME)
    char *gnames[2] =  { POSIX_GNAME, MAIL_GNAME };
    gid_t gids[2];
    for (i = 0; i < 2; ++i)
#else
    char *gnames[1] =  { POSIX_GNAME };
    gid_t gids[1];
    for (i = 0; i < 1; ++i)
#endif
    {
	struct group *gr;
	if ((gr = getgrnam (gnames[i])) == NULL)
	    fprintf(stderr, "%s: error getgrnam(%s) failed errno %d", argv[0],
		gname, errno);
	else
	    gids[i] = gr->gr_gid;
    }
#if defined(MAIL_GNAME)
    if (setgroups(2, gids) == -1)
#else
    if (setgroups(1, gids) == -1)
#endif
    {
	fprintf(stderr, "%s: error setgroups failed errno %d", argv[0],
		errno);
    }
}
#endif
	  

#ifdef POSIX_GNAME
{
    struct group *gr;
    if ((gr = getgrnam (POSIX_GNAME)) != NULL)
        setgid(gr->gr_gid);
    else
	fprintf(stderr, "%s: error getgrnam failed errno %d", argv[0],
		errno);
}
#else
    setgid(getegid());
#endif

#ifdef POSIX_UNAME
{
    struct passwd *pw;
    if ((pw = getpwnam (POSIX_UNAME)) != NULL)
        setuid(pw->pw_uid);
    else
	fprintf(stderr, "%s: error getpwnam failed errno %d", argv[0],
		errno);
}
#else
    setuid(geteuid());
#endif

    if ((getuid() != geteuid()) || (getgid() != getegid())) {
	fprintf(stderr, "%s: error: Not running with proper UID and GID.\n", argv[0]);
	fprintf(stderr, "    Make certain that wrapper is installed setuid, and if so,\n");
	fprintf(stderr, "    recompile with POSIX flags.\n");
	exit(EX_SOFTWARE);
    }

    execve(prog, argv+first, new_env);

    /* the exec should never return */
    fprintf(stderr, "wrapper: Trying to exec %s failed: ", prog);
    perror(NULL);
    fprintf(stderr, "    Did you define PERL correctly in the Makefile?\n");
    fprintf(stderr, "    HOME is %s,\n", HOME);
    fprintf(stderr, "    PATH is %s,\n", PATH);
    fprintf(stderr, "    SHELL is %s,\n", SHELL);
    fprintf(stderr, "    MAJORDOMO_CF is %s\n", MAJORDOMO_CF);
    exit(EX_OSERR);
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
