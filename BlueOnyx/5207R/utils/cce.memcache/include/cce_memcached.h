#ifndef __CCE_MEMCACHED_H__
#define __CCE_MEMCACHED_H__

#include <cce_scalar.h>

extern memcached_st *memc;
extern memcached_return rv;

int connect_memcached(void);
int get_from_memcached(char *key, cce_scalar *scalar);
int set_to_memcached(char *key, char *value);
int disconnect_memcached(void);
int flush_memcached(void);

#endif
