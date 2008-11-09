#include <cce_common.h>
#include <cce_debug.h>
#include <xml_parse.h>

#include <stdio.h>
#include <ctype.h>
#include <unistd.h>
#include <errno.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <glib.h>

static struct xml_element *parse_element(int fd, char first, int *lineno); 
static int unescape(int fd, int *lineno);
static void prolog(int fd, int *lineno);
static void comment(int fd, int *lineno);
static void dump_elements(GList *list);

#define ERROR(s, c, l)	DPRINTF(DBG_COMMON, \
	"invalid char '%c' in state %d (line %d)\n", \
					(c), (s), (l));
#define set_state(new_state)	do { state = new_state; } while(0);
#define is_name_start(c)	(isalpha(c) || (c) == '_' || (c) == ':')
#define is_name(c)		(is_name_start(c) || isdigit(c) || (c) == '.' \
					 || (c) == '-')

GList *
xml_parse_file(char *file, int *errline)
{
	int fd;
	char c;
	GList *el_list = NULL;
	struct xml_element *new_el = NULL;
	int can_prolog = 1;
	int lineno = 1;

	*errline = 0;

	fd = open(file, O_RDONLY);
	if (fd < 0) {
		DPERROR(DBG_COMMON, "open");
		return NULL;
	}

	while (read(fd, &c, 1)) {
		if (c == '\n') {
			lineno++;
		}
		if (isspace(c)) {
			continue;
		} else if (c == '<') {
			if (!read(fd, &c, 1)) {
				DPRINTF(DBG_COMMON, "ERROR: premature EOF (line %d)\n", 
					lineno);
				return el_list;
			}
			/* handle a prolog ("<?") */
			if (c == '?' && can_prolog) {
				prolog(fd, &lineno);
				continue;
			}
			can_prolog = 0;

			/* handle a comment ("<!") */
			if (c == '!') {
				comment(fd, &lineno);
				continue;
			}
			if (c == '\n') {
				lineno++;
			}
			
			new_el = parse_element(fd, c, &lineno);
			if (!new_el) {
				*errline = lineno;
				return el_list;
			}
			el_list = g_list_append(el_list, new_el);
		} else {
			DPRINTF(DBG_COMMON, 
				"invalid char '%c' in state top (line %d)\n", c,
				lineno);
		}
	}
	lineno++;

	/* cleanup */
	close(fd);

	return el_list;
}

void
xml_destroy_list(GList *el_list)
{
	GList *p = el_list;

	if (!el_list) {
		return;
	}

	while (p) {
		xml_element_destroy((struct xml_element *)p->data);
		p = g_list_next(p);
	}
	g_list_free(el_list);
}

/* valid states */
typedef enum {
	ST_TAGSTART = 0,
	ST_START_STAG,
	ST_STAG_NAME_DONE,
	ST_PROPNAME,
	ST_GETEQ,
	ST_GOTEQ,
	ST_PROPVAL,
	ST_SQ_PROPVAL,
	ST_POST_PROPVAL,
	ST_END_SETAG,
	ST_END_STAG,
	ST_CONTENT,
	ST_MAYBE_COMMENT,
	ST_NEWTAG_START,
	ST_START_ETAG,
	ST_ETAG_NAME,
	ST_END_ETAG,
} state_t;
#define ST_CUR		state
			

/* macros used in parse_element() */
#define SAVE_PROP()	\
	do { \
		gpointer oldval; \
		gpointer oldkey; \
		\
		/* see if it exists */ \
		if (g_hash_table_lookup_extended( \
		 cur_el->el_props, propname, &oldkey, &oldval)) { \
		 	free(oldkey); \
			free(oldval); \
		} \
		\
		/* insert it and cleanup */ \
		g_hash_table_insert(cur_el->el_props, \
			strdup(propname->str), strdup(propval->str)); \
		g_string_truncate(propname, 0); \
		g_string_truncate(propval, 0); \
	} while (0)

#define CLEANUP() \
	do { \
		g_string_free(propname, TRUE); \
		g_string_free(propval, TRUE); \
		g_string_free(end_name, TRUE); \
	} while (0)

#define END_ELEMENT() \
	do { \
		if (!strcasecmp(cur_el->el_name->str, end_name->str)) { \
			CLEANUP(); \
			return cur_el; \
		} else { \
			/* not a match! */ \
			DPRINTF(DBG_COMMON, "end_name (%s) != el_name (%s)\n", \
				end_name->str, cur_el->el_name->str); \
			set_state(ST_CONTENT); \
		} \
	} while (0)

