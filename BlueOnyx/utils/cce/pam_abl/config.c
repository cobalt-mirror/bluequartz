/* config.c */
/* Unless otherwise *explicitly* stated the following text
 * describes the licensed conditions under which the
 * contents of this module release may be distributed:
 *
 * --------------------------------------------------------
 * Redistribution and use in source and binary forms of
 * this module, with or without modification, are permitted
 * provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain any
 *    existing copyright notice, and this entire permission
 *    notice in its entirety, including the disclaimer of
 *    warranties.
 *
 * 2. Redistributions in binary form must reproduce all
 *    prior and current copyright notices, this list of
 *    conditions, and the following disclaimer in the
 *    documentation and/or other materials provided with
 *    the distribution.
 *
 * 3. The name of any author may not be used to endorse or
 *    promote products derived from this software without
 *    their specific prior written permission.
 *
 * ALTERNATIVELY, this product may be distributed under the
 * terms of the GNU General Public License, in which case
 * the provisions of the GNU GPL are required INSTEAD OF
 * the above restrictions.  (This clause is necessary due
 * to a potential conflict between the GNU GPL and the
 * restrictions contained in a BSD-style copyright.)
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR(S) BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * --------------------------------------------------------
 *
 * Copyright Andy Armstrong, andy@hexten.net, 2005
 */
#include "pam_abl.h"

static int config_match(const char *pattern, const char *target, int len) {
    return len == strlen(pattern) && memcmp(pattern, target, len) == 0;
}

/* Check an arg string of the form arg=something and return either
 * NULL if the arg doesn't match the supplied name or
 * a pointer to 'something' if the arg matches
 */
static const char *is_arg(const char *name, const char *arg) {
    char *eq;

    if (eq = strchr(arg, '='), NULL == eq) {
        return NULL;
    }

    if (!config_match(name, arg, eq - arg)) {
        return NULL;
    }

    eq++;                                   /* skip '=' */
    while (*eq != '\0' && isspace(*eq)) {   /* skip spaces */
        eq++;
    }

    return eq;
}

void config_clear(pam_handle_t *pamh, abl_args *args) {
    /* Init the args structure
     */
    args->pamh            = pamh;
    args->debug           = 0;
    args->no_warn         = 0;
    args->use_first_pass  = 0;
    args->try_first_pass  = 0;
    args->use_mapped_pass = 0;
    args->expose_account  = 0;

    args->host_db         = NULL;
    args->host_rule       = NULL;
    args->host_purge      = HOST_PURGE;
    args->user_db         = NULL;
    args->user_rule       = NULL;
    args->user_purge      = USER_PURGE;

    args->strs            = NULL;

    args->record_attempt  = 0;
    args->check_only 	  = 0;
}

static int parse_arg(const char *arg, abl_args *args) {
    const char *v;
    int err;

    if (0 == strcmp(arg, "debug")) {
        args->debug = 1;
    } else if (0 == strcmp(arg, "no_warn")) {
        args->no_warn = 1;
    } else if (0 == strcmp(arg, "use_first_pass")) {
        args->use_first_pass = 1;
    } else if (0 == strcmp(arg, "try_first_pass")) {
        args->try_first_pass = 1;
    } else if (0 == strcmp(arg, "use_mapped_pass")) {
        args->use_mapped_pass = 1;
    } else if (0 == strcmp(arg, "expose_account")) {
        args->expose_account = 1;
    /* Our args */
    } else if (v = is_arg("host_db", arg), NULL != v) {
        args->host_db = v;
    } else if (v = is_arg("host_rule", arg), NULL != v) {
        args->host_rule = v;
    } else if (v = is_arg("host_purge", arg), NULL != v) {
        if (err = rule_parse_time(v, &args->host_purge, HOURSECS), 0 != err) {
            log_out(args, LOG_ERR, "Illegal host_purge value: %s", v);
        }
    } else if (v = is_arg("user_db", arg), NULL != v) {
        args->user_db = v;
    } else if (v = is_arg("user_rule", arg), NULL != v) {
        args->user_rule = v;
    } else if (v = is_arg("user_purge", arg), NULL != v) {
        if (err = rule_parse_time(v, &args->user_purge, HOURSECS), 0 != err) {
            log_out(args, LOG_ERR, "Illegal user_purge value: %s", v);
        }
    } else if (v = is_arg("config", arg), NULL != v) {
        config_parse_file(v, args);
    } else if (!strcmp(arg, "record_attempt")) {
    		args->record_attempt = 1;
    } else if (!strcmp(arg, "check_only")) {
    		args->check_only = 1;
    } else {
        log_out(args, LOG_ERR, "Illegal option: %s", arg);
        return EINVAL;
    }

    return 0;
}

struct linebuf {
    char    *buf;
    int     len;
    int     size;
};

struct reader {
    FILE    *f;
    int     lc;
};

