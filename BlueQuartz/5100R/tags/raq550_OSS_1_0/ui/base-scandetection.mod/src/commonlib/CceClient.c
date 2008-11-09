/*
** Copyright 2001 Sun Microsystems, Inc. All rights reserved.
** $Id: CceClient.c,v 1.5.2.2 2002/02/20 18:01:10 ge Exp $
*/

/*
 * This code is a hodgepodge to get email alerts to be sent out i18n
 * style.  The first part comes from the swatch+cce code and the second
 * part puts it all together.  I choose to use this client because its
 * i18n compliant and ours wasn't.  
 */

#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <ctype.h>

//#include <glib.h>
#include <cce/cce.h>
#include <cce/i18n.h>

#include "CceClient.h"
#include "libphoenix.h"

i18n_handle *i18n = NULL;
cce_handle_t *cce = NULL;

/***********************************************************************/

#define DEF_LOCALE      "en"

/* where to look for the sys locale */
#define SYS_LOCALE_FILE "/etc/cobalt/locale"

#define DOMAIN    "base-scandetection"

/* how to send mail */
#define MAIL_CMD  "/usr/sausalito/bin/i18nmail.pl"
#define MAIL_SUBJ "alertEmailSubject"
#define MAIL_BODY "alertEmailBody"


/*
 *  This code was mercilessly stolen from swatch+cce
 */
static int
send_mail(char *emails, char **messages, char* locale, i18n_vars *v) 
{
	char *cmd;
	char *tmpfile;
	FILE *fp;
	int fd;
	int len;
	char *subj;

#if 0	
	if (!messages[0] || !*emails) {
#endif
	if (!*emails) {
		/* no messages to report, or no one to report them to */
		return 0;
	}

	/* get and open a temp file */
	tmpfile = strdup("/tmp/.alertd-XXXXXX");
	fd = mkstemp(tmpfile);
	if (fd < 0) {
	  LOG(LOG_ERROR, "send_mail: mkstemp(): %s\n", strerror(errno));
	  return -1;
	}
	fp = fdopen(fd, "w");
	if (!fp) {
		LOG(LOG_ERROR, "send_mail: fdopen(): %s\n", strerror(errno));
		unlink(tmpfile);
		free(tmpfile);
		return -1;
	}

	/* put the i18ned body */
#if 0
	v = i18n_vars_new();
#endif
	fprintf(fp, "%s\n\n", i18n_get(i18n, MAIL_BODY, DOMAIN, v));
#if 0
	i18n_vars_destroy(v);
	/* write each message */
	for (i = 0; messages[i]; i++) {
		fprintf(fp, "* %s\n", messages[i]);
	}
#endif
	fclose(fp);

	/* i18n the subject */
#if 0
	v = i18n_vars_new();
#endif
	subj = i18n_get(i18n, MAIL_SUBJ, DOMAIN, v);
#if 0
	18n_vars_destroy(v);
#endif
	/* shut Perl up */
	setenv("PERL_BADLANG", "0", 1);

	/* "MAIL_CMD -s "subj" emails < tmpfile" */
	/* OR, if locale is specified, 
	   "MAIL_CMD -l locale -s "subj" emails < tmpfile" */
	len = strlen(MAIL_CMD) + 3 + 2 + strlen(subj) + 2 
		+ strlen(emails) + 3 + strlen(tmpfile) + 1;
	if (locale) {
	  len += 4 + strlen(locale);
	}

	cmd = malloc(len);
	if (!cmd) {
		LOG(LOG_ERROR, "send_mail: malloc(): %s\n", strerror(errno));
		unlink(tmpfile);
		free(tmpfile);
		return -1;
	}

	if (locale) {
	  snprintf(cmd, len, "%s -l %s -s \"%s\" %s < %s", MAIL_CMD, locale,
		   subj, emails, tmpfile);
	} else {
	  snprintf(cmd, len, "%s -s \"%s\" %s < %s", MAIL_CMD, 
		   subj, emails, tmpfile);
	}
	LOG(LOG_DEBUG, "sending mail (%s)\n", cmd);

	if (system(cmd)) {
		LOG(LOG_ERROR, "unexpected mailer error (%s)\n", strerror(errno));
		unlink(tmpfile);
		free(tmpfile);
		return -1;
	}

	unlink(tmpfile);
	free(tmpfile);

	return 0;
}



/* this is another of those static-memory-returning functions.  
 * freeing it's return would be a bad idea
 */
static char *
get_sys_locale(void)
{
	int fd,r;
	static char buf[6];

	fd=open(SYS_LOCALE_FILE, O_RDONLY);
	if (fd < 0) {
		snprintf(buf, sizeof(buf), "%s", DEF_LOCALE);
	}

	r=read(fd, buf, sizeof(buf)-1);
	buf[r]='\0';

	return buf;
}


/* FIXME: do better cleanup in here */
static char *
get_adm_locales(cce_handle_t *cce)
{
	cce_props_t *props;
	cscp_oid_t adm_oid;
	GSList *oidlist;

	props = cce_props_new();
	if (!props) {
		return NULL;
	}

	cce_props_set(props, "name", "admin");
	oidlist = cce_find_cmnd(cce, "User", props);
	cce_props_destroy(props);
	if (!oidlist) {
		return NULL;
	}
	
	adm_oid = (cscp_oid_t)oidlist->data;

	props = cce_get_cmnd(cce, adm_oid, "");
	if (!props) {
		return NULL;
	}

	return cce_props_get(props, "localePreference");
}

/************************************************************************/

/* 
 * Sample packets - break them down into their components
 *
 * eth0:restrict: tcp 172.25.91.7/22 -> 172.25.91.1/2238 280 (3)
 * eth0:portscan: 3/1/icmp 172.25.91.7 <- 129.148.1.37 56 (2)
 */

#define NEXT_TOKEN {  while ( isspace(*p) )   \
                        p++;                  \
                      q = p;                  \
                      while (! isspace(*p) )  \
                        p++;                  \
                      *p++ = '\0';            \
                   };

