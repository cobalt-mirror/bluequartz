/* pam_abl.c */
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

#define MAXUSERS 200

static int purge    = 0;
static int verbose  = 0;
static int relative = 0;
static int okusers  = 0;
static int okhosts  = 0;

static const char *okuser[MAXUSERS];
static const char *okhost[MAXUSERS];

#define PAD "    "

static void help(const char *prg) {
    printf("Usage: %s [OPTION] [CONFIG]\n", prg);
    printf("Perform maintenance on the databases used by the pam_abl (auto blacklist)\n"
           "module. CONFIG is the name of the pam_abl config file (defaults to\n"
           CONFIG "). The config file is read to discover the names\n"
           "of the pam_abl databases and the rules that control purging of old data\n"
           "from them. The following options are available:\n\n"
           "  -h, --help       See this message.\n"
           "  -p, --purge      Purge databases according to purge rules in config.\n"
/*           "  -r, --relative   Display times relative to now.\n"
           "  -v, --verbose    Verbose output.\n" */
           "  --okuser=USER    Unblock USER.   (Shell wildcards may be\n"
           "  --okhost=HOST    Unblock HOST.    used in USER and HOST)\n"
           "\n"
           "Report bugs to <andy@hexten.net>.\n");
}

static void mention(const char *msg, ...) {
    if (verbose > 0) {
        va_list ap;
        va_start(ap, msg);
        vprintf(msg, ap);
        printf("\n");
        va_end(ap);
    }
}

static void die(const char *msg, ...) {
    va_list ap;
    va_start(ap, msg);
    fprintf(stderr, "Fatal: ");
    vfprintf(stderr, msg, ap);
    fprintf(stderr, "\n");
    va_end(ap);
    exit(1);
}

static int wildmatch(const char *key, const char *value, const char *end) {
    for (;;) {
        switch (*key) {
        case '\0':
            return value == end;
        case '*':
            key++;
            for (;;) {
                if (wildmatch(key, value, end)) {
                    return 1;
                }
                if (value == end) {
                    return 0;
                }
                value++;
            }
        case '?':
            if (value == end) {
                return 0;
            }
            break;
        default:
            if (value == end || *value++ != *key) {
                return 0;
            }
            break;
        }
        key++;
    }
}

static int iswild(const char *key) {
    return NULL != strchr(key, '*') || NULL != strchr(key, '?');
}

static void make_key(DBT *dbt, const char *key) {
    memset(dbt, 0, sizeof(*dbt));
    dbt->data = (void *) key;
    dbt->size = strlen(key);
    dbt->ulen = dbt->size + 1;
}

static void reltime(long t) {
    long days    = t / (24 * 60 * 60);
    long hours   = t / (60 * 60) % 24;
    long minutes = t / 60 % 60;
    long seconds = t % 60;
    printf(PAD PAD "%ld/%02ld:%02ld:%02ld\n", days, hours, minutes, seconds);
}

static void showblocking(const abl_args *args, const char *rule, const time_t *history, size_t histsz, time_t now) {
    int op = 0;
    while (*rule) {
        const char *up;
        const char *colon = strchr(rule, '=');
        if (NULL == colon) {
            break;
        }
        up = rule;
        rule = colon + 1;
        if (rule_matchperiods(args, history, histsz, now, &rule)) {
            if (!op) {
                printf(PAD PAD "Blocking users [");
                op = 1;
            } else {
                printf("], [");
            }
            while (up != colon) {
               putchar(*up++);
            } 
        }
        while (*rule != '\0' && !isspace(*rule)) {
            rule++;
        }
        while (*rule != '\0' && isspace(*rule)) {
            rule++;
        }
    }

    if (op) {
        printf("]\n");
    } else {
        printf(PAD PAD "Not blocking\n");
    }
}

