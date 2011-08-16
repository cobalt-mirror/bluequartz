/* $Id: conf_handler.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <cce_conf.h>
#include <conf_internal.h>
#include <cce_conf_types.h>
#ifndef _GNU_SOURCE
#define _GNU_SOURCE
#endif
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

static char *h_stage_itoc(int stage);
static int h_stage_ctoi(char *stage);

/* order should be synced with handler_stage_t */
static char *h_stages[] = {
	"none",
	"validate",
	"configure",
	"execute",
	"test",
	"cleanup",
	NULL
};

typedef enum {
	ROLLBACK_UNSAFE = 0,
	ROLLBACK_SAFE,
} can_rollback_t;

/* store a unique handler */
struct cce_conf_handler_struct {
	char *type;
	char *data;
	handler_stage_t stage;
	can_rollback_t rollback;
};

/* create a handler object */
cce_conf_handler *
cce_conf_handler_new(char *type, char *data, char *stage, char *flags)
{
	cce_conf_handler *handler;
	int nstage;
	int failure = 0;

	nstage = h_stage_ctoi(stage);
	if (nstage == H_STAGE_NONE) {
		/* invalid stage */
		CCE_SYSLOG("Invalid stage \"%s\"", stage);
		return NULL;
	}

	handler = (cce_conf_handler *)malloc(sizeof(cce_conf_handler));
	if (!handler) {
		return NULL;
	}
	
	handler->type = strdup(type);
	handler->data = strdup(data);

	/* verify */
	if (strcasecmp(handler->type, "exec") == 0) {
		/* make sure the exec handler exists */
		{
			int fd;
			fd = open(handler->data, O_RDONLY);
			if (!fd) {
				failure++;
				CCE_SYSLOG("handler exec:%s not found", 
					handler->data);
			}
			close(fd);
		}
	} else {
		handler->data = strdup(data);
	}
	handler->stage = nstage;

	handler->rollback = ROLLBACK_UNSAFE;
	if (flags && !strcmp("rollback", flags)) {
		handler->rollback = ROLLBACK_SAFE;
	}

	if (failure) {
		cce_conf_handler_destroy(handler);
		return NULL;
	}

	return handler;
}

/* cleanup a handler object */
void
cce_conf_handler_destroy(cce_conf_handler *h)
{
	if (h) {
		if (h->type) {
			free(h->type);
		}
		if (h->data) {
			free(h->data);
		}
		free(h);
	}
}

/* build a string of the handler data */
char *
cce_conf_handler_serialize(cce_conf_handler *h)
{
	char *rep;
	int len;

	len = strlen(h->type) + strlen(h->data) + 2;

	rep = (char *)malloc(len);
	snprintf(rep, len, "%s:%s", h->type, h->data);

	return rep;
}

/*
 * accessor functions
 */
char *
cce_conf_handler_data(cce_conf_handler *h)
{
	return h ? h->data : NULL;
}

char *
cce_conf_handler_type(cce_conf_handler *h)
{
	return h ? h->type : NULL;
}

char *
cce_conf_handler_stage(cce_conf_handler *h)
{
	return h ? h_stage_itoc(h->stage) : NULL;
}
int
cce_conf_handler_nstage(cce_conf_handler *h)
{
	return h ? h->stage : -1;
}
int
cce_conf_handler_rollback(cce_conf_handler *h)
{
	if (!h)
		return 0;
	return (h->rollback == ROLLBACK_SAFE);
}

/*
 * helpers for storing handler stage
 */
static char *
h_stage_itoc(int stage)
{
	if (stage >= 0 && stage < H_STAGE_MAX) {
		return h_stages[stage];
	}
	
	return "invalid stage";
}

static int
h_stage_ctoi(char *stage)
{
	int i = 1;
	
	if (stage) {
		while (h_stages[i]) {
			if (!strcasecmp(stage, h_stages[i])) {
				return i;
			}
			i++;
		}
	}

	return H_STAGE_NONE;
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
