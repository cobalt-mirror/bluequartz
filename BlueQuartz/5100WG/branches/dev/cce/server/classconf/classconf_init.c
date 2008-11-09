/* $Id: classconf_init.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include "cce_common.h"
#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <dirent.h>
#include <unistd.h>
#include <errno.h>
#include <glib.h>
#include "codb.h"
#include "classconf.h"
#include "xml_parse.h"

typedef struct {
	/* we can track important data here, when it becomes important */
	GList *tdlist;		/* typedefs */
	GList *cllist;		/* classes */
	GList *rulist;		/* security rules */
	GList *mtlist;		/* matchtypes */
} cc_schema;

static int isdir(const char *path);
static int classconf_parse_dir(codb_classconf *cc, const char *dname);
static void free_rulist(GList * tdlist);
static void free_tdlist(GList * tdlist);
static void free_cllist(GList * cllist);
static void free_schlist(GList * schlist);
static void free_schlist_partial(GList * schlist);
static codb_property *mk_property(struct xml_element *el);
static codb_index *mk_index(struct xml_element *el, codb_class *class);
static codb_class *mk_class(struct xml_element *el);
static codb_typedef *mk_typedef(struct xml_element *el);
static codb_rule *mk_rule(struct xml_element *el);
static codb_matchtype *mk_matchtype(struct xml_element *el);
static cc_schema *mk_schema(struct xml_element *el, codb_classconf *cc);
static int add_td_to_schema(struct xml_element *el, cc_schema * sch,

    codb_classconf *cc);
static int add_cl_to_schema(struct xml_element *el, cc_schema * sch,

    codb_classconf *cc);
static int add_mt_to_schema(struct xml_element *el, cc_schema * sch,

    codb_classconf *cc);
static int add_ru_to_schema(struct xml_element *el, cc_schema * sch,

    codb_classconf *cc);
static cc_schema *cc_schema_new(void);
static void cc_schema_destroy_full(cc_schema * s);
static void cc_schema_destroy_partial(cc_schema * s);
static int cc_schema_add_class(cc_schema * s, codb_class *cl);
static int cc_schema_add_typedef(cc_schema * s, codb_typedef * td);
static int cc_schema_add_rule(cc_schema * s, codb_rule * td);
static int cc_schema_add_matchtype(cc_schema * s, codb_matchtype * td);
static int valid_class(struct xml_element *el, struct xml_element *parent);
static int valid_prop(struct xml_element *el, struct xml_element *parent);
static int valid_index(struct xml_element *el, struct xml_element *parent);
static int valid_td(struct xml_element *el, struct xml_element *parent);
static int valid_rule(struct xml_element *el, struct xml_element *parent);
static int valid_matchtype(struct xml_element *el,

    struct xml_element *parent);
static int valid_schema(struct xml_element *el,

    struct xml_element *parent);
static int is_c_token(char *str);

/* valid elements - leave names lowercase, we tolower() their comparators */
static struct xml_elem_def valid_els[] = {
	{"class", valid_class},
	{"property", valid_prop},
	{"index", valid_index},
	{"typedef", valid_td},
	{"schema", valid_schema},
	{"rule", valid_rule},
	{"matchtype", valid_matchtype},
	{NULL, NULL}
};


codb_classconf *
codb_classconf_init(const char *schemadir)
{
	codb_classconf *cc;
	int n = 0;

	cc = codb_classconf_new();
	if (!cc) {
		DPRINTF(DBG_CODB,
		    "classconf_init: codb_classconf_new() failed\n");
		return NULL;
	}

	if (classconf_parse_dir(cc, schemadir) < 0) {
		CCE_SYSLOG("classconf: some errors found");
#ifdef PEDANTIC_STARTUP
		codb_classconf_destroy(cc);
		return NULL;
#endif
	}

	n = codb_classconf_bindtypes(cc);
	if (n) {
		CCE_SYSLOG("classconf: %d errors binding types", n);
#ifdef PEDANTIC_STARTUP
		codb_classconf_destroy(cc);
		return NULL;
#endif
	}

	return cc;
}

