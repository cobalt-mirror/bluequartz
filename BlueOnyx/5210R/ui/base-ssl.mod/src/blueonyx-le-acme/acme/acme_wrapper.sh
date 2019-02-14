#!/bin/sh
arg1="$1"
shift 1

export LE_WORKING_DIR="/usr/sausalito/acme"
export LE_CONFIG_HOME="/usr/sausalito/acme/data"
#alias acme.sh="/usr/sausalito/acme/acme.sh --config-home '/usr/sausalito/acme/data'"

/usr/sausalito/acme/acme.sh --config-home '/usr/sausalito/acme/data' "$@"
