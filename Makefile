update:
	docker compose up -d

run:
	docker compose up

build:
	docker compose build

stop:
	docker compose down --remove-orphans

sql:
	mysql -u root -h 127.0.0.1 -p
