.PHONY: build up down restart logs shell clean

build:
	docker-compose build

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

logs:
	docker-compose logs -f

shell:
	docker-compose exec backender sh

clean:
	docker-compose down -v
	rm -rf storage/database storage/endpoints storage/logs

help:
	@echo "Backender - Available commands:"
	@echo "  make build   - Build the Docker image"
	@echo "  make up      - Start the container"
	@echo "  make down    - Stop the container"
	@echo "  make restart - Restart the container"
	@echo "  make logs    - View container logs"
	@echo "  make shell   - Open shell in container"
	@echo "  make clean   - Remove all data and containers"
