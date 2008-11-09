ACPIDIR=$(PREFIX)/etc/acpi

ACPIACTIONS=power_button.sh acpi_voltage.pl
ACPIEVENTS=power_button.conf acpi_voltage.conf

all:

clean:

install:
	mkdir -p /$(ACPIDIR)/events
	mkdir -p /$(ACPIDIR)/actions

	for i in $(ACPIACTIONS); do \
		install -m 755 acpi/$$i $(ACPIDIR)/actions; \
	done

	for i in $(ACPIEVENTS); do \
		install -m 644 acpi/$$i $(ACPIDIR)/events; \
	done
