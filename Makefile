update:
	docker compose up -d

build:
	docker compose build

stop:
	docker compose down --remove-orphans
