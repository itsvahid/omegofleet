init: docker-down-clear docker-up

test:
	docker compose exec web php bin/phpunit

docker-up:
	docker compose up -d

docker-down-clear:
	docker compose down -v