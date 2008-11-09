/* $Id: ccewrap_conf.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <sys/types.h>
#include <sys/stat.h>
#include <dirent.h>
#include <glib.h>

#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <errno.h>

#include "../include/xml_parse.h"

#include "ccewrap_conf.h"
#include "ccewrap_program.h"
#include "../include/cce.h"
#include "../include/cce_common.h"

struct ccewrapconf_t {
	/* list of ccewrapconf_program s */
	GList *programs;
	cce_handle_t *cce;
	char *authuser;
	char *usercaps;
};

static int isdir(char *path);
static int ccewrapconf_add_elements(struct ccewrapconf_t *conf, GList *list);

static int
isdir(char *path)
{
	struct stat s;
	int r;

	if (stat(path, &s)) {
		CCE_SYSLOG("Error: Could not stat: %s\n", path);
		return 0;
	}

	r = S_ISDIR(s.st_mode);

	return r;
}

int
ccewrapconf_parse_dir(struct ccewrapconf_t *conf, char *dname)
{
	DIR *dir;
	struct dirent *dirent;
	char *filename = NULL;
	int ret = 0;

	if (!isdir(dname)) {
		return -1;
	}

	dir = opendir(dname);
	if (!dir) {
		CCE_SYSLOG("Could not open directory %s: %s", dname, strerror(errno));
		return -1;
	}

	while ((dirent = readdir(dir))) {
		int len;
		if (dirent->d_name[0] == '.')
			continue;	/* skip dot files */

		len = strlen(dirent->d_name) + strlen(dname) + 2;
		filename = (char *)malloc(len);
		snprintf(filename, len, "%s/%s", dname, dirent->d_name);
		/* recurse if this entry is a directory */
		if (isdir(filename)) {
			if (ccewrapconf_parse_dir(conf, filename) < 0) {
				ret = -1;
			}
		} else if (!strcmp(".xml", filename+strlen(filename)-4)) {
			GList *list;
			int errline = 0;

			/* parse the file */
			list = xml_parse_file(filename, &errline);
			if (errline > 0) {
				CCE_SYSLOG("error in file '%s', line %d\n", filename, errline);
				ret = -1;
			}
			if (!list) {
				continue;
			}
			
			/* add the elements to our config struct */
			if (ccewrapconf_add_elements(conf, list)) {
				CCE_SYSLOG("error in conf file %s\n", filename);
				ret = -1;
			}

			/* free the list */
			g_list_free(list);
		}

		free(filename); /* we malloced this */
	}

	closedir(dir);

	return ret;
}


struct ccewrapconf_t *
ccewrapconf_new (cce_handle_t *cce, char *authuser)
{
	struct ccewrapconf_t *conf;
	conf = malloc(sizeof(struct ccewrapconf_t));
	conf->programs = NULL;
	conf->cce = cce;
	conf->authuser = authuser;
	conf->usercaps = NULL;
	return conf;
}

void
ccewrapconf_free (struct ccewrapconf_t *conf)
{
	g_list_free(conf->programs);
	free(conf);
}

static int
ccewrapconf_add_elements(struct ccewrapconf_t *conf, GList *list)
{
	GList *programs = NULL;
	GList *p;
	
	p = list;
	while (p) {
		struct xml_element *e;

		e = (struct xml_element*)p->data;

		/* We only understand the 'program' element */
		if (!strcmp(e->el_name->str, "program")) {
			struct ccewrapconf_program_t* program;
			program = mk_program(e);
			if (!program) {
				CCE_SYSLOG("Error making program entry\n");
				/* TODO: clean up program structs */
				g_list_free(programs);
				return -1;
			}
			programs = g_list_append(programs, program);
		} else {
			CCE_SYSLOG("unknown top level entry");
			/* TODO: clean up program structs */
			g_list_free(programs);
			return -1;
		}

		p = g_list_next(p);
	} 

	/* we made it here,  we can safely add the elements to
	 * the known collection of program configs */

	p = programs;

	while (p) {
		conf->programs = g_list_append(conf->programs, p->data);
		p = g_list_next(p);
	}
	g_list_free(programs);

	return 0;
}

void
ccewrapconf_addprogram(struct ccewrapconf_t *conf, ccewrapconf_program program) 
{
	conf->programs = g_list_append(conf->programs, program);
}

GList *
ccewrapconf_getprograms(struct ccewrapconf_t *conf)
{
	return conf->programs;
}

int
ccewrapconf_checkprogram(struct ccewrapconf_t *conf, char *progname, GList **validUsers)
{
	int ret = 0;
	GList *p;

	p = ccewrapconf_getprograms(conf);

	while (p) {
		ccewrapconf_program program = (ccewrapconf_program)p->data;
		if (!strcmp(ccewrapconf_program_getname(program),progname)) {
			/* this is the entry */
			ret += ccewrapconf_program_allowed(program, conf, validUsers);
		}

		p = g_list_next(p);
	}
	return ret;
}

cce_handle_t *
ccewrapconf_getcce (struct ccewrapconf_t *conf)
{
	return conf->cce;
}

char *
ccewrapconf_getauthuser (struct ccewrapconf_t *conf)
{
	return conf->authuser;
}

char *
ccewrapconf_getusercapabilities(struct ccewrapconf_t *conf)
{
	char *authuser;
	cce_handle_t *cce;
	cce_props_t *props;
	GSList *oids;
	cscp_oid_t oid;

	if (conf->usercaps != NULL)
		return conf->usercaps;

	authuser = ccewrapconf_getauthuser(conf);
	cce = ccewrapconf_getcce(conf);

	/* find this user and look at their capabilities */
	props = cce_props_new();
	cce_props_set(props, "name", authuser);

	oids = cce_find_sorted_cmnd(cce, "User", props, NULL, 0);
	oid = (cscp_oid_t)oids->data;
	g_slist_free(oids);
	cce_props_destroy(props);

	props = cce_get_cmnd(cce, oid, "");
	conf->usercaps = cce_props_get(props, "capabilities");



	return (conf->usercaps);
}
	
int 
ccewrapconf_issystemadministrator(struct ccewrapconf_t *conf) 
{
	return isSystemAdministrator(
			ccewrapconf_getcce(conf),
			ccewrapconf_getauthuser(conf)
			);
}

int 
isSystemAdministrator(cce_handle_t *cce, char *user){

	int ret = 0;
	cscp_oid_t oid;
	GSList *oids;
	cce_props_t *props = cce_props_new();

	cce_props_set(props, "name", user);
	
	/* check for systemAdmin! */
	oids = cce_find_sorted_cmnd(cce, "User", props, NULL, 0);
	oid = (cscp_oid_t)oids->data;	
	cce_props_destroy(props);

	props = cce_get_cmnd(cce, oid, "");

	/* cce boolean is "" or "0" - very PERLy...*/
	ret = (strcmp(cce_props_get(props, "systemAdministrator"), "")
	 && strcmp(cce_props_get(props, "systemAdministrator"), "0"));

	return ret;
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