/* handle a top-level list of xml elements to add */
static int
classconf_add_elements(codb_classconf *cc, GList * els)
{
	cc_schema *defsch;
	GList *schlist = NULL;
	GList *p;

	/* get the "default" schema */
	defsch = cc_schema_new();
	if (!defsch) {
		return -1;
	}
	schlist = g_list_append(schlist, defsch);

	/* first validate/build a list of schemas */
	p = els;
	while (p) {
		struct xml_element *e;

		e = (struct xml_element *)p->data;

		/* handle top-level schemas */
		if (!strcmp(e->el_name->str, "schema")) {
			cc_schema *sch = NULL;

			/* build a schema */
			sch = mk_schema(e, cc);
			if (!sch) {
				/* ABORT */
				CCE_SYSLOG
				    ("classconf: failed to make schema %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				free_schlist(schlist);
				return -1;
			}

			/* enlist it */
			schlist = g_list_append(schlist, sch);

			/* FIXME: see if the schema conflicts */
		} else if (!strcmp(e->el_name->str, "typedef")) {
			if (add_td_to_schema(e, defsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add typedef %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				free_schlist(schlist);
				return -1;
			}
		} else if (!strcmp(e->el_name->str, "rule")) {
			if (add_ru_to_schema(e, defsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add rule %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				free_schlist(schlist);
				return -1;
			}
		} else if (!strcmp(e->el_name->str, "matchtype")) {
			if (add_mt_to_schema(e, defsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add matchtype %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				free_schlist(schlist);
				return -1;
			}
		} else if (!strcmp(e->el_name->str, "class")) {
			if (add_cl_to_schema(e, defsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add class %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				free_schlist(schlist);
				return -1;
			}
		} else {
			/* ABORT */
			CCE_SYSLOG("classconf: unknown element type %s",
			    e->el_name->str);
			free_schlist(schlist);
			return -1;
		}

		p = g_list_next(p);
	}

	/* if we get here - we can add all the schemas */
	p = schlist;
	while (p) {
		cc_schema *s;
		GList *p2;

		s = (cc_schema *) p->data;

		/* add all typedefs in this schema */
		p2 = s->tdlist;
		while (p2) {
			codb_typedef *td;

			td = (codb_typedef *) p2->data;
			/* FIXME: check for errors */
			codb_classconf_settype(cc, td);
			p2 = g_list_next(p2);
		}

		/* add all classes in this schema */
		p2 = s->cllist;
		while (p2) {
			codb_class *cl;

			cl = (codb_class *)p2->data;
			/* FIXME: check for errors */
			codb_classconf_setclass(cc, cl);
			p2 = g_list_next(p2);
		}

		/* add all rules in this schema */
		p2 = s->rulist;
		while (p2) {
			codb_rule *ru;

			ru = (codb_rule *) p2->data;
			/* FIXME: check for errors */
			codb_classconf_setrule(cc, ru);
			p2 = g_list_next(p2);
		}

		/* add all matchtypes in this schema */
		p2 = s->mtlist;
		while (p2) {
			codb_matchtype *mt;

			mt = (codb_matchtype *) p2->data;
			/* FIXME: check for errors */
			codb_classconf_setmatchtype(cc, mt);
			p2 = g_list_next(p2);
		}

		p = g_list_next(p);
	}

	/* don't free the data - it is owned by classconf now */
	free_schlist_partial(schlist);

	return 0;
}

/* take a raw xml element, validate it as a matchtype, add it to a schema */
static int
add_mt_to_schema(struct xml_element *el, cc_schema * sch,
    codb_classconf *cc)
{
	codb_matchtype *mt = NULL;

	/* build a matchtype */
	mt = mk_matchtype(el);
	if (!mt) {
		return -1;
	}

	/* enlist it */
	cc_schema_add_matchtype(sch, mt);

	return 0;
}

/* take a raw xml element, validate it as a rule, add it to a schema */
static int
add_ru_to_schema(struct xml_element *el, cc_schema * sch,
    codb_classconf *cc)
{
	codb_rule *ru = NULL;

	/* build a typedef */
	ru = mk_rule(el);
	if (!ru) {
		return -1;
	}

	/* enlist it */
	cc_schema_add_rule(sch, ru);

	return 0;
}

/* take a raw xml element, validate it as a typedef, add it to a schema */
static int
add_td_to_schema(struct xml_element *el, cc_schema * sch,
    codb_classconf *cc)
{
	codb_typedef *td = NULL;

	/* build a typedef */
	td = mk_typedef(el);
	if (!td) {
		return -1;
	}

	/* see if the type exists */
	if (codb_classconf_gettype(cc, codb_typedef_get_name(td))) {
		return -1;
	}

	/* enlist it */
	cc_schema_add_typedef(sch, td);

	return 0;
}

/* take a raw xml element, validate it as a class, add it to a schema */
static int
add_cl_to_schema(struct xml_element *el, cc_schema * sch,
    codb_classconf *cc)
{
	codb_class *cl = NULL;

	/* build up a class */
	cl = mk_class(el);
	if (!cl) {
		/* ABORT */
		return -1;
	}

	/* see if the class exists */
	if (codb_classconf_getclass(cc, codb_class_get_name(cl),
		codb_class_get_namespace(cl))) {
		return -1;
	}

	/* enlist it */
	cc_schema_add_class(sch, cl);

	return 0;
}

/* take an xml element and build a codb_matchtype */
static codb_matchtype *
mk_matchtype(struct xml_element *el)
{
	char *name;
	char *type;
	char *data;

	type = (char *)g_hashwrap_lookup(el->el_props, "type");
	name = (char *)g_hashwrap_lookup(el->el_props, "name");
	data = (char *)g_hashwrap_lookup(el->el_props, "data");

	if (!type || !name || !data) {
		return NULL;
	}

	return codb_matchtype_new(name, type, data);
}

/* take an xml element and build a codb_rule */
static codb_rule *
mk_rule(struct xml_element *el)
{
	char *name;
	char *type;
	char *data;

	type = (char *)g_hashwrap_lookup(el->el_props, "type");
	name = (char *)g_hashwrap_lookup(el->el_props, "name");
	data = (char *)g_hashwrap_lookup(el->el_props, "data");

	if (!type || !name || !data) {
		return NULL;
	}

	return codb_rule_new(name, type, data);
}

/* take an xml element (a typedef) and build a codb_typedef */
static codb_typedef *
mk_typedef(struct xml_element *el)
{
	char *type;
	char *name;
	char *data;
	char *errmsg;

	type = (char *)g_hashwrap_lookup(el->el_props, "type");
	name = (char *)g_hashwrap_lookup(el->el_props, "name");
	data = (char *)g_hashwrap_lookup(el->el_props, "data");
	errmsg = (char *)g_hashwrap_lookup(el->el_props, "errmsg");

	if (!type || !name || !data) {
		return NULL;
	}

	if (!errmsg) {
		errmsg = "[[base-cce.invalidData]]";
	}

	return codb_typedef_new(type, name, data, errmsg);
}

/* take an xml element (a class) and build a codb_class */
static codb_class *
mk_class(struct xml_element *el)
{
	char *name;
	char *ver;
	char *nspace;
	char *createacl;
	char *destroyacl;
	codb_class *cl;
	GList *p;

	name = (char *)g_hashwrap_lookup(el->el_props, "name");
	ver = (char *)g_hashwrap_lookup(el->el_props, "version");
	nspace = (char *)g_hashwrap_lookup(el->el_props, "namespace");
	createacl = (char *)g_hashwrap_lookup(el->el_props, "createacl");
	destroyacl = (char *)g_hashwrap_lookup(el->el_props, "destroyacl");

	if (!name) {
		return NULL;
	}
	if (!ver) {
		ver = "";
	}
	if (!nspace) {
		nspace = "";
	}

	cl = codb_class_new(name, nspace, ver, createacl, destroyacl);
	if (!cl) {
		return NULL;
	}

	p = el->el_children;
	/* now get all the properties */
	while (p) {
		struct xml_element *e;

		e = (struct xml_element *)p->data;

		if (!strcmp(e->el_name->str, "property")) {
			codb_property *pr;

			/* build up a property */
			pr = mk_property(e);
			if (!pr) {
				/* damn! */
				codb_class_destroy(cl);
				return NULL;
			}
			if (codb_class_addproperty(cl, pr)) {
				/* damn */
				codb_property_destroy(pr);
				codb_class_destroy(cl);
				return NULL;
			}
		}
		p = g_list_next(p);
	}

	/* two pass compilation! * now go back and get all the indexes * * 
	 * attached to the right props. */
	p = el->el_children;
	while (p) {
		struct xml_element *e;

		e = (struct xml_element *)p->data;

		if (!strcmp(e->el_name->str, "index")) {
			codb_index *ind;

			ind = mk_index(e, cl);
			codb_class_addindex(cl, ind);
		}
		p = g_list_next(p);
	}


	return cl;
}

/* take an xml element (a property) and build a codb_property */
static codb_property *
mk_property(struct xml_element *el)
{
	char *name;
	char *type;
	char *def;
	char *readacl;
	char *writeacl;
	char *optional;
	char *array;

	name = (char *)g_hashwrap_lookup(el->el_props, "name");
	type = (char *)g_hashwrap_lookup(el->el_props, "type");
	def = (char *)g_hashwrap_lookup(el->el_props, "default");
	readacl = (char *)g_hashwrap_lookup(el->el_props, "readacl");
	writeacl = (char *)g_hashwrap_lookup(el->el_props, "writeacl");
	optional = (char *)g_hashwrap_lookup(el->el_props, "optional");
	array = (char *)g_hashwrap_lookup(el->el_props, "array");

	if (!type || !name) {
		return NULL;
	}
	if (!def) {
		def = "";
	}
	if (!readacl) {
		readacl = "";
	}
	if (!writeacl) {
		writeacl = "";
	}
	if (!optional) {
		optional = "";
	}
	if (!optional) {
		array = "";
	}

	return codb_property_new(name, type, readacl, writeacl, def,
	    optional, array);
}

static codb_index *
mk_index(struct xml_element *el, codb_class *class)
{
	char *name;
	char *property;
	char *sortname;

	name = (char *)g_hashwrap_lookup(el->el_props, "name");
	property = (char *)g_hashwrap_lookup(el->el_props, "property");
	sortname = (char *)g_hashwrap_lookup(el->el_props, "sortname");
	if (!name || !property) {
		return NULL;
	}
	return codb_index_new(name, codb_class_get_name(class),
	    property, sortname);
}

/* take an xml element (a schema) and build a cc_schema */
static cc_schema *
mk_schema(struct xml_element *el, codb_classconf *cc)
{
	cc_schema *newsch;
	GList *p;

	newsch = cc_schema_new();
	if (!newsch) {
		return NULL;
	}

	/* build lists */
	p = el->el_children;
	while (p) {
		struct xml_element *e;

		e = (struct xml_element *)p->data;

		/* handle schema level elements */
		if (!strcmp(e->el_name->str, "typedef")) {
			if (add_td_to_schema(e, newsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add typedef %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				cc_schema_destroy_full(newsch);
				return NULL;
			}
		} else if (!strcmp(e->el_name->str, "rule")) {
			if (add_ru_to_schema(e, newsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add rule %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				cc_schema_destroy_full(newsch);
				return NULL;
			}
		} else if (!strcmp(e->el_name->str, "matchtype")) {
			if (add_mt_to_schema(e, newsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add matchtype %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				cc_schema_destroy_full(newsch);
				return NULL;
			}
		} else if (!strcmp(e->el_name->str, "class")) {
			if (add_cl_to_schema(e, newsch, cc) < 0) {
				CCE_SYSLOG
				    ("classconf: failed to add class %s",
				    (char *)g_hashwrap_lookup(e->el_props,
					"name"));
				cc_schema_destroy_full(newsch);
				return NULL;
			}
		} else {
			CCE_SYSLOG("classconf: unknown element type %s",
			    e->el_name->str);
		}

		p = g_list_next(p);
	}

	return newsch;
}

static cc_schema *
cc_schema_new(void)
{
	cc_schema *n;

	n = malloc(sizeof(cc_schema));
	if (!n) {
		return NULL;
	}
	n->tdlist = n->cllist = n->rulist = n->mtlist = NULL;

	return n;
}

static void
cc_schema_destroy_full(cc_schema * s)
{
	if (s) {
		free_tdlist(s->tdlist);
		s->tdlist = NULL;

		free_cllist(s->cllist);
		s->cllist = NULL;

		free_rulist(s->rulist);
		s->rulist = NULL;

		cc_schema_destroy_partial(s);
	}
}

static void
cc_schema_destroy_partial(cc_schema * s)
{
	if (s) {
		if (s->tdlist) {
			g_list_free(s->tdlist);
		}
		if (s->cllist) {
			g_list_free(s->cllist);
		}
		free(s);
	}
}

static int
cc_schema_add_class(cc_schema * s, codb_class *cl)
{
	s->cllist = g_list_append(s->cllist, cl);
	return 0;
}

static int
cc_schema_add_matchtype(cc_schema * s, codb_matchtype * st)
{
	s->mtlist = g_list_append(s->mtlist, st);
	return 0;
}

static int
cc_schema_add_rule(cc_schema * s, codb_rule * ru)
{
	s->rulist = g_list_append(s->rulist, ru);
	return 0;
}

static int
cc_schema_add_typedef(cc_schema * s, codb_typedef * td)
{
	s->tdlist = g_list_append(s->tdlist, td);
	return 0;
}

static int
isdir(const char *path)
{
	struct stat s;
	int r;

	if (stat(path, &s)) {
		DPRINTF(DBG_CODB, "Error: Could not stat: %s\n", path);
		return 0;
	}

	r = S_ISDIR(s.st_mode);

	return r;
}

static int
classconf_parse_dir(codb_classconf *cc, const char *dname)
{
	DIR *dir;
	struct dirent *dirent;
	char *file = NULL;
	int ret = 0;

	if (!isdir(dname)) {
		return -1;
	}

	DPRINTF(DBG_CODB, "Scanning directory: %s\n", dname);

	dir = opendir(dname);
	if (!dir) {
		CCE_SYSLOG("classconf: could not open directory %s",
		    dname);
		return -1;
	}
	while ((dirent = readdir(dir))) {
		int len;

		if (dirent->d_name[0] == '.')
			continue;	/* skip dot files */

		len = strlen(dirent->d_name) + strlen(dname) + 2;
		file = (char *)malloc(len);
		snprintf(file, len, "%s/%s", dname, dirent->d_name);

		if (isdir(file)) {
			if (classconf_parse_dir(cc, file) < 0) {
				ret = -1;
			}
		} else if (!strcmp(".schema", file + strlen(file) - 7)) {
			GList *el_list;
			int errline = 0;

			/* parse it into elements */
			DPRINTF(DBG_CODB,
			    "classconf_parse_dir: reading file %s\n",
			    file);
			el_list = xml_parse_file(file, &errline);
			if (errline > 0) {
				CCE_SYSLOG
				    ("error in schema file %s, line %d",
				    file, errline);
#ifdef PEDANTIC_STARTUP
				return = -1;
#endif
			}
			if (!el_list) {
				continue;
			}

			/* make sure it is legal structure */
			if (xml_validate_elements(el_list, NULL,
				valid_els) < 0) {
				DPRINTF(DBG_CODB,
				    "error validating schema file %s\n",
				    file);
				xml_destroy_list(el_list);
				ret = -1;
				continue;
			}

			/* add all the elements to the classconf */
			if (classconf_add_elements(cc, el_list)) {
				CCE_SYSLOG("error in schema file %s",
				    file);
				ret = -1;
			}

			/* free the list, not the elements */
			xml_destroy_list(el_list);
		}

		free(file);
	}
	closedir(dir);

	return ret;
}

static void
free_rulist(GList * rulist)
{
	GList *p;

	p = rulist;
	while (p) {
		codb_rule_destroy((codb_rule *) p->data);
		p = g_list_next(p);
	}
	g_list_free(rulist);
}

static void
free_tdlist(GList * tdlist)
{
	GList *p;

	p = tdlist;
	while (p) {
		codb_typedef_destroy((codb_typedef *) p->data);
		p = g_list_next(p);
	}
	g_list_free(tdlist);
}

static void
free_cllist(GList * cllist)
{
	GList *p;

	p = cllist;
	while (p) {
		codb_class_destroy((codb_class *)p->data);
		p = g_list_next(p);
	}
	g_list_free(cllist);
}

static void
free_schlist(GList * schlist)
{
	GList *p;

	p = schlist;
	while (p) {
		cc_schema_destroy_full((cc_schema *) p->data);
		p = g_list_next(p);
	}
	g_list_free(schlist);
}

static void
free_schlist_partial(GList * schlist)
{
	GList *p;

	p = schlist;
	while (p) {
		cc_schema_destroy_partial((cc_schema *) p->data);
		p = g_list_next(p);
	}
	g_list_free(schlist);
}

static int
valid_class(struct xml_element *el, struct xml_element *parent)
{
	char *str;
	int hashi, hashn;

	if (!el) {
		return 0;
	}

	/* parent may be a "schema" */
	if (parent) {
		if (strcasecmp(parent->el_name->str, "schema")) {
			DPRINTF(DBG_CODB,
			    "valid_class: parent is not \"schema\"\n");
			return 0;
		}
	}

	/* class must have name property */
	str = (char *)g_hashwrap_lookup(el->el_props, "name");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_class: has no name\n");
		return 0;
	}
	/* the name is a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_class: name is invalid\n");
		return 0;
	}

	/* if there is a namespace, it must be a C token */
	str = (char *)g_hashwrap_lookup(el->el_props, "namespace");
	if (str && strlen(str) && !is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_class: namespace is invalid\n");
		return 0;
	}

	/* class must have version property */
	str = (char *)g_hashwrap_lookup(el->el_props, "version");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_class: has no version\n");
		return 0;
	}

	/* class might have other properties */
	hashn = g_hashwrap_size(el->el_props);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(el->el_props, hashi, &key, &val);

		if (strcmp((char *)key, "name")
		    && strcmp((char *)key, "version")
		    && strcmp((char *)key, "namespace")
		    && strcmp((char *)key, "createacl")
		    && strcmp((char *)key, "destroyacl")) {
			DPRINTF(DBG_CODB,
			    "valid_class: unknown property \"%s\"\n",
			    (char *)key);
		}
	}

	/* class may have children */
	if (el->el_children
	    && xml_validate_elements(el->el_children, el, valid_els) < 0) {
		DPRINTF(DBG_CODB, "valid_class: error with children\n");
		return 0;
	}

	return 1;
}

static int
valid_index(struct xml_element *el, struct xml_element *parent)
{
	char *str;
	int hashi, hashn;

	if (!el) {
		return 0;
	}

	/* index can only be under a "class" */
	if (!parent) {
		DPRINTF(DBG_CODB, "valid_index: has no parent\n");
		return 0;
	}

	/* parent must be a "class" */
	if (strcasecmp(parent->el_name->str, "class")) {
		DPRINTF(DBG_CODB,
		    "valid_index: parent is not \"class\"\n");
		return 0;
	}

	/* index must have a property */
	str = (char *)g_hashwrap_lookup(el->el_props, "property");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_index: has no property\n");
		return 0;
	}
	/* index must have a name */
	str = (char *)g_hashwrap_lookup(el->el_props, "name");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_index: has no name\n");
		return 0;
	}
	/* name must be a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_index: name is invalid\n");
		return 0;
	}

	/* prop might have other properties */
	hashn = g_hashwrap_size(el->el_props);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(el->el_props, hashi, &key, &val);

		if (strcmp((char *)key, "name")
		    && strcmp((char *)key, "property")
		    && strcmp((char *)key, "sort")) {
			DPRINTF(DBG_CODB,
			    "valid_index: unknown property \"%s\"\n",
			    (char *)key);
		}
	}

	/* prop may not have children */
	if (el->el_children) {
		DPRINTF(DBG_CODB, "valid_index: has children\n");
	}

	return 1;
}

static int
valid_prop(struct xml_element *el, struct xml_element *parent)
{
	char *str;
	int hashi, hashn;

	if (!el) {
		return 0;
	}

	/* prop can only be under a "class" */
	if (!parent) {
		DPRINTF(DBG_CODB, "valid_prop: has no parent\n");
		return 0;
	}

	/* parent must be a "class" */
	if (strcasecmp(parent->el_name->str, "class")) {
		DPRINTF(DBG_CODB, "valid_prop: parent is not \"class\"\n");
		return 0;
	}

	/* prop must have a name */
	str = (char *)g_hashwrap_lookup(el->el_props, "name");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_prop: has no name\n");
		return 0;
	}
	/* name must be a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_prop: name is invalid\n");
		return 0;
	}

	/* prop must have a type */
	str = (char *)g_hashwrap_lookup(el->el_props, "type");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_prop: has no type\n");
		return 0;
	}
	/* type must be a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_prop: type is invalid\n");
		return 0;
	}

	/* prop might have other properties */
	hashn = g_hashwrap_size(el->el_props);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(el->el_props, hashi, &key, &val);

		if (strcmp((char *)key, "name")
		    && strcmp((char *)key, "type")
		    && strcmp((char *)key, "default")
		    && strcmp((char *)key, "optional")
		    && strcmp((char *)key, "array")
		    && strcmp((char *)key, "readacl")
		    && strcmp((char *)key, "writeacl")) {
			DPRINTF(DBG_CODB,
			    "valid_prop: unknown property \"%s\"\n",
			    (char *)key);
		}
	}

	/* prop may not have children */
	if (el->el_children) {
		DPRINTF(DBG_CODB, "valid_prop: has children\n");
	}

	return 1;
}

