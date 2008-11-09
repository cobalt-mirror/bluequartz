/* $Id: cce_ed.c 3 2003-07-17 15:19:15Z will $
 * ed implementation
 *
 */

#include "cce_ed_internal.h"

#include <string.h>
#include <cscp_fsm.h>
#include <sys/time.h>
#include <unistd.h>

/* local function declarations */
static void reset_ed_for_new_txn (cce_ed *, codb_handle *);
static int dispatch_events (cce_ed * ed, GHashTable * eventlist_hash);
static int run_handlers (cce_ed * ed, oid_t oid, GSList *);

/*
 * Functions for hashes of oids
 */
guint
oid_hash(gconstpointer key)
{
    /* note that we always pass in teh address of an oid_t */
    return (guint) (*((oid_t *) key));
}

gint
oid_equal(gconstpointer a, gconstpointer b)
{
    /* note that we always deal with pointers to oid_t */
    return (*((oid_t *) a) == *((oid_t *) b)) ? TRUE : FALSE;
}


/*
 * Functions exported for manipulating an ed
 */

/** create an ed
 */
cce_ed *
cce_ed_new (cce_conf * conf)
{
    cce_ed *ed;
    ed = malloc (sizeof (cce_ed));
    DASSERT (ed != NULL);
    if (!ed)
        return NULL;

    ed->conf = conf;
    ed->odb = NULL;
    ed->msgs = NULL;
    ed->prop_msgs = g_hash_table_new (oid_hash, oid_equal);

    return ed;
}

/** branch ed
 */
cce_ed *
cce_ed_branch (cce_ed *ed)
{
    return cce_ed_new(ed->conf);
}

/** destroy an ed
 */
void
cce_ed_destroy (cce_ed * ed)
{
    DASSERT (ed != NULL);
    reset_ed_for_new_txn (ed, NULL);
    free (ed);
}

/** flush an ed
 */
void
cce_ed_flush (cce_ed * ed)
{
    DASSERT (ed != NULL);
    reset_ed_for_new_txn (ed, NULL);
}


/*
 * utility functions
 */

/* free the oid_t used in a hash of {oid => (list of event)} */
static gboolean
GHR_remove_events (gpointer key, gpointer val, gpointer data)
{
    //DPRINTF(DBG_ED, "GHR_remove_events: 0x%lx\n", (long) key);
    //DPRINTF(DBG_ED, "FREE OID: %ld at 0x%lx\n", *((oid_t *) key), (long) key);

    DASSERT (key != NULL);
    free (key);

    /* no need to free the data, just the list */
    g_slist_free((GSList *)val);
        
    return TRUE;
}

/* see if a hash of oid=>event has a given oid */
static int
eventhash_has_entry (GHashTable *ehash, codb_event *event)
{
    oid_t oid;
    oid = codb_event_get_oid(event);

    if (g_hash_table_lookup(ehash, &oid)) {
        return 1;
    } else {
        return 0;
    }
}

/* 
 * add an event to the current list for it's oid 
 * takes a hash of {oid => (list of codb_event)} */
static void
cce_ed_addevent(GHashTable *ehash, codb_event *event)
{
    gpointer key, val;
    GSList *eventlist;
    oid_t oid;

    oid = codb_event_get_oid(event);

    if (!ehash || !event)
        return;

    if (!g_hash_table_lookup_extended(ehash, &oid, &key, &val)) {
        /* key not already in db */
        key = malloc (sizeof(oid_t));
        *((oid_t *)key) = oid;
        val = NULL;
    } else {
        /* key already in db, append to end of list */
    }
    eventlist = (GSList *)val;
    eventlist = g_slist_append(eventlist, event);
    g_hash_table_insert(ehash, key, eventlist);
}

/** cce_ed_dispatch
 * the ed dispatch algorithm:
 *        1. examine the codb_handle object and extract a list of events.
 *        2. use the conf object to get a list of handlers for the list of
 *             events.
 *        3. run list of events.
 *        4. let the caller know how we did
 */