static void
parse_alert(const char *message, i18n_vars *v)
{
    char msg[256];
    char *p, *q, *r;
    char *src, *dst;

    p = strncpy(msg, message, sizeof(msg));

    NEXT_TOKEN;
    /* LOG(LOG_DEBUG, "Iface=%s\n", q); */
    if ((r = strchr(q, ':')) != NULL)
        *r = '\0';
    i18n_vars_add(v, "interface", q);

    NEXT_TOKEN;
    /* LOG(LOG_DEBUG, "Protocol=%s\n", q); */
    i18n_vars_add(v, "protocol", q);
    
    NEXT_TOKEN;
    /* LOG(LOG_DEBUG, "IP1=%s\n", q); */
    src = q;

    NEXT_TOKEN;
    /* LOG(LOG_DEBUG, "Direction=%s\n", q); */
    r = q;

    NEXT_TOKEN;
    /* LOG(LOG_DEBUG, "IP2%s\n", q); */
    dst = q;

    /* Based on direction, setup fields */
    if (! strcmp(r, "->")) {
        i18n_vars_add(v, "direction", i18n_get(i18n, "outbound", DOMAIN, v));
    } else {
        char *t = src;
	src = dst;
	dst = t;
        i18n_vars_add(v, "direction", i18n_get(i18n, "inbound", DOMAIN, v));
    }

    /* Split the ip/port.  Icmps don't have port so leave empty. */
    if ((r = strchr(src, '/')) != NULL) {
        *r = '\0';
	i18n_vars_add(v, "srcport", r+1);
    } else {
        i18n_vars_add(v, "srcport", "");
    }
    i18n_vars_add(v, "srcaddr", src);

    if ((r = strchr(dst, '/')) != NULL) {
        *r = '\0';
	i18n_vars_add(v, "dstport", r+1);
    } else {
        i18n_vars_add(v, "dstport", "");
    }
    i18n_vars_add(v, "dstaddr", dst);

    NEXT_TOKEN;
    /* LOG(LOG_DEBUG, "Size=%s\n", q); */
    i18n_vars_add(v, "pktsize", q);
}

/* cce_connect() is defined in cce.h */

static int
cce_connect_local(void)
{
    if (! (cce = cce_handle_new()) ) {
        LOG(LOG_BASE, "cannot get CCE handle");
	return 0;
    }

    if (!cce_connect_cmnd(cce, NULL)) {
        LOG(LOG_BASE, "cannot connect to CCE");
	cce_handle_destroy(cce);
	return 0;
    }
    return 1;
}

static void
cce_bye(void)
{
    cce_bye_cmnd(cce);
    cce_handle_destroy(cce);
}

