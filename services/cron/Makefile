build:
	docker build -t homelab_cron .

shell:
	docker run --rm -e TURSO_API_TOKEN -it homelab_cron bash

lshell:
	docker exec -it homelab_cron bash
