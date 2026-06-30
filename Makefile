SAIL = ./vendor/bin/sail

install:
	docker run --rm \
		-u "$$(id -u):$$(id -g)" \
		-v "$$(pwd):/var/www/html" \
		-w /var/www/html \
		laravelsail/php84-composer:latest \
		composer install --ignore-platform-reqs
	$(SAIL) up -d
	$(SAIL) artisan key:generate
	$(SAIL) artisan migrate --seed
	$(SAIL) artisan moonshine:install -Q -u
	$(SAIL) artisan moonshine:user --email=admin --name=admin --password=admin

down:
	$(SAIL) down

update:
	git pull
	$(SAIL) composer install
	$(SAIL) artisan migrate