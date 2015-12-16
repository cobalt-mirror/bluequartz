#include <string.h>
#include <cce_common.h>
#include <cce_scalar.h>
#include <libmemcached/memcached.h>
#include <cce_memcached.h>

/* globals to use memcached */
memcached_st *memc = NULL;
memcached_return rv;

int connect_memcached(void)
{
	if (memc == NULL) {
		DPRINTF(DBG_MEMCACHED, "Create memcahed connection\n");
		if ((memc = memcached_create(NULL)) == NULL) {
			DPRINTF(DBG_MEMCACHED, "failed to allocate memory\n");
			return 1;
		}
		rv = memcached_server_add(memc, "localhost", 11211);
		if (rv != MEMCACHED_SUCCESS) {
			DPRINTF(DBG_MEMCACHED, "failed to set server\n");
			return 1;
		}
	}

	return 0;
}


int get_from_memcached(char *key, cce_scalar *scalar)
{
	uint32_t flags;
	char *value;
	size_t result_length;

	value = memcached_get(memc, key, strlen(key), &result_length, &flags, &rv);
	if (rv != MEMCACHED_SUCCESS) {
		DPRINTF(DBG_MEMCACHED, "No cache : %s\n", key);
		cce_scalar_undefine(scalar);
		return -1;
	}

	DPRINTF(DBG_MEMCACHED, "Hit cache : %s\n", key);

	/* realloc */   
	if (!cce_scalar_resize(scalar, result_length)) {
		return -1;
	}
        cce_scalar_reset(scalar); /* otherwise not null-terminated. */

	scalar->data = value;

	return result_length;
}


int set_to_memcached(char *key, char *value)
{
	rv =  memcached_set(memc, key, strlen(key), value, strlen(value), 0, 0);
	DPRINTF(DBG_MEMCACHED, "Write cache : %s\n", key);

	if (rv != MEMCACHED_SUCCESS) {
		DPRINTF(DBG_MEMCACHED, "failed to set record : %s\n", key);
		return 1;
	}
	return 0;
}


int delete_from_memcached(char *key)
{
	rv = memcached_delete(memc, key, strlen(key), 0);
	DPRINTF(DBG_MEMCACHED, "Delete cache : %s\n", key);

	if (rv != MEMCACHED_SUCCESS) {
		DPRINTF(DBG_MEMCACHED, "failed to delete record : %s\n", key);
		return 1;
	}
	return 0;
}


int disconnect_memcached(void)
{
	memcached_free(memc);
}


int flush_memcached(void)
{
	DPRINTF(DBG_MEMCACHED, "Flush memcached\n");
	memcached_flush(memc, 0);
}
