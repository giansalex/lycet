# Lycet - Greenter
[![Travis-CI](https://img.shields.io/travis/giansalex/lycet.svg?branch=master&style=flat-square)](https://travis-ci.org/giansalex/lycet)

Lycet es un API REST basado en [greenter](https://github.com/giansalex/greenter) y Symfony Framework v4, UBL 2.1 es soportado.

## Requerimientos
- Php 7.2 o superior
- Php Extensions habilitadas (gd, soap, xml, openssl, zlib)
- WkhtmltoPdf executable (PDF report)
- Pem Certificate - [convert pfx to pem](https://github.com/giansalex/xmldsig/blob/master/CONVERT.md)

## Pasos

### Instalar Lycet
```
git clone https://github.com/giansalex/lycet
cd lycet
composer install -o
```

### Configuraciones  
En el archivo `.env` ubicado en la raiz del proyecto, cambiar estas configuraciones de acuerdo a su necesidad.
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

### Archivos Requeridos
Se necesita almacenar el certificado y logo en la carpeta `/data`, los archivos deben tener nombres especificos que se indican
a continuación.
```
/data
├── cert.pem
├── logo.png
```
También puede usar [lycet-ui-config](https://giansalex.github.io/lycet-ui-config/) como interfaz de usuario, siendo mas útil
esta opción cuando emplea contenedores.

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
docker run -p 8000:80 --name lycet_app lycet 
```

Abrir el navegador, y dirígete a http://localhost:8000/public

### Docs
Ver [swagger documentation](http://petstore.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), puedes crear un cliente en [swagger editor](http://editor.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), para tu lenguaje de preferencia.

