ACPIDIR=$(PREFIX)/etc/acpi

ACPIACTIONS=acpi_temp.pl
ACPIEVENTS=acpi_temp.conf

all:

clean:

install:
	mkdir -p $(ACPIDIR)/actions
	mkdir -p $(ACPIDIR)/events

	for i in $(ACPIACTIONS); do \
		install -m 755 acpi/actions/$$i $(ACPIDIR)/actions; \
	done

	for i in $(ACPIEVENTS); do \
		install -m 644 acpi/events/$$i $(ACPIDIR)/events; \
	done