static int ensure(const abl_args *args, struct linebuf *b, int minfree) {
    if (b->size - b->len < minfree) {
        char *nb;
        int ns;
        if (minfree < 128) {
            minfree = 128;
        }
        ns = b->len + minfree;
        nb = realloc(b->buf, ns);
        if (NULL == nb) {
            log_sys_error(args, ENOMEM, "parsing config file");
            return ENOMEM;
        }
        b->size = ns;
        b->buf  = nb;
        /*log_debug(args, "Line buffer grown to %d", b->size);*/
    }

    return 0;
}

static int readc(struct reader *r) {
    int nc;

    for (;;) {
        nc    = r->lc;
        r->lc = (nc == EOF) ? EOF : getc(r->f);
        /* Handle line continuation */
        if (nc != '\\' || r->lc != '\n') {
            return nc;
        }
        /* No need for EOF check here */
        r->lc = getc(r->f);
    }
}

static int read_line(const abl_args *args, struct linebuf *b, struct reader *r) {
    int c, err;

    c = readc(r);
    b->len = 0;
    while (c != '\n' && c != EOF && c != '#') {
        while (c != '\n' && c != EOF && isspace(c)) {
            c = readc(r);
        }
        while (c != '\n' && c != EOF && c != '#') {
            if (err = ensure(args, b, 1), 0 != err) {
                return err;
            }
            b->buf[b->len++] = c;
            c = readc(r);
        }
    }
    while (c != '\n' && c != EOF) {
        c = readc(r);
    }

    /* Trim trailing spaces from line */
    while (b->len > 0 && isspace(b->buf[b->len-1])) {
        b->len--;
    }

    ensure(args, b, 1);
    b->buf[b->len++] = '\0';

    return 0;
}

static const char *dups(abl_args *args, const char *s) {
    int l = strlen(s);
    abl_string *str = malloc(sizeof(abl_string) + l + 1);
    memcpy(str + 1, s, l + 1);
    str->link = args->strs;
    args->strs = str;
    return (const char *) (str + 1);
}

/* Parse the contents of a config file */
int config_parse_file(const char *name, abl_args *args) {
    struct linebuf b;
    struct reader  r;
    int err = 0;
    const char *l;

    b.buf  = NULL;
    b.len  = 0;
    b.size = 0;

    if (r.f = fopen(name, "r"), NULL == r.f) {
        err = errno;
        goto done;
    }

    r.lc = getc(r.f);
    while (r.lc != EOF) {
        if (err = read_line(args, &b, &r), 0 != err) {
            goto done;
        }
        if (b.len > 1) {
            if (l = dups(args, b.buf), NULL == l) {
                err = ENOMEM;
                goto done;
            }
            log_debug(args, "%s: %s", name, l);
            if (err = parse_arg(l, args), 0 != err) {
                goto done;
            }
        }
    }

done:
    if (0 != err) {
        log_sys_error(args, err, "reading config file");
    }

    if (NULL != r.f) {
        fclose(r.f);
    }

    free(b.buf);
    return err;
}

#if 0
static void dump_args(const abl_args *args) {
    abl_string *s;

    log_debug(args, "pamh            = %p",  args->pamh);
    log_debug(args, "debug           = %d",  args->debug);
    log_debug(args, "no_warn         = %d",  args->no_warn);
    log_debug(args, "use_first_pass  = %d",  args->use_first_pass);
    log_debug(args, "try_first_pass  = %d",  args->try_first_pass);
    log_debug(args, "use_mapped_pass = %d",  args->use_mapped_pass);
    log_debug(args, "expose_account  = %d",  args->expose_account);

    log_debug(args, "host_db         = %s",  args->host_db);
    log_debug(args, "host_rule       = %s",  args->host_rule);
    log_debug(args, "host_purge      = %ld", args->host_purge);

    log_debug(args, "user_db         = %s",  args->user_db);
    log_debug(args, "user_rule       = %s",  args->user_rule);
    log_debug(args, "user_purge      = %ld", args->user_purge);

    for (s = args->strs; NULL != s; s = s->link) {
        log_debug(args, "str[%p] = %s", s, (char *) (s + 1));
    }
}
#endif

/* Parse our argments and populate an abl_args structure accordingly.
 */
int config_parse_args(pam_handle_t *pamh, int argc, const char **argv, abl_args *args) {
    int argn;
    int err;

    config_clear(pamh, args);

    for (argn = 0; argn < argc; argn++) {
        if (err = parse_arg(argv[argn], args), PAM_SUCCESS != err) {
            return err;
        }
    }

    /*dump_args(args);*/

    return PAM_SUCCESS;
}

/* Destroy any storage allocated by args
 */
void config_free(abl_args *args) {
    abl_string *s, *next;

    for (s = args->strs; s != NULL; s = next) {
        next = s->link;
        free(s);
    }

    args->strs = NULL;
}

