.PHONY: up seed down logs css

up:
	docker compose up -d --build

seed:
	docker compose exec app php bin/seed.php

down:
	docker compose down

logs:
	docker compose logs -f app db

css:
	npm run build:css
