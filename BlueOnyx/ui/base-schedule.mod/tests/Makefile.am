TESTS = 01_test 02_test 03_test 04_test

bin_SCRIPTS = test_action

clean:
	rm -f $(TESTS)
	rm -f $(bin_SCRIPTS)

%: %.pl
	cp $^ $@
	chmod 755 $@