#define ST_ERR()	\
	do { \
		ERROR(state, c, *lineno); \
		CLEANUP(); \
		xml_element_destroy(cur_el); \
		return NULL; \
	} while (0)
		

static struct xml_element *
parse_element(int fd, char first, int *lineno) 
{
	char c;
	state_t state = ST_TAGSTART;
	struct xml_element *cur_el = NULL;
	struct xml_element *new_el = NULL;
	GString *propname;
	GString *propval;
	GString *end_name;
	
	cur_el = xml_element_new();
	propname = g_string_new("");
	propval = g_string_new("");
	end_name = g_string_new("");

	/* we got a '<' */
	if (is_name_start(first)) {
		g_string_append_c(cur_el->el_name, tolower(first));
		set_state(ST_START_STAG);
	} else {
		set_state(ST_TAGSTART);
	}

	while (read(fd, &c, 1)) {
		if (c == '\n') {
			(*lineno)++;
		}
		switch (state) {
			/* start */
			case ST_TAGSTART:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (is_name_start(c)) {
					g_string_append_c(cur_el->el_name, 
						tolower(c));
					set_state(ST_START_STAG);
				} else {
					ST_ERR();
				}
				break;
			/* we got the start of a keyword for an stag */
			case ST_START_STAG:
				if (is_name(c)) {
					g_string_append_c(cur_el->el_name, 
						tolower(c));
					set_state(ST_CUR);
				} else if (isspace(c)) {
					set_state(ST_STAG_NAME_DONE);
				} else if (c == '>') {
					set_state(ST_END_STAG);
				} else if (c == '/') {
					set_state(ST_END_SETAG);
				} else {
					ST_ERR();
				}
				break;
			/* keyword done - look for next prop or other */
			case ST_STAG_NAME_DONE:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '>') {
					set_state(ST_END_STAG);
				} else if (c == '/') {
					set_state(ST_END_SETAG);
				} else if (is_name_start(c)) {
					/* must be a propname */
					g_string_append_c(propname, 
						tolower(c));
					set_state(ST_PROPNAME);
				} else {
					ST_ERR();
				}
				break;
			/* found start of a propname */
			case ST_PROPNAME:
				if (is_name(c)) {
					g_string_append_c(propname, 
						tolower(c));
					set_state(ST_CUR);
				} else if (c == '=') {
					set_state(ST_GOTEQ);
				} else if (isspace(c)) {
					set_state(ST_GETEQ);
				} else {
					ST_ERR();
				}
				break;
			/* got propname, get '=' */
			case ST_GETEQ:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '=') {
					set_state(ST_GOTEQ);
				} else {
					ST_ERR();
				}
				break;
			/* got '=' - find propval */
			case ST_GOTEQ:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '"') {
					set_state(ST_PROPVAL);
				} else if (c == '\'') {
					set_state(ST_SQ_PROPVAL);
				} else {
					ST_ERR();
				}
				break;
			/* got a quoted propval */
			case ST_PROPVAL:
				if (c == '&') {
					int lc = unescape(fd, lineno);
					if (lc > 0) {
						g_string_append_c(propval, lc);
					}
				} else if (c == '"') {
					SAVE_PROP();
					set_state(ST_STAG_NAME_DONE);
				} else if (c == '<') {
					ST_ERR();
				} else {
					g_string_append_c(propval, c);
					set_state(ST_CUR);
				}
				break;
			/* got a single-quoted propval */
			case ST_SQ_PROPVAL:
				if (c == '&') {
					int lc = unescape(fd, lineno);
					if (lc > 0) {
						g_string_append_c(propval, lc);
					}
				} else if (c == '\'') {
					SAVE_PROP();
					set_state(ST_STAG_NAME_DONE);
				} else if (c == '<') {
					/* spec says this is an error */
					ST_ERR();
				} else {
					g_string_append_c(propval, c);
					set_state(ST_CUR);
				}
				break;
			/* spec says we must have a space between props */
			case ST_POST_PROPVAL:
				if (isspace(c)) {
					set_state(ST_STAG_NAME_DONE);
				} else if (c == '>') {
					set_state(ST_END_STAG);
				} else if (c == '/') {
					set_state(ST_END_SETAG);
				} else {
					ST_ERR();
				}
				break;
			/* got a '/', suggesting an empty element */
			case ST_END_SETAG:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '>') {
					g_string_free(propname, TRUE);
					g_string_free(propval, TRUE);
					g_string_free(end_name, TRUE);
					return cur_el;
				} else {
					ST_ERR();
				}
				break;
			/* time to look for content */
			case ST_END_STAG:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '&') {
					int lc = unescape(fd, lineno);
					if (lc > 0) {
						g_string_append_c(cur_el->el_content, lc);
					}
					set_state(ST_CONTENT);
				} else if (c == '<') {
					set_state(ST_MAYBE_COMMENT);
				} else {
					g_string_append_c(cur_el->el_content, c);
					set_state(ST_CONTENT);
				}
				break;
			/* processing content */
			case ST_CONTENT:
				if (isspace(c)) {
					g_string_append_c(cur_el->el_content, c);
					set_state(ST_CUR);
				} else if (c == '&') {
					int lc = unescape(fd, lineno);
					if (lc > 0) {
						g_string_append_c(cur_el->el_content, lc);
					}
				} else if (c == '<') {
					set_state(ST_MAYBE_COMMENT);
				} else {
					g_string_append_c(cur_el->el_content, c);
					set_state(ST_CUR);
				}
				break;
			/* got a '<' - check for a comment */
			case ST_MAYBE_COMMENT:
				if (c == '!') {
					comment(fd, lineno);
					set_state(ST_CONTENT);
				} else if (isspace(c)) {
					set_state(ST_NEWTAG_START);
				} else if (c == '/') {
					set_state(ST_START_ETAG);
				} else {
					new_el = parse_element(fd, c, lineno);
					if (!new_el) {
						xml_element_destroy(cur_el);
						return NULL;
					}
					cur_el->el_children = g_list_append(
						cur_el->el_children, new_el);
					set_state(ST_CONTENT);
				}
				break;
			/* got a '<' in content - figure it out */
			case ST_NEWTAG_START:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '/') {
					set_state(ST_START_ETAG);
				} else {
					new_el = parse_element(fd, c, lineno);
					if (!new_el) {
						xml_element_destroy(cur_el);
						return NULL;
					}
					cur_el->el_children = g_list_append(
						cur_el->el_children, new_el);
					set_state(ST_CONTENT);
				}
				break;
			/* got a </ */
			case ST_START_ETAG:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (is_name_start(c)) {
					g_string_append_c(end_name, c);
					set_state(ST_ETAG_NAME);
				} else {
					ST_ERR();
				}
				break;
			/* reading all of </name */
			case ST_ETAG_NAME:
				if (is_name(c)) {
					g_string_append_c(end_name, c);
					set_state(ST_CUR);
				} else if (isspace(c)) {
					set_state(ST_END_ETAG);
				} else if (c == '>') {
					END_ELEMENT();
				} else {
					ST_ERR();
				}
				break;
			/* just get '>' and wrap up */
			case ST_END_ETAG:
				if (isspace(c)) {
					set_state(ST_CUR);
				} else if (c == '>') {
					END_ELEMENT();
				} else {
					ST_ERR();
				}
				break;
		}
	}
	DPRINTF(DBG_COMMON, "ERROR: premature EOF (line %d)\n", *lineno);
	return NULL;
}

