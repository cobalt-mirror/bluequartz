#include <cce_common.h>
#include <cce_conf.h>
#include <conf_internal.h>
#include <glib.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <dirent.h>
#include <string.h>
#include <ctype.h>

#define EXECHANDLERPATH		CCEHANDLERDIR

/* used locally to hold a handler config line */
struct handlerdef {
	char *class;
	char *nspace;
	char *prop;
	char *h_type;
	char *h_data;
	char *stage;
};
#define NEW_HANDLERDEF (struct handlerdef){NULL, NULL, NULL, NULL, NULL, NULL}

static cce_conf *cce_conf_new(void);
static cce_conf_handler *add_unique_handler(cce_conf *conf, char *type, 
	char *data, char *stage);
static gboolean hash_handlers_rm(gpointer *key, gpointer *val, gpointer *u);
static gboolean GHR_remove_event(gpointer *key, gpointer *val, gpointer *u);
static int conf_parse_dir(cce_conf *conf, char *dir_name);
static int conf_parse_file(cce_conf *conf, char *file);
static int isdir(char *path);
static int add_handler(cce_conf *conf, struct handlerdef *h);
static char *build_event_key(char *class, char *namespace, char *property);
static int valid_handler(char *type, char *data);
static char *get_field(char **buf);

/*
 * the real conf struct 
 */
struct cce_conf_struct {
	GHashTable *events;
	GHashTable *handlers;
};

/*
 * explore a directory recursively, and parse *.conf
 */
cce_conf *
cce_conf_get_configuration(char *conf_root)
{
	cce_conf *conf;
	
	conf = cce_conf_new();
	
	/* NULL conf_root means no conf files */
	if (conf_root) {
		if (conf_parse_dir(conf, conf_root) != 0) {
			cce_conf_destroy(conf);
			return NULL;
		}
	}

	return conf;
}

/*
 * get the list of handlers for a give class.namespace.prop key
 */
GSList *
cce_conf_get_handlers(cce_conf *conf, char *class, char *ns, char *prop)
{
	char *keystr;
	GSList *list;

	/* defaults */
	if (!class || !prop)
		return NULL;
	if (!ns)
		ns = "";

	/* concat handlers for this key onto the whole_list */
	keystr = build_event_key(class, ns, prop);
	list = g_hash_table_lookup(conf->events, keystr);

	free(keystr);

	return list;
}

/*
 * free a conf object and all associated data
 */
void
cce_conf_destroy(cce_conf *conf)
{
	g_hash_table_foreach_remove(conf->handlers, 
		(GHRFunc)hash_handlers_rm, NULL);
	g_hash_table_foreach_remove(conf->events,
		(GHRFunc) GHR_remove_event, NULL);

	free(conf);
}


/*
 * private functions
 */

static cce_conf *
cce_conf_new(void)
{
	cce_conf *conf;

	conf = (cce_conf *)malloc(sizeof(cce_conf));

	conf->handlers = g_hash_table_new(g_str_hash, g_str_equal);
	conf->events = g_hash_table_new(g_str_hash, g_str_equal);

	return conf;
}

static gboolean
hash_handlers_rm(gpointer *key, gpointer *val, gpointer *u)
{
	free(key);
	cce_conf_handler_destroy((cce_conf_handler *)val);
	return TRUE;
}

static gboolean
GHR_remove_event(gpointer *key, gpointer *val, gpointer * u)
{
	free(key);
	return TRUE;
}


/*
 * This does no more than add a handler to the list of unique handlers.
 * A handler is unique by its type:data key
 * 
 * Returns a handler * on success
 * Returns NULL if there is a malloc error or we tried to redefine the stage
 */
