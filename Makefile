# COLORS
GREEN		= \033[1;32m
RED 		= \033[1;31m
ORANGE		= \033[1;33m
CYAN		= \033[1;36m
RESET		= \033[0m

# FOLDER
SRCS_DIR	= ./
ENV_FILE	= ${SRCS_DIR}.env
DOCKER_DIR	= ${SRCS_DIR}docker-compose.yml

# VARIABLES
DB_NAME = your_database
DB_PASSWORD = your_password
DB_USER = your_username
DB_CONTAINER = postgres

# COMMANDS
DOCKER =  docker compose -f ${DOCKER_DIR} --env-file ${ENV_FILE} -p camagru

%:
	@:

all: up

start: up

up:
	@echo "${GREEN}Starting containers...${RESET}"
	@${DOCKER} up -d --remove-orphans

down:
	@echo "${RED}Stopping containers...${RESET}"
	@${DOCKER} down

stop:
	@echo "${RED}Stopping containers...${RESET}"
	@${DOCKER} stop

rebuild:
	@echo "${GREEN}Rebuilding containers...${RESET}"
	@${DOCKER} up -d --remove-orphans --build

delete:
	@echo "${RED}Deleting containers...${RESET}"
	@${DOCKER} down -v --remove-orphans

rebuild-no-cache:
	@echo "${GREEN}Rebuilding containers...${RESET}"
	@${DOCKER} build --no-cache
	@${DOCKER} up -d --remove-orphans --build

frankenphp:
	@echo "${GREEN}Running frankenphp ...${RESET}"
	@${DOCKER} exec frankenphp sh
seed: delete up
# wait for postgres to start
	@sleep 2        
	docker exec -e PGPASSWORD=${DB_PASSWORD} -it $(DB_CONTAINER) psql -U $(DB_USER) -d $(DB_NAME) -h postgres -c "\i /docker-entrypoint-initdb.d/init.sql"
	docker exec -e PGPASSWORD=${DB_PASSWORD} -it $(DB_CONTAINER) psql -U $(DB_USER) -d $(DB_NAME) -h postgres -c "\i /docker-entrypoint-initdb.d/seed.sql"
	@echo "${GREEN}Database seeding completed successfully!${RESET}"

.PHONY: all start up down stop rebuild delete rebuild-no-cache frankenphp