struct xml_element *
xml_element_new(void) 
{
	struct xml_element *new_el;
	
	new_el = (struct xml_element *)malloc(sizeof(struct xml_element));
	if (!new_el) {
		DPERROR(DBG_COMMON, "xml_element_new: malloc()");
		exit(errno);
	}

	new_el->el_content = g_string_new("");
	new_el->el_name = g_string_new("");
	new_el->el_props = g_hash_table_new(g_str_hash, g_str_equal);
	new_el->el_children = NULL;

	return new_el;
}

static void hash_rm_el(gpointer k, gpointer v, gpointer crap);

void
xml_element_destroy(struct xml_element *element)
{
	GList *kid = NULL;
	GList *next_kid = NULL;

	if (!element) {
		return;
	}
	
	g_string_free(element->el_content, TRUE);
	
	g_string_free(element->el_name, TRUE);
	
	g_hash_table_foreach(element->el_props, hash_rm_el, NULL);
	g_hash_table_destroy(element->el_props);
	
	kid = element->el_children;
	while (kid) {
		next_kid = g_list_next(kid);
		xml_element_destroy((struct xml_element *)kid->data);
		g_list_free_1(kid);
		kid = next_kid;
	}
}

static void
hash_rm_el(gpointer k, gpointer v, gpointer crap)
{
	free(k);
	free(v);
}