static cce_conf_handler *
add_unique_handler(cce_conf *conf, char *type, char *data, char *stage)
{
	cce_conf_handler *handler;
	GString *key;
	char *keystr;

	/* build a handler identifier string */
	key = g_string_new(type);
	g_string_append_c(key, ':');
	g_string_append(key, data);
	/* cleanup */
	keystr = strdup(key->str);
	g_string_free(key, TRUE);

	/* Check to see if an equiv. handler already exists */
	handler = g_hash_table_lookup(conf->handlers, keystr);

	if (!valid_handler(type, data)) {
		CCE_SYSLOG("add_unique_handler: %s is invalid", keystr);
#ifdef PEDANTIC_STARTUP
		free(keystr);
		retun NULL;
#endif
	}
	/* If it doesn't, well make it */
	if (!handler) {
		handler = cce_conf_handler_new(type, data, stage);
		if (!handler) {
			free(keystr);
			return NULL;
		}
		g_hash_table_insert(conf->handlers, keystr, handler);
	} else {
		/* It was already in there.. better clean up after ourselves */
		if (handler && strcasecmp(stage, cce_conf_handler_stage(handler))) {
			CCE_SYSLOG("can't redefine stage for %s", keystr);
			handler = NULL;
		}
		free(keystr);
	}

	return handler;
}
static int
conf_parse_dir(cce_conf *conf, char *dir_name)
{
	DIR *dir;
	struct dirent *dirent;
	char *file = NULL;
	int ret = 0;

	DPRINTF(DBG_EXCESSIVE, "conf: scanning directory %s", dir_name);

	if (!isdir(dir_name)) {
		return -1;
	}

	DPRINTF(DBG_CONF, "Scanning directory: %s\n", dir_name);
	dir = opendir(dir_name);
	if (!dir) {
		CCE_SYSLOG("could not open config directory %s", dir_name);
		return -1;
	}
	while ((dirent = readdir(dir))) {
		int len;
		if (dirent->d_name[0] == '.')
			continue;	 /* skip dot files */

		len = strlen(dirent->d_name) + strlen(dir_name) + 2;
		file = (char *)malloc(len);
		snprintf(file, len, "%s/%s", dir_name, dirent->d_name);

		if (isdir(file)) {
			if (conf_parse_dir(conf, file)) {
				ret = -1;
			}
		} else if (!strcmp(".conf", file+strlen(file)-5)) {
			if (conf_parse_file(conf, file)) {
				ret = -1;
			}
		}

		free(file);
	}
	closedir(dir);

	return ret;
}

static int
conf_parse_file(cce_conf *conf, char *file)
{
	FILE *fp;
	char buf[512];
	char *line;
	int lineno = 0;
	char *p;
	char *field0;
	char *field1;
	char *field2;
	struct handlerdef h;
	int ret = 0;

	DPRINTF(DBG_CONF, "\treading %s\n", file);
	
	fp = fopen(file, "r");
	if (!fp) {
		DPERROR(DBG_CONF, "fopen");
		return -1;
	}

	while (!feof(fp)) {
		lineno++;
		/* get a line */
		if (fgets(buf, sizeof(buf)-1, fp) == NULL) {
			continue;
		}

		h = NEW_HANDLERDEF;
		line = buf;

		/* get the first field */
		field0 = get_field(&line);
		if (!field0) {
			/* skip blank lines */
			continue;
		}
		if (*field0 == '#') {
			/* skip comment lines */
			continue;
		}

		/* not a comment - must have two or three fields */
		field1 = get_field(&line);
		if (!field1) {
			CCE_SYSLOG("Error in conf file %s:%d: can't get field 1\n", 
				file, lineno);
			ret = -1;
			continue;
		}
		
		field2 = get_field(&line);

		p = get_field(&line);
		if (p) {
			CCE_SYSLOG("Error in conf file %s:%d: can't get field 3\n", 
				file, lineno);
			ret = -1;
			continue;
		}
		
		/*
		 * process field0 - the event
		 */

		p = field0;

		h.class = strsep(&p, ".");	
		h.nspace = strsep(&p, ".");
		if (!h.nspace) {
			CCE_SYSLOG("Error in conf file %s:%d: can't get namespace\n", 
				file, lineno);
			ret = -1;
			continue;
		}
		h.prop = strsep(&p, ".");
		if (!h.prop) {
			h.prop = h.nspace;
			h.nspace = "";
		}

		/* check for errors */
		if (p || (strlen(h.prop) == 0)) {
			CCE_SYSLOG("Error in conf file %s:%d: invalid property\n", 
				file, lineno);
			ret = -1;
			continue;
		}

		/*
		 * process field1 - the handler
		 */

		p = field1;

		h.h_type = strsep(&p, ":");	
		h.h_data = strsep(&p, ":");
		if (!h.h_data) {
			CCE_SYSLOG("Error in conf file %s:%d: invalid handlerdef\n", 
				file, lineno);
			ret = -1;
			continue;
		}

		/* check for errors */
		if (p || (strlen(h.h_data) == 0)) {
			CCE_SYSLOG("Error in conf file %s:%d: invalid handler\n", 
				file, lineno);
			ret = -1;
			continue;
		}

		/*
		 * process field2 - the stage
		 */

		p = field2;

		if (field2) {
		 	h.stage = strsep(&p, ". \t\n\r");
		}
		if (!h.stage || (strlen(h.stage) == 0)) {
			h.stage = "execute";
		}

		if (add_handler(conf, &h)) {
			CCE_SYSLOG("Error in conf file %s:%d: add_handler failed\n", 
				file, lineno);
			ret = -1;
			continue;
		}

		DPRINTF(DBG_EXCESSIVE, "conf: adding %s.%s.%s %s:%s %s", 
			h.class, h.nspace, h.prop, h.h_type, h.h_data, h.stage);
	}

	/* cleanup */
	fclose(fp);

	return ret;
}