int
cce_ed_dispatch(cce_ed *ed, codb_handle *odb)
{
    int 		ret;
    GSList 	*events;
    GSList 	*cursor;
    GHashTable 	*create_events, 
    		*modify_events, 
		*destroy_events;
    codb_handle *old_odb;

    //DPRINTF(DBG_ED, "cce_ed_dispatch:\n");

    /* test against recursion */
    if (codb_handle_branch_level (odb) >= ED_MAX_DEPTH) {
        DPRINTF(DBG_ED, "cce_ed_dispatch: maximum recursion (%d) reached\n",
            (int)codb_handle_branch_level(odb) );
        return -1;
    }

    /* clear the message lists */
    old_odb = ed->odb;
    reset_ed_for_new_txn(ed, odb);

    /* +A: get a GSList object containing all the events in the curren txn */
    codb_list_events(odb, &events);

    /* +B: sort events by object and type */
    create_events = g_hash_table_new(oid_hash, oid_equal);
    modify_events = g_hash_table_new(oid_hash, oid_equal);
    destroy_events = g_hash_table_new(oid_hash, oid_equal);

    /* for each event in the list ... */
    for (cursor = events; cursor; cursor = g_slist_next (cursor)) {
        codb_event *e = (codb_event *)cursor->data;

        /* figure out what kind of event it is */
        if (codb_event_is_create(e)) {
            /* create event */
            DPRINTF(DBG_ED, "Create event: %lu (%s)\n",
                    (unsigned long)codb_event_get_oid(e), 
                    codb_event_get_string(e));
            cce_ed_addevent(create_events, e);
        } else if (codb_event_is_modify(e)) {
            /* 
             * only deal with modify events for objects that don't also have
             * create events 
             */
            if (!eventhash_has_entry(create_events, e)) {
                DPRINTF(DBG_ED, "Modify event: %lu.%s\n",
                    (unsigned long)codb_event_get_oid(e), 
                    codb_event_get_string(e));
                cce_ed_addevent(modify_events, e);
            }
        } else if (codb_event_is_destroy(e)) {
            /* destroy event */
            DPRINTF(DBG_ED, "Destroy event: %lu (%s)\n",
                (unsigned long)codb_event_get_oid(e), 
                codb_event_get_string(e));
            cce_ed_addevent(destroy_events, e);
        } else {
            /* bad event! */
            DPRINTF(DBG_ED, "bad event %lu (%s)\n",
                (unsigned long)codb_event_get_oid(e), 
                codb_event_get_string(e));
        }
    }

    /* dispatch all event handlers */
    ret = dispatch_events(ed, create_events);
    if (ret == 0)
        ret = dispatch_events(ed, modify_events);
    if (ret == 0)
        ret = dispatch_events(ed, destroy_events);

    /* -B: free event hashes */
    g_hash_table_foreach_remove(create_events, GHR_remove_events, NULL);
    g_hash_table_destroy(create_events);
    g_hash_table_foreach_remove(modify_events, GHR_remove_events, NULL);
    g_hash_table_destroy(modify_events);
    g_hash_table_foreach_remove(destroy_events, GHR_remove_events, NULL);
    g_hash_table_destroy(destroy_events);

    /* -A: free all events that the g_slist object that contains them */
    codb_free_events(&events);

    ed->odb = old_odb; /* reset ed! */

    return ret;
}

/** translate_events_to_handlers
    * 
    * Given a list of events for an object, creates a list of
    * handlers for that object.    Groovy.
    * 
    * FIXME: this only works for a single namespace - must be fixed for txns
    */