static int
valid_rule(struct xml_element *el, struct xml_element *parent)
{
	char *str;
	int hashi, hashn;

	if (!el) {
		return 0;
	}

	/* parent may be a "schema" */
	if (parent) {
		if (strcasecmp(parent->el_name->str, "schema")) {
			DPRINTF(DBG_CODB,
			    "valid_rule: parent is not \"schema\"\n");
			return 0;
		}
	}

	/* rules must have a name */
	str = (char *)g_hashwrap_lookup(el->el_props, "name");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_rule: has no name\n");
		return 0;
	}
	/* name must be a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_rule: name is invalid\n");
		return 0;
	}

	/* rules must have type,data properties */
	if (!(char *)g_hashwrap_lookup(el->el_props, "type")) {
		DPRINTF(DBG_CODB, "valid_rule: has no type\n");
		return 0;
	}
	if (!(char *)g_hashwrap_lookup(el->el_props, "data")) {
		DPRINTF(DBG_CODB, "valid_rule: has no data\n");
		return 0;
	}

	/* td can't have other properties */
	hashn = g_hashwrap_size(el->el_props);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(el->el_props, hashi, &key, &val);

		if (strcmp((char *)key, "name")
		    && strcmp((char *)key, "type")
		    && strcmp((char *)key, "data")) {
			DPRINTF(DBG_CODB,
			    "valid_rule: unknown property \"%s\"\n",
			    (char *)key);
		}
	}

	/* rules may not have children */
	if (el->el_children) {
		DPRINTF(DBG_CODB, "rules: has children\n");
	}

	return 1;
}

