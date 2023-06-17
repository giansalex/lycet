# Lycet - Greenter
[![Symfony](https://github.com/giansalex/lycet/actions/workflows/symfony.yml/badge.svg)](https://github.com/giansalex/lycet/actions/workflows/symfony.yml)
[![PPM Compatible](https://raw.githubusercontent.com/php-pm/ppm-badge/master/ppm-badge.png)](https://github.com/php-pm/php-pm)

Lycet es un API REST basado en [greenter](https://github.com/thegreenter/greenter) y Symfony Framework, UBL 2.1 es soportado.

**Objetivo:** Ofrecer una interfaz a greenter desde otros lenguajes de programación. 

LIVE (Pruebas)

|      :rocket: |                                      |
|--------------:|--------------------------------------|
|URL            | https://greenter-lycet.herokuapp.com/|    
|API TOKEN      | `greenter`                           |

## Requerimientos
- Php 7.4 o superior
- Php Extensions habilitadas (soap, xml, openssl, zlib)
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

Ejemplo de contenido del archivo `empresas.json`, tambien puede cambiar la URL de los servicios para apuntar a un OSE.

```json
{
  "20000000001": {
    "SOL_USER": "20000000001MODDATOS",
    "SOL_PASS": "moddatos",
    "certificate": "20000000001-cert.pem",
    "logo": "20000000001-logo.png"
  },
  "20000000002": {
    "SOL_USER": "20000000002MODDATOS",
    "SOL_PASS": "moddatos",
    "certificate": "20000000002-cert.pem",
    "logo": "20000000002-logo.png",
    "FE_URL": "https://my-ose.com/billService",
    "RE_URL": "https://my-ose.com/billService",
    "GUIA_URL": "https://my-ose.com/billService",
    "AUTH_URL": "https://api-test-seguridad.sunat.gob.pe/v1",
    "API_URL": "https://api-test.sunat.gob.pe/v1",
    "CLIENT_ID": "85e5b0ae-255c-4891-a595-0b98c65c9854",
    "CLIENT_SECRET": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```
> Para pruebas de Guia de remision, utilizar la siguiente configuración [issue#605](https://github.com/giansalex/lycet/issues/605)

### Ejecutar    
Usando Php Built-in Web Server.
```
php -S 0.0.0.0:8000 -t public
```
Ir a http://localhost:8000/


### Docker
Desplegar con Docker.
```
git clone https://github.com/giansalex/lycet
cd lycet
docker build -t lycet .

# copiar certificado y logo de prueba (puedes reemplazar por uno personal)
cp tests/Resources/* data
# ejecutar el contenedor
docker run -d -p 8000:8000  -v ./data:/var/www/html/data --name lycet_app lycet
```

Abrir el navegador, y dirígete a http://localhost:8000/

### Docs

- [Conectando lycet desde nodejs](https://github.com/giansalex/lycet-demo-js)

Puedes visitar [greenter en postman](https://www.postman.com/greenter/) que contiene ejemplos del envío de algunos comprobantes.

Ver [swagger documentation](http://petstore.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), puedes crear un cliente en [swagger editor](http://editor.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), para tu lenguaje de preferencia.

