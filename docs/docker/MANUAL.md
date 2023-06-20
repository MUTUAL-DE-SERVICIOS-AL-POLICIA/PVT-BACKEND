# PLATAFORMA VIRTUAL DE TRÁMITES
## Requirements
* [Docker](https://docs.docker.com/install/)
* [Docker Compose](https://docs.docker.com/compose/install/)
* Conexión a internet

***
## Install
- Clonar el proyecto
```sh
git clone https://github.com/MUTUAL-DE-SERVICIOS-AL-POLICIA/PVT-BACKEND.git
cd PVT-BACKEND
```

- Editamos la configuracion del archivo .env con las credenciales de las bases de datos

- Clonamos el submodulo de laradock

```sh
git clone https://github.com/Laradock/laradock.git
```

- copiamos los archivos de configuracion del laradock

```sh
cp -f docs/docker/docker-compose.yml laradock/
cp -f docs/docker/env-example laradock/.env
```
- ingresamos a la carpeta laradock

```sh
cd laradock
```

- levantamos las imagenes y los contenedores

```sh
docker-compose up -d nginx redis workspace
```
- Instalamos las dependensias de la aplicacion

```sh
docker-compose exec --user laradock workspace composer install
```