static int
valid_matchtype(struct xml_element *el, struct xml_element *parent)
{
	char *str;
	int hashi, hashn;

	if (!el) {
		return 0;
	}

	/* parent may be a "schema" */
	if (parent) {
		if (strcasecmp(parent->el_name->str, "schema")) {
			DPRINTF(DBG_CODB,
			    "valid_matchtype: parent is not \"schema\"\n");
			return 0;
		}
	}

	/* matchtype must have a name */
	str = (char *)g_hashwrap_lookup(el->el_props, "name");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_matchtype: has no name\n");
		return 0;
	}
	/* name must be a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_matchtype: name is invalid\n");
		return 0;
	}

	/* matchtype must have type,data properties */
	if (!(char *)g_hashwrap_lookup(el->el_props, "type")) {
		DPRINTF(DBG_CODB, "valid_matchtype: has no type\n");
		return 0;
	}
	if (!(char *)g_hashwrap_lookup(el->el_props, "data")) {
		DPRINTF(DBG_CODB, "valid_matchtype: has no data\n");
		return 0;
	}

	/* td can't have other properties */
	hashn = g_hashwrap_size(el->el_props);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(el->el_props, hashi, &key, &val);

		if (strcmp((char *)key, "name")
		    && strcmp((char *)key, "type")
		    && strcmp((char *)key, "data")) {
			DPRINTF(DBG_CODB,
			    "valid_matchtype: unknown property \"%s\"\n",
			    (char *)key);
		}
	}

	/* matchtype may not have children */
	if (el->el_children) {
		DPRINTF(DBG_CODB, "matchtype: has children\n");
	}

	return 1;
}

