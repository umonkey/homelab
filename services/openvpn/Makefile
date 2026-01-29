SERVICE_NAME=homelab_openvpn

build:
	docker build -t $(SERVICE_NAME) .

shell:
	docker run --rm -it $(SERVICE_NAME) bash

lshell:
	docker exec -it $(SERVICE_NAME) bash