static int
translate_events_to_handlers(cce_ed *ed, oid_t oid, GSList *events,
	GSList **handlers)
{
    GHashTable *handler_hash;
    GSList *handler_queue = NULL;
    GSList *cursor;
    GSList *star_handler_queue = NULL;
    GString *gstr_namespace;
    GString *gstr_propname;
    codb_event *event;
    char *class, *val;
    int count;
    int created_flag;

    //DPRINTF(DBG_ED, "translate_events_to_handlers: oid = %lu\n", oid);

    /* discover class */
    class = codb_get_classname(ed->odb, oid);
    if (!class) {
        DPRINTF(DBG_ED, 
        "translate_events_to_handlers: object %ld has no class!\n", oid);
        return -1;
    }

    /* hash used to uniquify handlers */
    handler_hash = g_hash_table_new(g_direct_hash, g_direct_equal);

    /* this flag indicates whether the object was created in this txn */
    created_flag = 0;

    /* walk through events list */
    while (events) {
        event = (codb_event *)events->data;
        DASSERT(event->oid == oid);

        switch (event->type) {
        case CREATE:
            DPRINTF(DBG_ED, "--CREATE\n");

            /* get list of handlers for this event */
            handler_queue = cce_conf_get_handlers(ed->conf, class,
                "", EVENTNAME_CREATE);
            created_flag = 1;
            break;

        case MODIFY:
            if (created_flag)
                break;

            gstr_namespace = g_string_new ("");
            gstr_propname = g_string_new ("");

            val = (char *)codb_event_get_string (event);
            DPRINTF(DBG_ED, "--MODIFY %lu.%s\n", oid, val);
            {
                char *cursor, *ptr;
                cursor = strchr ((char *) val, '.');
                if (!cursor) {
                    /* no nspace in prop string - all property */
                    g_string_append (gstr_propname, (char *) val);
                } else {
                    /* ah ha, a namespace */
                    ptr = (char *) val;
                    while (ptr < cursor) {
                        g_string_append_c (gstr_namespace, *ptr);
                        ptr++;
                    }
                    ptr++; /* skip the dot */
                    g_string_append (gstr_propname, ptr);
                }
            }

            /* get list of handlers for this event */
            handler_queue = cce_conf_get_handlers (ed->conf, class,
                gstr_namespace->str, gstr_propname->str);

            /* only _MODIFY has a '*' property */
            if ((strcasecmp (gstr_propname->str, "_CREATE") != 0)
             && (strcasecmp (gstr_propname->str, "_DESTROY") != 0)) {
                /* get handlers for the global modify event "*" */
                star_handler_queue = cce_conf_get_handlers (ed->conf,
                    class, gstr_namespace->str, "*");
            }

            /* cleanup */
            g_string_free (gstr_namespace, TRUE);
            g_string_free (gstr_propname, TRUE);
            break;

        case DESTROY:
            DPRINTF(DBG_ED, "--DESTROY\n");
            /* get list of handlers for this event */
            handler_queue = cce_conf_get_handlers (ed->conf, class,
                "", EVENTNAME_DESTROY);
            break;

        default:
            DPRINTF(DBG_ED, "--ERROR: unknown event type encountered.\n");
            /* FIXME: bad event, should emit some kind of message */
            break;
        };

        /* add handlers to a hash to uniquify them */
        for (cursor = handler_queue; cursor; cursor = g_slist_next (cursor)) {
            cce_conf_handler *htmp;
            ed_handler_event *he;

            htmp = (cce_conf_handler *)cursor->data;
            //DPRINTF(DBG_ED, "----handler: %s:%s\n", cce_conf_handler_type(htmp),
                // cce_conf_handler_data(htmp));

            he = g_hash_table_lookup(handler_hash, htmp);
            if (he) {
                he->events = g_slist_append(he->events, event);
            } else {
                he = handler_event_new(htmp);
                he->events = g_slist_append(he->events, event);
                g_hash_table_insert(handler_hash, htmp, he);
            }
        }
        /* don't free the list - it is kept inside conf */
        handler_queue = NULL;

        /* add more handlers, if needed */
        for (cursor=star_handler_queue; cursor; cursor=g_slist_next(cursor)) {
            cce_conf_handler *htmp;
            ed_handler_event *he;

            htmp = (cce_conf_handler *) cursor->data;
            //DPRINTF(DBG_ED, "----handler: %s:%s\n", 
                //cce_conf_handler_type(htmp), cce_conf_handler_data (htmp));

            he = g_hash_table_lookup(handler_hash, htmp);
            if (!he) {
                he = handler_event_new(htmp);
                g_hash_table_insert(handler_hash, htmp, he);
            }
            he->events = g_slist_append(he->events, event);
        }
        /* don't free the list - it is kept inside conf */
        star_handler_queue = NULL;

        /* next event */
        events = g_slist_next(events);
    }

    /* appends keys from handler_hash to the handler_list */
    //DPRINTF(DBG_ED, "Uniquified handler list:\n");
    count = 0;
    {
        GHashIter *it;
        gpointer key, val;
        it = g_hash_iter_new (handler_hash);
        for (g_hash_iter_first (it, &key, &val); key;
             g_hash_iter_next (it, &key, &val)) {
	    /* append to the list of unique handler events */
            ed_handler_event *he = (ed_handler_event *)val;
            *handlers = g_slist_append(*handlers, he);

            count++;
            //DPRINTF(DBG_ED, "----handler %d: %s:%s\n", count,
                //cce_conf_handler_type(he->handler),
                //cce_conf_handler_data(he->handler));
        }
        g_hash_iter_destroy (it);
    }
    g_hash_table_destroy(handler_hash);

    free (class);

    return count;
}

ed_handler_event *
handler_event_new(cce_conf_handler *h)
{
    ed_handler_event *e;

    e = malloc(sizeof(ed_handler_event));
    if (!e) {
        return NULL;
    }

    e->handler = h;
    e->events = NULL;

    return e;
}

void
handler_event_destroy(ed_handler_event *e)
{
    if (e->events) {
        g_slist_free(e->events);
    }
    free(e);
}