static int doshow(const abl_args *args, const char *rule, const char *dbname, const char *thing) {
    DB *db;
    int err, err2;
    DBT key, data;
    DBC *cursor;
    time_t now = time(NULL);
    char *buf = NULL;
    int bsz = 0;
    int cnt = 0;

    if (NULL == dbname) {
        return 0;
    }

    printf("Failed %s:\n", thing);

    if (err = db_create(&db, NULL, 0), 0 != err) {
        log_sys_error(args, err, "creating database object");
        return err;
    }

#if DB_VERSION_MAJOR > 4 || DB_VERSION_MAJOR == 4 && DB_VERSION_MINOR >= 1
    if (err = db->open(db, NULL, dbname, NULL, DB_BTREE, 0, 0), 0 != err) {
#else
    if (err = db->open(db, dbname, NULL, DB_BTREE, 0, 0), 0 != err) {
#endif
        if (ENOENT == err) {
            return 0;
        } else {
            log_sys_error(args, err, "opening or creating database");
            return err;
        }
    }

    memset(&key,  0, sizeof(key));
    key.flags = DB_DBT_REALLOC;
    memset(&data, 0, sizeof(data));
    data.flags = DB_DBT_REALLOC;

    if (err = db->cursor(db, NULL, &cursor, 0), 0 != err) {
        log_sys_error(args, err, "creating cursor");
        goto fail;
    }

    for (;;) {
        time_t *tm;
        size_t ntm;
        
        err = cursor->c_get(cursor, &key, &data, DB_NEXT);
        if (DB_NOTFOUND == err) {
            break;
        } else if (0 != err) {
            log_sys_error(args, err, "iterating cursor");
            goto fail;
        }

        /* Print it out */
        if (bsz < key.size + 1) {
            char *nb;
            int ns = key.size + 80;
            if (nb = realloc(buf, ns), NULL == nb) {
                log_sys_error(args, ENOMEM, "displaying item");
                goto fail;
            }
            buf = nb;
            bsz = ns;
        }

        memcpy(buf, key.data, key.size);
        buf[key.size] = '\0';
/*        printf(PAD "%s (%d)\n", buf, data.size / sizeof(time_t)); */
        printf(PAD "%s (%d)", buf, data.size / sizeof(time_t));
        cnt++;

        tm = (time_t *) data.data;
        ntm = data.size / sizeof(time_t);
/*        if (verbose) {
            while (ntm != 0) {
                ntm--;
                if (relative) {
                    reltime((long) difftime(now, tm[ntm]));
                } else {
                    printf(PAD PAD "%s", ctime(&tm[ntm]));
                }
            }
        } else */
        if (NULL != rule) {
            showblocking(args, rule, tm, ntm, now);  
        }
    }

    if (0 == cnt) {
        printf("   <none>\n");
    }

    /* Cleanup */
fail:
    free(key.data);
    free(data.data);
    free(buf);

    if (err2 = db->close(db, 0), 0 != err2) {
        log_sys_error(args, err2, "closing database");
        if (0 == err) {
            err = err2;
        }
    }

    return err;
}

static int dopurge(const abl_args *args, const char *dbname, long maxage) {
    DB *db;
    int err, err2;
    DBT key, data;
    DBC *cursor;
    time_t now = time(NULL);

    if (NULL == dbname) {
        return 0;
    }

    mention("Purging %s", dbname);

    if (err = db_create(&db, NULL, 0), 0 != err) {
        log_sys_error(args, err, "creating database object");
        return err;
    }

#if DB_VERSION_MAJOR > 4 || DB_VERSION_MAJOR == 4 && DB_VERSION_MINOR >= 1
    if (err = db->open(db, NULL, dbname, NULL, DB_BTREE, 0, 0), 0 != err) {
#else
    if (err = db->open(db, dbname, NULL, DB_BTREE, 0, 0), 0 != err) {
#endif
        if (ENOENT == err) {
            return 0;
        } else {
            log_sys_error(args, err, "opening or creating database");
            return err;
        }
    }

    memset(&key,  0, sizeof(key));
    key.flags = DB_DBT_REALLOC;
    memset(&data, 0, sizeof(data));
    data.flags = DB_DBT_REALLOC;

    if (err = db->cursor(db, NULL, &cursor, 0), 0 != err) {
        log_sys_error(args, err, "creating cursor");
        goto fail;
    }

    for (;;) {
        err = cursor->c_get(cursor, &key, &data, DB_NEXT);
        if (DB_NOTFOUND == err) {
            break;
        } else if (0 != err) {
            log_sys_error(args, err, "iterating cursor");
            goto fail;
        }

        if (rule_purge(&data, maxage, now)) {
            if (data.size == 0) {
                if (err = cursor->c_del(cursor, 0), 0 != err) {
                    goto fail;
                }
            } else {
                if (err = cursor->c_put(cursor, &key, &data, DB_CURRENT), 0 != err) {
                    goto fail;
                }
            }
        }
    }

    /* Cleanup */
fail:
    free(key.data);
    free(data.data);

    if (err2 = db->close(db, 0), 0 != err2) {
        log_sys_error(args, err2, "closing database");
        if (0 == err) {
            err = err2;
        }
    }

    return err;
}

static int dook(const abl_args *args, const char *dbname, const char **ok, int nok) {
    DB *db;
    int err, err2;
    DBT key, data;
    DBC *cursor;
    int wild = 0;
    int n, hit, del = 0;
    int *done;

    if (NULL == dbname) {
        return 0;
    }

    if (done = malloc(sizeof(int) * nok), NULL == done) {
        log_sys_error(args, ENOMEM, "allocating buffer");
    }
    
    /* Any wildcards? */
    for (n = 0; n < nok; n++) {
        if (iswild(ok[n])) {
            wild = 1;
        }
        done[n] = 0;
    }

    if (err = db_create(&db, NULL, 0), 0 != err) {
        log_sys_error(args, err, "creating database object");
        return err;
    }

#if DB_VERSION_MAJOR > 4 || DB_VERSION_MAJOR == 4 && DB_VERSION_MINOR >= 1
    if (err = db->open(db, NULL, dbname, NULL, DB_BTREE, 0, 0), 0 != err) {
#else
    if (err = db->open(db, dbname, NULL, DB_BTREE, 0, 0), 0 != err) {
#endif
        if (ENOENT == err) {
            return 0;
        } else {
            log_sys_error(args, err, "opening or creating database");
            return err;
        }
    }

    memset(&key,  0, sizeof(key));
    key.flags = DB_DBT_REALLOC;
    memset(&data, 0, sizeof(data));
    data.flags = 0;

    if (wild) {
        /* wildcard delete */

        if (err = db->cursor(db, NULL, &cursor, 0), 0 != err) {
            log_sys_error(args, err, "creating cursor");
            goto fail;
        }

        for (;;) {
            err = cursor->c_get(cursor, &key, &data, DB_NEXT);
            if (DB_NOTFOUND == err) {
                break;
            } else if (0 != err) {
                log_sys_error(args, err, "iterating cursor");
                goto fail;
            }

            for (n = 0, hit = 0; n < nok; n++) {
                if (wildmatch(ok[n], (const char *) key.data, (const char *) key.data + key.size)) {
                    done[n] = 1;
                    hit = 1;
                }
            }

            if (hit) {
                if (err = cursor->c_del(cursor, 0), 0 != err) {
                    goto fail;
                }
                del++;
            }
        }
    } else {
        /* delete individual items */
        for (n = 0; n < nok; n++) {
            if (!done[n]) {
                make_key(&key, ok[n]);
                err = db->del(db, NULL, &key, 0);
                if (0 == err) {
                    int m;
                    done[n] = 1;
                    del++;
                    /* Slightly pedantic - knock out any duplicates so we don't flag an error when
                     * we can't delete them later. Arguably if you supply duplicate arguments you
                     * should expect an error but this keeps the semantics the same as the wildcard
                     * case.
                     */
                    for (m = n + 1; m < nok; m++) {
                        if (strcmp(ok[n], ok[m]) == 0) {
                            done[m] = 1;
                        }
                    }
                } else if (DB_NOTFOUND == err) {
                    /* shrug */
                } else {
                    goto fail;
                }
            }
        }
    }

    if (verbose) {
        hit = 0;
        if (del) {
            printf("Deleted %d item%s\n", del, del == 1 ? "" : "s");
        }
        for (n = 0; n < nok; n++) {
            if (!done[n]) {
                if (!hit) {
                    printf("These keys could not be matched:\n");
                    hit++;
                }
                printf(PAD "%s\n", ok[n]);
            }
        }
    }

    /* Cleanup */
fail:
    free(done);

    if (err2 = db->close(db, 0), 0 != err2) {
        log_sys_error(args, err2, "closing database");
        if (0 == err) {
            err = err2;
        }
    }

    return err;
}

static int parseeq(int argc, char **argv, int *argn, const char *pattern, const char **arg) {
    size_t l = strlen(pattern);
    if (strlen(argv[*argn]) < l || memcmp(argv[*argn], pattern, l) != 0) {
        return 0;   /* no match */
    }

    if (argv[*argn][l] == '=') {
        *arg = argv[*argn] + l + 1;
        return 1;   /* = match */
    }

    if (*argn + 1 < argc) {
        *arg = argv[++(*argn)];
        return 2;   /* next arg match, argn incremented */
    }

    /* Don't bother with the return value */
    die("Missing argument to %s", pattern);
    
    return -1;  /* missing arg */
}

int main(int argc, char **argv) {
    int argn, err;
    char *conf = NULL;
    abl_args args;

    for (argn = 1; argn < argc; argn++) {
        if (argv[argn][0] == '-') {
            if (argv[argn][1] == '-') {
                const char *ap;

                /* long option? */
                if (0 == strcmp(argv[argn] + 2, "help")) {
                    help(argv[0]);
                    return 0;
                } else if (0 == strcmp(argv[argn] + 2, "purge")) {
                    purge = 1;
                } else if (0 == strcmp(argv[argn] + 2, "relative")) {
                    relative = 1;
                } else if (0 == strcmp(argv[argn] + 2, "verbose")) {
                    verbose = 1;
                } else if (parseeq(argc, argv, &argn, "--okuser", &ap)) {
                    if (okusers >= MAXUSERS) {
                        die("Too many users specified (max %d)", MAXUSERS);
                    }
                    okuser[okusers++] = ap;
                } else if (parseeq(argc, argv, &argn, "--okhost", &ap)) {
                    if (okhosts >= MAXUSERS) {
                        die("Too many hosts specified (max %d)", MAXUSERS);
                    }
                    okhost[okhosts++] = ap;
                } else {
                    die("Bad option: %s", argv[argn]);
                }
            } else {
                char *op;
                for (op = argv[argn] + 1; *op; op++) {
                    switch (*op) {
                    case 'h':
                        help(argv[0]);
                        return 0;
                    case 'p':
                        purge = 1;
                        break;
                    case 'r':
                        relative = 1;
                        break;
                    case 'v':
                        verbose = 1;
                        break;
                    default:
                        die("Bad option: -%c", *op);
                    }
                }
            }
        } else {
            if (NULL != conf) {
                die("Config file already named");
            }
            conf = argv[argn];
        }
    }

    if (NULL == conf) {
        conf = CONFIG;
    }

    config_clear(NULL, &args);
    mention("Reading config from %s", conf);
    if (err = config_parse_file(conf, &args), 0 != err) {
        return err;
    }

    if (NULL == args.user_db) {
        mention("No user_db in %s", conf);
    }

    if (NULL == args.host_db) {
        mention("No host_db in %s", conf);
    }

    if (okusers > 0 || okhosts > 0) {
        dook(&args, args.user_db, okuser, okusers);
        dook(&args, args.host_db, okhost, okhosts); 
    }
    
    if (purge) {
        dopurge(&args, args.user_db, args.user_purge);
        dopurge(&args, args.host_db, args.host_purge);
    } else if (okusers == 0 && okhosts == 0) {
        doshow(&args, args.user_rule, args.user_db, "users");
        doshow(&args, args.host_rule, args.host_db, "hosts");
    }

    config_free(&args);

    return 0;
}

