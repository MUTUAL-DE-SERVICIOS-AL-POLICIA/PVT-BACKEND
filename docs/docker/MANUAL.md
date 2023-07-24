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

- copiamos los archivos de configuracion de laradock

```sh
cp -f docs/docker/docker-compose.yml laradock/
cp -f docs/docker/env-example laradock/.env
```
- Ingresamos a la carpeta laradock

```sh
cd laradock
```

- En el archivo .env modificamos el puerto en el cual queremos que se ejecute el servicio nginx, como la siguiente siguiente linea

```sh
NGINX_HOST_HTTP_PORT=80
```

- construimos la imagen de nginx, esta se encargara de levantar las imagenes necesarias para el proyecto

```sh
docker-compose build --no-cache nginx
```
- levantamos los contenedores necesarios

```sh
docker-compose up -d nginx
```

- Instalamos las dependencias de la aplicacion 

```sh
docker-compose exec --user laradock workspace composer install
```