/** dispatch_events
 * Takes a hash of { oid -> (list of events) } and, for each object,
 * generates the list of handlers associated with the eventlist,
 * and runs that handler set.    
 * 
 * dispatch_events bails at the first sign of handler failure.
 */
static int
dispatch_events(cce_ed *ed, GHashTable *event_hash)
{
    int ret;
    GHashIter *it;
    GSList *eventlist;
    GSList *handlers;
    gpointer key, val;
    oid_t oid;

    DPRINTF(DBG_ED, "dispatch_events:\n");

    /* For each object... */
    it = g_hash_iter_new(event_hash);
    ret = 0;

    for (g_hash_iter_first(it, &key, &val); key && (ret == 0);
         g_hash_iter_next(it, &key, &val)) {
        /* gather handlers for this object */
        eventlist = (GSList *)val;
        oid = *((oid_t *)key);
        handlers = NULL; /* initialize to empty list */

        DPRINTF(DBG_ED, "object %ld\n", oid);

        translate_events_to_handlers(ed, oid, eventlist, &handlers);

        /* run handlers for this object */
        ret = run_handlers(ed, oid, handlers);
        {
	        GSList *p = handlers;
	        ed_handler_event *he;
	        
	        while (p) {
	            he = (ed_handler_event *)p->data;
	            handler_event_destroy(he);
	            p = g_slist_next(p);
	        }
                g_slist_free(handlers); /* just free the list, not the data */
        }
    }
    g_hash_iter_destroy(it);

    DPRINTF(DBG_ED, "dispatch_events finished: %d\n", ret);

    return ret;
}

void print_handler_queue(GSList *defered, GSList *remaining)
{
#ifdef DEBUG_ED
    cce_conf_handler *h;
    ed_handler_event *he;

    fprintf(stderr, "\nRemaining: ");
    while (remaining) {
        fprintf(stderr, "%lx:%lx:", (long)remaining, (long)(remaining->data));
        he = (ed_handler_event *)remaining->data;
        h = he->handler;

        fprintf(stderr, "%s ", cce_conf_handler_data(h));
        remaining = remaining->next;
    }
    fprintf(stderr, "\n");

    fprintf(stderr, "Defered: ");
    while (defered) {
        he = (ed_handler_event *)defered->data;
        h = he->handler;

        fprintf(stderr, "%s ", cce_conf_handler_data(h));
        defered = defered->next;
    }
    fprintf(stderr, "\n\n");
#endif
}

/** run_handlers
 * takes a list of ed_handler_event objects
 */
static int
run_handlers(cce_ed *ed, oid_t oid, GSList *handlers)
{
    struct timeval start;
    GSList *defered = NULL;
    GSList *remaining = NULL;
    GSList *cursor = NULL;
    cce_conf_handler *handler;
    ed_handler_event *he;
    int ret = 0;
    char *type;
    int stage;
    int cnt_run;
    int free_remains = 0;

    DPRINTF(DBG_ED, "run_handlers: started\n");

    for (stage = H_STAGE_NONE + 1; (!ret) && (stage < H_STAGE_MAX); stage++) {
        //DPRINTF(DBG_ED, "run_handlers: stage %d\n", stage);
        cnt_run = 0; /* number of handlers run in this pass */
        remaining = handlers;
        defered = NULL;
        free_remains = 0;
        while (remaining && !ret) {
            print_handler_queue(defered, remaining);
            for (cursor=remaining; cursor && !ret; cursor=g_slist_next(cursor)){
                he = (ed_handler_event *)cursor->data;
                handler = he->handler;
                type = cce_conf_handler_type(handler);

                /* only do handlers in this stage */
                if (stage != cce_conf_handler_nstage(handler)) {
                    continue;
                }

                DPRINTF(DBG_ED, "** handler %s:%s (oid %lu)\n",
                    type, cce_conf_handler_data(handler), oid);

		DPROFILE_START(PROF_ED, &start, "handler %s:%s", 
			type, cce_conf_handler_data(handler));

                if (strcasecmp (type, "exec") == 0) {
                    ret = handler_exec(ed, oid, he);
		} else if (strcasecmp (type, "perl") == 0) {
                    ret = handler_perl(ed, oid, he);
                } else {
                    /* test is the default handler */
                    ret = handler_test(ed, oid, he);
                }

                if (ret == FSM_RET_SUCCESS) {
                    /* count only successes */
                    cnt_run++;
                    DPRINTF(DBG_ED, "-- handler succeeded\n");
		    DPROFILE(PROF_ED, start, "handler(%s) succeeded",
			cce_conf_handler_data(handler));
                } else if (ret == FSM_RET_DEFER) {
                    /* move a deferred handler to the new list */
                    defered = g_slist_append(defered, he);
                    ret = 0;
                    DPRINTF(DBG_ED, "-- handler defered\n"); 
		    DPROFILE(PROF_ED, start, "handler(%s) defered",
			cce_conf_handler_data(handler));
                } else {
                    DPRINTF(DBG_ED, "-- handler failed\n"); 
		    DPROFILE(PROF_ED, start, "handler(%s) failed",
			cce_conf_handler_data(handler));
                }

            } /* end of for loop */

            /* set up defered handlers for next pass */
            if (free_remains) {
                /* this was a built-up defers list - free the list structure */
                g_slist_free(remaining);
            }

            remaining = defered;
            defered = NULL;
            free_remains = 1;

            /* check for deadlock */
            if (!cnt_run && remaining) {
                /* deadlock has occurred */
                DPRINTF(DBG_ED, "handler deadlock detected\n");
                ret = -1;
            }
        }
    }

    /* 
     * FIXME: we should log any handlers that do not get run because of 
     * a failure 
     */
    if (ret) { 
    	DPRINTF(DBG_ED, "run_handlers: failed\n"); 
    } else { 
    	DPRINTF(DBG_ED, "run_handlers: succeeded\n"); 
    }
    
    return ret;
}

