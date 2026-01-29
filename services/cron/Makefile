SERVICE_NAME=homelab-cron
include ../common.mk

shell:
	docker run --rm -e TURSO_API_TOKEN -it $(SERVICE_NAME) bash

lshell:
	docker exec -it $(SERVICE_NAME) bash
