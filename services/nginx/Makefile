SERVICE_NAME=homelab_nginx

build:
	docker build -t $(SERVICE_NAME) .

shell:
	docker run --rm -e TURSO_API_TOKEN -it $(SERVICE_NAME) bash

lshell:
	docker exec -it $(SERVICE_NAME) bash