static int
valid_td(struct xml_element *el, struct xml_element *parent)
{
	char *str;
	int hashi, hashn;

	if (!el) {
		return 0;
	}

	/* parent may be a "schema" */
	if (parent) {
		if (strcasecmp(parent->el_name->str, "schema")) {
			DPRINTF(DBG_CODB,
			    "valid_td: parent is not \"schema\"\n");
			return 0;
		}
	}

	/* td must have a name */
	str = (char *)g_hashwrap_lookup(el->el_props, "name");
	if (!str) {
		DPRINTF(DBG_CODB, "valid_td: has no name\n");
		return 0;
	}
	/* name must be a C token */
	if (!is_c_token(str)) {
		DPRINTF(DBG_CODB, "valid_td: name is invalid\n");
		return 0;
	}

	/* td must have type,data properties */
	if (!(char *)g_hashwrap_lookup(el->el_props, "type")) {
		DPRINTF(DBG_CODB, "valid_td: has no type\n");
		return 0;
	}
	if (!(char *)g_hashwrap_lookup(el->el_props, "data")) {
		DPRINTF(DBG_CODB, "valid_td: has no data\n");
		return 0;
	}

	/* td can't have other properties */
	hashn = g_hashwrap_size(el->el_props);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(el->el_props, hashi, &key, &val);

		if (strcmp((char *)key, "name")
		    && strcmp((char *)key, "type")
		    && strcmp((char *)key, "data")
		    && strcmp((char *)key, "errmsg")) {
			DPRINTF(DBG_CODB,
			    "valid_td: unknown property \"%s\"\n",
			    (char *)key);
		}
	}

	/* td may not have children */
	if (el->el_children) {
		DPRINTF(DBG_CODB, "valid_td: has children\n");
	}

	return 1;
}