static int
unescape(int fd, int *lineno) 
{
	char buf[16];
	int i = 0;
	char *escapes[] = {
		"lt;",
		"gt;",
		"amp;",
		"apos;",
		"quot;",
		"nbsp;",
		NULL
	};
	char esc_vals[] = {
		'<',
		'>',
		'&',
		'\'',
		'"',
		' ',
		'\0'
	};
	
	/* we assume we got a '&' before we got here */
	if (!read(fd, &(buf[i]), 1)) {
		DPRINTF(DBG_COMMON, "ERROR: premature EOF (line %d)\n", 
			*lineno);
		return -1;
	}
	if (buf[i] == '\n') {
		(*lineno)++;
	}
	while ((buf[i] != ';') && (!isspace(buf[i])) && (i < sizeof(buf)-1)) {
		i++;
		if (!read(fd, &(buf[i]), 1)) {
			DPRINTF(DBG_COMMON, "ERROR: premature EOF (line %d)\n", 
				*lineno);
			return -1;
		}
		if (buf[i] == '\n') {
			(*lineno)++;
		}
	}
	buf[i+1] = '\0';

	/* got an escape */
	if (buf[i] == ';') {
		i = 0;
		/* got a &# style escape */
		if (buf[0] == '#') {
			char charesc = -1;
			int base = 10;
			i++;
			if (tolower(buf[i]) == 'x') {
				base = 16;
				i++;
			}
			while (buf[i] != ';') {
				int decval;

				/* convert buf[i]) */
				if (isdigit(buf[i])) {
					decval = buf[i] - '0';
				} else if (isxdigit(buf[i])) {
					decval = (tolower(buf[i]) - 'a') + 10;
				} else {
					return -1;
				}
				charesc *= base;
				charesc += decval;
			}
			return charesc;
		}
		/* got a 'normal' escape */
		while (escapes[i]) {
			if (!strcasecmp(buf, escapes[i])) {
				return esc_vals[i];
			}
			i++;
		}
	}

	return -1;
}

static void
prolog(int fd, int *lineno)
{
	char cur = ' ';
	char prev = ' ';
	
	while (read(fd, &cur, 1)) {
		if (cur == '\n') {
			(*lineno)++;
		} else if (cur == '>' && prev == '?') {
			return;
		}
		prev = cur;
	}
}

static void
comment(int fd, int *lineno)
{
	char cur = ' ';
	char prev = ' ';
	char prev2 = ' ';
	
	while (read(fd, &cur, 1)) {
		if (cur == '\n') {
			(*lineno)++;
		} else if (cur == '>' && prev == '-' && prev2 == '-') {
			return;
		}
		prev2 = prev;
		prev = cur;
	}
}

static void
prop_print(gpointer k, gpointer v, gpointer crap)
{
	printf("\t  %s = \"%s\"\n", (char *)k, (char *)v);
}
static void
dump_elements(GList *list)
{
	GList *p = list;

	while (p) {
		struct xml_element *e;
		e = (struct xml_element *)p->data;

		printf("el_name = \"%s\"\n", e->el_name->str);
		//printf("\tel_content = \"%s\"\n", e->el_content->str);
		printf("\tel_props:\n");
		g_hash_table_foreach(e->el_props, prop_print, NULL);
		
		if (e->el_children) {
			dump_elements(e->el_children);
		}
		p = g_list_next(p);
	}
}

int 
xml_validate_elements(GList *elements, struct xml_element *parent, 
	struct xml_elem_def valid_els[])
{
	GList *p;
	struct xml_element *e;

	p = elements;
	while (p) {
		int i = 0;
		int did_el = 0;

		e = (struct xml_element *)p->data;

		while (valid_els[i].name) {
			if (!strcasecmp(valid_els[i].name, e->el_name->str)) {
				if (!valid_els[i].isvalid(e, parent)) {
					CCE_SYSLOG("xml: xml_validate failed "
						"for %s name=%s", e->el_name->str,
						(char *)g_hash_table_lookup(e->el_props, 
						"name"));
					return -1;
				}
				did_el = 1;
				break;
			}
			i++;
		}
		if (!did_el) {
			CCE_SYSLOG("xml: xml_validate failed - unknown "
				"element %s", e->el_name->str);
			return -1;
		}
		p = g_list_next(p);
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
