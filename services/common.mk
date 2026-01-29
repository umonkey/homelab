IMAGE_TAG=ghcr.io/umonkey/$(SERVICE_NAME):latest

build:
	docker build -t $(SERVICE_NAME) .

push:
	echo $(GHCR_TOKEN) | docker login ghcr.io -u umonkey --password-stdin
	docker tag $(SERVICE_NAME) $(IMAGE_TAG)
	docker push $(IMAGE_TAG)
	docker rmi -f $(IMAGE_TAG)