/* operates like strsep(), but breaks on (whitespace/newline)+ */
static char *
get_field(char **buf)
{
	char *p; 
	char *newbuf;

	if (!buf || !(*buf)) {
		return NULL;
	}

	p = *buf;

	/* step forwards to a field */
	while (*p && isspace(*p)) {
		p++;
	}

	newbuf = p;

	/* skip this field */
	while (*newbuf && !isspace(*newbuf)) {
		newbuf++;
	}
	if (*newbuf) {
		*newbuf = '\0';
		*buf = newbuf+1;
	} else {
		*buf = NULL;
	}

	return (*p) ? p : NULL;
}

static int
isdir(char *path)
{
	struct stat s;
	int r;

	if (stat(path, &s)) {
		DPRINTF(DBG_CONF, "Error: Could not stat: %s\n", path);
		return 0;
	}

	r = S_ISDIR(s.st_mode);

	return r;
}

static int
add_handler(cce_conf *conf, struct handlerdef *h)
{
	cce_conf_handler *handler;
	char *keystr;
	GSList *hlist = NULL;
	gpointer key;
	gpointer val;
	char *h_data;
	int cleanup = 0;

	if (!h->class) {
		h->class = "";
	}
	if (!h->nspace) {
		h->nspace = "";
	}
	if (!h->prop) {
		h->prop = "";
	}

	h_data = h->h_data;

	/* canonicalize data, based on type */
	if (!strcasecmp(h->h_type, "exec") || !strcasecmp(h->h_type, "perl")) {
		/* do relative path expansion for exec handlers */
		if (h->h_data[0] != '/') {
			int len = strlen(h->h_data) + strlen(EXECHANDLERPATH);
			h_data = malloc(len + 1);
			if (!h_data) {
				DPERROR(DBG_CONF, "add_handler: malloc()");
				return -1;
			}
			sprintf(h_data, "%s%s", EXECHANDLERPATH, h->h_data);
			cleanup = 1;
		}
	}

	/* get handler */
	handler = add_unique_handler(conf, h->h_type, h_data, h->stage);
	if (cleanup) {
		free(h_data);
	}
	if (!handler) {
		return -1;
	}

	/* build a key */
	keystr = build_event_key(h->class, h->nspace, h->prop);

	/* find any existing handlers */
	if (g_hash_table_lookup_extended(conf->events, keystr, &key, &val)) {
		hlist = (GSList *)val;
		free(keystr);
		keystr = (char *)key;
	}
	
	/* append */
	if (!g_slist_find(hlist, handler)) {
		hlist = g_slist_append(hlist, handler);
	}

	/* store */
	g_hash_table_insert(conf->events, keystr, hlist);

	return 0;
}

static char *
build_event_key(char *class, char *namespace, char *property)
{
	GString *key;
	char *data;

	/* assemble key */
	key = g_string_new(class);
	key = g_string_append_c(key, '.');
	key = g_string_append(key, namespace);
	key = g_string_append_c(key, '.');
	key = g_string_append (key, property);

	data = strdup(key->str);
	g_string_free(key, TRUE); 

	return data;
}

static int
valid_handler(char *type, char *data)
{
	if (!strcmp(type, "test")) {
		/* test handlers always are valid */
		return 1;
	}

	if (!strcmp(type, "exec")) {
		struct stat buf;

		/* verify it exists, and is executable */
		if (!stat(data, &buf)) {
			if ((buf.st_mode & S_IXUSR) || (buf.st_mode & S_IXGRP) 
			 || (buf.st_mode & S_IXOTH)) {
		 		return 1;
			} else {
				CCE_SYSLOG("valid_handler:%s is not executable", 
					data);
			}
		} else {
			CCE_SYSLOG("valid_handler:%s does not exist",data);
		}

		return 0;
	}

	if (!strcmp(type, "perl")) {
		struct stat buf;

		/* verify it exists */
		if (!stat(data, &buf)) {
			return 1;
		} else {
			CCE_SYSLOG("valid_handler:%s does not exist",data);
		}

		return 0;
	}
	
	/* oops - not a known type! */
	return 0;
}

#ifdef DEBUG_CONF
static void EventHashPrint(gpointer *class, gpointer *chash, gpointer *user);

void
cce_conf_dump_state (cce_conf * conf)
{
	printf("# Cce configuration\n");
	g_hash_table_foreach(conf->events, (GHFunc)EventHashPrint, NULL);
}

static void
EventHashPrint(gpointer *class, gpointer *classhash, gpointer *user)
{
	GSList *eventlist = (GSList *)classhash;
	cce_conf_handler *handler;
	
	while (eventlist) {
		char *str;
		handler = eventlist->data;
		str = cce_conf_handler_serialize(handler);
		printf("\t%s     %s      %s\n", 
			(char *)class, str, cce_conf_handler_stage(handler));
		free(str);
		eventlist = g_slist_next(eventlist);
	}
}
#endif
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
