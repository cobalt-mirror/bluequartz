/* $Id: pam_abl.c,v 1.1.1.1 2005/10/12 19:22:26 tagishandy Exp $
 *
 * Unless otherwise *explicitly* stated the following text
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
#define PAM_SM_AUTH

#include "pam_abl.h"

/* DB_BUFFER_SMALL is a relatively new error code */
#ifdef DB_BUFFER_SMALL
#define TOOSMALL DB_BUFFER_SMALL
#else
#define TOOSMALL ENOMEM
#endif

#define DBPERM 0600

static void make_key(DBT *dbt, const char *key) {
    memset(dbt, 0, sizeof(*dbt));
    dbt->data = (void *) key;
    dbt->size = strlen(key);
    dbt->ulen = dbt->size + 1;
}

/* Grow the buffer attached to a DBT if necessary.
 */
static int grow_buffer(const abl_args *args, DBT *dbt, u_int32_t minsize) {
    /*log_debug(args, "Current size %ld, desired size %ld", dbt->ulen, minsize);*/
    if (dbt->ulen < minsize) {
        void *nd;
        if (nd = realloc(dbt->data, minsize), NULL == nd) {
            log_sys_error(args, ENOMEM, "allocating record buffer");
            return ENOMEM;
        }
        dbt->data = nd;
        dbt->ulen = minsize;
        /*log_debug(args, "Buffer grown to %ld", dbt->ulen);*/
    }
    return 0;
}

/* Log a login attempt
 */