static int
valid_schema(struct xml_element *el, struct xml_element *parent)
{
	/* schema can only be toplevel */
	if (parent) {
		DPRINTF(DBG_CODB, "valid_schema: has a parent\n");
		return 0;
	}

	/* schema must have a name */
	if (!(char *)g_hashwrap_lookup(el->el_props, "name")) {
		DPRINTF(DBG_CODB, "valid_schema: has no name\n");
		return 0;
	}

	/* schema must have a vendor */
	if (!(char *)g_hashwrap_lookup(el->el_props, "vendor")) {
		DPRINTF(DBG_CODB, "valid_schema: has no vendor\n");
		return 0;
	}

	/* schema must have a version */
	if (!(char *)g_hashwrap_lookup(el->el_props, "version")) {
		DPRINTF(DBG_CODB, "valid_schema: has no version\n");
		return 0;
	}

	/* schema may have children */
	if (el->el_children
	    && xml_validate_elements(el->el_children, el, valid_els) < 0) {
		DPRINTF(DBG_CODB, "valid_schema: error with children\n");
		return 0;
	}

	return 1;
}

static int
is_c_token(char *str)
{
	char *p = str;

	if (!p || !(*p)) {
		return 0;
	}

	/* validate first char */
	if (!isalpha(*p) && (*p != '_')) {
		return 0;
	}
	p++;

	/* rest of str */
	while (*p) {
		if (!isalnum(*p) && (*p != '_')) {
			return 0;
		}
		p++;
	}

	return 1;
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