static int
i18n_connect(void)
{
    char *locale;

    /* set up i18n */
    locale=get_adm_locales(cce);
    if(strcmp(locale,"browser")==0){
        locale=get_sys_locale();
    }
    i18n = i18n_new(DOMAIN, locale);
    if (!i18n) {
	LOG(LOG_BASE, "cannot initialize i18n");
	return 0;
    }
    return 1;
}

/* 
 * This code is pretty messy.  I guess that happens when two disjoint
 * pieces of code try to merge together.
 */
int send_alert(
	       time_t timestamp,
	       const char *to,
	       const char *subject,
	       const char *message
	       )
{
    char *subj_i18n;
    i18n_vars *v;
    LOG(LOG_BASE, "Sending alert to %s", to);

    if (! cce_connect_local())
        return -1;

    if (! i18n_connect())
        return -1;

    /* The subject is an i18n tag; we look it up, take its value and add
       to the vars array to get substituted in the email. */
    v = i18n_vars_new();
    subj_i18n = i18n_get(i18n, (char *)subject, DOMAIN, v);
    /* This replaces [[VAR.variable]] in .po file */
    i18n_vars_add(v, "subject", subj_i18n);
    i18n_vars_add(v, "timestamp", i18n_get_datetime(i18n, timestamp));
    i18n_vars_add(v, "type", subj_i18n);
    i18n_vars_add(v, "logentry", (char *) message);
    parse_alert(message, v);
    send_mail((char *)to, NULL, NULL, v);

#if 0
    // Example property change
    props = cce_props_new();
    cce_props_set(props, "monitor", "987");
    cce_set_cmnd(cce, oid, "Fwall", props);
    cce_props_destroy(props);
#endif

    i18n_vars_destroy(v);
    i18n_destroy(i18n);
    cce_bye();

    return 0;
}

#ifdef UNDEF

/* NO MORE CCE ACCESS FROM C PROGRAMS! */

/*
 *  get_admin_email - look in CCE and return admin email list, space separated
 *
 *  Args:    buffer and size
 *  Returns: true if successful, false otherwise
 */               
int get_admin_email(char *buffer, int size)
{
    cce_props_t *props;
    cscp_oid_t oid;
    GSList *oids, *p;
    char *emails, *q;
    int i, remainder = size;

    if (! cce_connect_local())
        return(0);

    if ((oids = cce_find_cmnd(cce, "System", NULL)) == NULL) {
        LOG(LOG_BASE, "Unable to get System from CCE");
	cce_bye();
	return(0);
    }

    oid = (cscp_oid_t)oids->data;   
    props = cce_get_cmnd(cce, oid, "Scandetection");

    emails = cce_props_get(props, "alertEmail");
   
    snprintf(buffer, strlen(emails) +1 , "%s", emails); 
    cce_bye();
    return( strlen(buffer) );
}

/*
 *  isPortScanOn
 * 
 * returns -1 when System.Scandetection isn't found - fwall not installed
 * returns 0 if System.Scandetection.paranoiaLevel == (0 || 1)
 * returns 1 if System.Scandetection.paranoiaLevel == 2
 */               
int isPortScanOn(void)
{
    cce_props_t *props;
    cscp_oid_t oid;
    GSList *oids, *p;
    char *paranoiaLevelChar;
    char *alertMeChar;
    int paranoiaLevel, alertMe;

    if (! cce_connect_local())
        return(-1);

    if ((oids = cce_find_cmnd(cce, "System", NULL)) == NULL) {
        LOG(LOG_BASE, "Unable to get System from CCE");
	cce_bye();
	return(-1);
    }

    oid = (cscp_oid_t)oids->data;   
    props = cce_get_cmnd(cce, oid, "Scandetection");

    paranoiaLevelChar = cce_props_get(props, "paranoiaLevel");	 
    alertMeChar = cce_props_get(props, "alertMe");
//LOG(LOG_BASE, "PAR=%s ALERT=%s", paranoiaLevelChar, alertMeChar);

    paranoiaLevel = (int)paranoiaLevelChar[0] - 48;
    alertMe = (int)alertMeChar[0] - 48;

    if (paranoiaLevel > 0 && alertMe > 0) {
	cce_bye();
//LOG(LOG_BASE, "RETURNING TRUE");
	return(1);
    }    


    cce_bye();
//LOG(LOG_BASE, "RETURNING FALSE");
    return(0);
}
#endif /*UNDEF*/
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
