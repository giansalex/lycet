# Lycet - Greenter
[![Travis-CI](https://img.shields.io/travis/giansalex/lycet.svg?branch=master&style=flat-square)](https://travis-ci.org/giansalex/lycet)

Lycet es un API REST basado en [greenter](https://github.com/thegreenter/greenter) y Symfony Framework, UBL 2.1 es soportado.

**Objetivo:** Ofrecer una interfaz a greenter desde otros lenguajes de programación. 

LIVE (Pruebas)

|      :rocket: |                                      |
|--------------:|--------------------------------------|
|URL            | https://greenter-lycet.herokuapp.com/|    
|API TOKEN      | `greenter`                           |

## Requerimientos
- Php 7.2 o superior
- Php Extensions habilitadas (soap, xml, openssl, zlib, fileinfo)
- WkhtmltoPdf executable (PDF report)
- Pem Certificate - [convert pfx to pem](https://github.com/thegreenter/xmldsig/blob/master/CONVERT.md)

## Pasos

### Instalar Lycet
```
git clone https://github.com/giansalex/lycet
cd lycet
composer install -o
```

### Configuraciones  
En el archivo `.env` ubicado en la raíz del proyecto, podrá cambiar estas configuraciones.
```
###> greenter/greenter ###
WKHTMLTOPDF_PATH=full/path/wkhtmltopdf.exe
CLIENT_TOKEN=123456
SOL_USER=20000000001MODDATOS
SOL_PASS=moddatos
FE_URL=https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService
RE_URL=https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService
GUIA_URL=https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService
###< greenter/greenter ###
```

> Tener en cuenta que `SOL_USER` es la concatenación del **RUC + Usuario SOL**

### Archivos Requeridos
Se necesita almacenar el certificado y logo en la carpeta `/data`, los archivos deben tener nombres específicos que se indican
a continuación.
```
/data
├── cert.pem
├── logo.png
├── empresas.json (opcional para multiples empresas)
```
También puede usar [lycet-ui-config](https://giansalex.github.io/lycet-ui-config/) como interfaz de usuario, siendo mas útil
esta opción cuando emplea contenedores.  
Para mas detalles del contenido de `empresas.json` puedes revisarlo [aquí](https://github.com/giansalex/lycet/pull/129).

### Ejecutar    
Usando Php Built-in Web Server.
```
php -S 0.0.0.0:8000 -t public
```
Ir a http://localhost:8000/


### Docker
Deploy on Docker.
```
git clone https://github.com/giansalex/lycet
cd lycet
docker build -t lycet .
docker run -d -p 8000:8000 --name lycet_app lycet 
```

Abrir el navegador, y dirígete a http://localhost:8000/public

### Docs

Puedes descargar la [colección de postman](https://www.getpostman.com/collections/2ef4f4bd7c6720a9e09f) que contiene ejemplo del envío de algunos comprobantes.

Ver [swagger documentation](http://petstore.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), puedes crear un cliente en [swagger editor](http://editor.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), para tu lenguaje de preferencia.