static int record(const abl_args *args, const char *dbname, const char *kv, time_t tm, long maxage) {
    DB *db;
    int err, err2;
    DBT key, data;

    if (err = db_create(&db, NULL, 0), 0 != err) {
        log_sys_error(args, err, "creating database object");
        return err;
    }

#if DB_VERSION_MAJOR > 4 || DB_VERSION_MAJOR == 4 && DB_VERSION_MINOR >= 1
    if (err = db->open(db, NULL, dbname, NULL, DB_BTREE,
#else
    if (err = db->open(db, dbname, NULL, DB_BTREE,
#endif
                        DB_CREATE, DBPERM), 0 != err) {
        log_sys_error(args, err, "opening or creating database");
        return err;
    }

    /*log_debug(args, "%s opened", dbname);*/

    /* Attempt to find an existing record */
    make_key(&key, kv);

    memset(&data, 0, sizeof(data));
    data.flags = DB_DBT_USERMEM;

    err = db->get(db, NULL, &key, &data, 0);
    if (TOOSMALL == err) {
        /* Buffer too small so grow it... */
        if (err = grow_buffer(args, &data, data.size + sizeof(time_t)), 0 != err) {
            goto fail2;
        }
        data.size = 0;
        /* ...and try again. */
        err = db->get(db, NULL, &key, &data, 0);
    }

    if (0 != err && DB_NOTFOUND != err) {
        goto fail2;
    }

    if (0 == err) {
        rule_purge(&data, maxage, tm);
    } else if (DB_NOTFOUND == err) {
        data.size = 0;
    }

    if (err = grow_buffer(args, &data, data.size + sizeof(time_t)), 0 != err) {
        goto fail2;
    }

    memcpy((char *) data.data + data.size, &tm, sizeof(time_t));
    data.size += sizeof(time_t);

    if (err = db->put(db, NULL, &key, &data, 0), 0 != err) {
        log_sys_error(args, err, "updating database");
    }

    /* Cleanup */
fail2:
    if (NULL != data.data) {
        free(data.data);
    }

    if (err2 = db->close(db, 0), 0 != err2) {
        log_sys_error(args, err2, "closing database");
        if (0 == err) {
            err = err2;
        }
    }

    return err;
}

static int record_host(const abl_args *args, time_t tm) {
    if (NULL != args->host_db) {
        const char *rhost;
        int err;

        if (err = pam_get_item(args->pamh, PAM_RHOST, (const void **) &rhost), PAM_SUCCESS != err) {
            log_pam_error(args, err, "getting PAM_RHOST");
            return err;
        }
        if (NULL != rhost) {
            return record(args, args->host_db, rhost, tm, args->host_purge);
        } else {
            log_debug(args, "PAM_RHOST is NULL");
            return 0;
        }
    } else {
        return 0;
    }
}

static int record_user(const abl_args *args, time_t tm) {
    if (NULL != args->user_db) {
        const char *user;
        int err;
        if (err = pam_get_item(args->pamh, PAM_USER, (const void **) &user), PAM_SUCCESS != err) {
            log_pam_error(args, err, "getting PAM_USER");
            return err;
        }
        if (NULL != user) {
            return record(args, args->user_db, user, tm, args->user_purge);
        } else {
            log_debug(args, "PAM_USER is NULL");
            return 0;
        }
    } else {
        return 0;
    }
}

static int record_attempt(const abl_args *args) {
    int err;
    time_t tm = time(NULL);

    log_debug(args, "Recording failed attempt");

    if (err = record_host(args, tm), 0 != err) {
        return err;
    }

    if (err = record_user(args, tm), 0 != err) {
        return err;
    }

    return 0;
}

/* Check whether access should be permitted for the specified user.
 */
static int check(const abl_args *args, const char *dbname, const char *user, const char *service,
                 const char *rule, const char *kv, time_t tm, int *rv) {
    DB *db;
    int err, err2;
    DBT key, data;
    int sz;

    if (err = db_create(&db, NULL, 0), 0 != err) {
        log_sys_error(args, err, "creating database object");
        return err;
    }

#if DB_VERSION_MAJOR > 4 || DB_VERSION_MAJOR == 4 && DB_VERSION_MINOR >= 1
    if (err = db->open(db, NULL, dbname, NULL, DB_BTREE,
#else
    if (err = db->open(db, dbname, NULL, DB_BTREE,
#endif
                       0, DBPERM), 0 != err) {
        if (ENOENT == err) {
            return 0;
        } else {
            log_sys_error(args, err, "opening or creating database");
            return err;
        }
    }

    log_debug(args, "%s opened", dbname);

    make_key(&key, kv);

    memset(&data, 0, sizeof(data));
    data.flags = DB_DBT_MALLOC;

    err = db->get(db, NULL, &key, &data, 0);
    if (0 != err) {
        if (DB_NOTFOUND == err) {
            err = 0;
        }
        goto fail2;
    }

    sz = data.size / sizeof(time_t);
    *rv = rule_test(args, rule, user, service, (time_t *) data.data, sz, tm);

    /* Cleanup */
fail2:
    if (NULL != data.data) {
        free(data.data);
    }

    if (err2 = db->close(db, 0), 0 != err2) {
        log_sys_error(args, err2, "closing database");
        if (0 == err) {
            err = err2;
        }
    }

    return err;
}

static int check_host(const abl_args *args, const char *user, const char *service, time_t tm, int *rv) {
    if (NULL != args->host_db) {
        const char *rhost;
        int err;
        if (err = pam_get_item(args->pamh, PAM_RHOST, (const void **) &rhost), PAM_SUCCESS != err) {
            log_pam_error(args, err, "getting PAM_RHOST");
            return err;
        }
        if (NULL != rhost) {
            log_debug(args, "Checking host %s", rhost);
            return check(args, args->host_db, rhost, service, args->host_rule, rhost, tm, rv);

        } else {
            log_debug(args, "PAM_RHOST is NULL");
            return 0;
        }
    } else {
        return 0;
    }
}

static int check_user(const abl_args *args, const char *user, const char *service, time_t tm, int *rv) {
    if (NULL != args->user_db) {
        log_debug(args, "Checking user %s", user);
        return check(args, args->user_db, user, service, args->user_rule, user, tm, rv);
    } else {
        return 0;
    }
}

static int check_attempt(const abl_args *args, int *rv) {
    int err;
    time_t tm = time(NULL);
    const char *user;
    const char *service;

    if (err = pam_get_item(args->pamh, PAM_USER, (const void **) &user), PAM_SUCCESS != err) {
        log_pam_error(args, err, "getting PAM_USER");
        return err;
    }

    if (err = pam_get_item(args->pamh, PAM_SERVICE, (const void **) &service), PAM_SUCCESS != err) {
        log_pam_error(args, err, "getting PAM_SERVICE");
        return err;
    }

    *rv = 0;
    if (user != NULL && service != NULL) {
        if (err = check_host(args, user, service, tm, rv), 0 != err) {
            return err;
        }
        if (0 == *rv && (err = check_user(args, user, service, tm, rv), 0 != err)) {
            return err;
        }
    }

    return 0;
}

static void cleanup(pam_handle_t *pamh, void *data, int err) {
    if (NULL != data) {
        abl_args *args = data;
        log_debug(args, "In cleanup, err is %08x", err);

        if (err && (err & PAM_DATA_REPLACE) == 0 && !args->check_only) {
            record_attempt(args);
        }
        config_free(args);
        free(args);
    }
}

/* Authentication management functions */

PAM_EXTERN int pam_sm_authenticate(pam_handle_t *pamh, int flags, int argc, const char **argv) {
    abl_args *args;
    int err = PAM_SUCCESS;

    /*log_debug(NULL, "pam_sm_authenticate(), flags=%08x", flags);*/

    if (args = malloc(sizeof(abl_args)), NULL == args) {
        return PAM_BUF_ERR;
    }

    if (err = config_parse_args(pamh, argc, argv, args), PAM_SUCCESS == err) {
        int rv = 0;

        if (err = pam_set_data(pamh, DATA_NAME, args, cleanup), PAM_SUCCESS != err) {
            goto fail;
        }

        if (args->record_attempt) {
		record_attempt(args);
		return PAM_AUTH_ERR;
	}

	check_attempt(args, &rv);
        if (rv) {
            const char *rhost, *user, *service;
            if (PAM_SUCCESS == pam_get_item(args->pamh, PAM_RHOST,   (const void **) &rhost  ) &&
                PAM_SUCCESS == pam_get_item(args->pamh, PAM_USER,    (const void **) &user   ) &&
                PAM_SUCCESS == pam_get_item(args->pamh, PAM_SERVICE, (const void **) &service)) {
                log_info(args, "Blocking access from %s to service %s, user %s", rhost, service, user);
            }
            return PAM_AUTH_ERR;
        } else {
            return PAM_SUCCESS;
        }
    }

fail:
    config_free(args);
    free(args);
    return err;
}

PAM_EXTERN int pam_sm_setcred(pam_handle_t *pamh, int flags, int argc, const char **argv) {
    return pam_set_data(pamh, DATA_NAME, NULL, cleanup);
}

/* Init structure for static modules */
#ifdef PAM_STATIC
struct pam_module _pam_abl_modstruct = {
    MODULE_NAME,
    pam_sm_authenticate,
    pam_sm_setcred,
    NULL,
    NULL,
    NULL,
    NULL
};
#endif

