# $Id: Makefile 3 2003-07-17 15:19:15Z will $

install:
	@cp -uv *.schema /usr/sausalito/schemas
	@cp -uv *.conf /usr/sausalito/conf
	@cp -uv *_handler /usr/sausalito/handlers
	@chmod +x /usr/sausalito/handlers/*_handler
	@echo "*** INSTALLED, RESTART CCE BEFORE RUNNING TEST ***"

test:
	perl ./alphabeta_test

uninstall:
	/bin/rm -f \
	  /usr/sausalito/schemas/test_alphabeta.schema \
	  /usr/sausalito/conf/test_alphabeta.conf \
	  /usr/sausalito/handlers/alpha_handler \
	  /usr/sausalito/handlers/beta_handler
      
