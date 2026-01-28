SERVICE_NAME=homelab_files

build:
	docker build -t $(SERVICE_NAME) .

run:
	docker run --rm -p 8001:80 --name $(SERVICE_NAME) $(SERVICE_NAME)

debug:
	docker run --rm -it $(SERVICE_NAME) bash