gboolean
GHR_remove_str_str (gpointer key, gpointer val, gpointer data)
{
    free (key);
    free (val);
    return TRUE;
}

/* reset_ed_for_new_txn
 */
static void
reset_ed_for_new_txn (cce_ed * ed, codb_handle * odb)
{
    GHashIter *it;
    GHashTable *badkey_hash;
    oid_t *oidP;

    DPRINTF(DBG_ED, "Flushing the ED object of all messages.\n");

    /* free all generic messages */
    while (ed->msgs) {
        free (ed->msgs->data);
        ed->msgs = g_slist_remove (ed->msgs, ed->msgs->data);
    }

    /* free all badkey messages */
    it = g_hash_iter_new (ed->prop_msgs);
    for (g_hash_iter_first(it, (gpointer *)&oidP, (gpointer *)&badkey_hash); 
         oidP; 
         g_hash_iter_next(it, (gpointer *)&oidP, (gpointer *)&badkey_hash)) {
        free (oidP);

        if (badkey_hash) {
            g_hash_table_foreach_remove (badkey_hash, GHR_remove_str_str, NULL);
            g_hash_table_destroy (badkey_hash);
        }
    }
    g_hash_iter_destroy (it);
    g_hash_table_destroy (ed->prop_msgs);
    ed->prop_msgs = g_hash_table_new (oid_hash, oid_equal);

    ed->odb = odb;

    return;
}

GHashTable *
cce_ed_access_baddata (cce_ed * ed, oid_t oid)
{
    GHashTable *baddata_hash;
    baddata_hash = g_hash_table_lookup (ed->prop_msgs, &oid);
    return baddata_hash;
}

GSList *
cce_ed_access_messages (cce_ed * ed)
{
    return ed->msgs;
}

void
cce_ed_add_message (cce_ed * ed, char *msg)
{
    char *msgdup;

    if (!ed || !msg)
        return;

    msgdup = strdup (msg);
    if (!msgdup)
        return;

    ed->msgs = g_slist_append (ed->msgs, msgdup);
}

void
cce_ed_add_baddata (cce_ed * ed, oid_t oid, char *prop, char *why)
{
    GHashTable *badkey_hash;
    gpointer key, val;
    gpointer prop_key, prop_val;

    if (!g_hash_table_lookup_extended(ed->prop_msgs, &oid, 
        &prop_key, &prop_val)) {
        prop_key = malloc (sizeof (oid_t));
        *((oid_t *)prop_key) = oid;
        badkey_hash = g_hash_table_new(g_str_hash, g_str_equal);
    } else {
        badkey_hash = (GHashTable *)prop_val;
    }

    if (g_hash_table_lookup_extended (badkey_hash, prop, &key, &val)) {
        /* free previous entry so we can replace it: */
        g_hash_table_remove(badkey_hash, key);
        free (val);
        free (key);
    }

    key = strdup(prop);
    val = strdup(why);

    /* insert oid / why into badkey_hash */
    g_hash_table_insert(badkey_hash, key, val);

    /* update prop_msgs */
    g_hash_table_insert(ed->prop_msgs, prop_key, badkey_hash);
}

/* eof */
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
