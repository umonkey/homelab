SERVICE_NAME=homelab_land

build:
	docker build -t $(SERVICE_NAME) .

run:
	docker run --rm -p 8003:80 --name $(SERVICE_NAME) --replace $(SERVICE_NAME)

debug:
	docker run --rm -it $(SERVICE_NAME) bash
