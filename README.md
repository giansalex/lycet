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

#### API de gestión de empresas

También puede gestionar empresas programáticamente via API:

**Registrar/Actualizar empresa:**
```bash
curl -X PUT "http://localhost:8000/api/v1/configuration/company/20000000001?token=123456" \
  -H "Content-Type: application/json" \
  -d '{
    "SOL_USER": "20000000001MODDATOS",
    "SOL_PASS": "moddatos",
    "certificate": "<certificado .pem en base64>",
    "FE_URL": "https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService"
  }'
```

**Subir logo (separado para evitar límite de payload):**
```bash
curl -X PUT "http://localhost:8000/api/v1/configuration/company/20000000001/logo?token=123456" \
  -H "Content-Type: application/json" \
  -d '{"logo": "<logo en base64>"}'
```

**Eliminar logo:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/configuration/company/20000000001/logo?token=123456"
```

**Eliminar empresa:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/configuration/company/20000000001?token=123456"
```

Los campos opcionales (`FE_URL`, `RE_URL`, `GUIA_URL`, `AUTH_URL`, `API_URL`, `CLIENT_ID`, `CLIENT_SECRET`) permiten configurar URLs por empresa. Si no se proporcionan, se usan las del `.env`.

#### Entornos por empresa

Cada empresa puede definir varios **entornos** con nombre libre (`beta`, `production`, `sunat-beta`, `bizlink-prod`, …) y un
campo `environment` que indica cuál está activo. Esto permite dejar preconfiguradas las credenciales, certificado y URLs de
homologación, producción o de distintos OSE, y "conmutar" entre ellos sin reenviar toda la configuración.

Cualquier clave (`SOL_USER`, `SOL_PASS`, `certificate`, `FE_URL`, `RE_URL`, `GUIA_URL`, `AUTH_URL`, `API_URL`, `CLIENT_ID`,
`CLIENT_SECRET`) se resuelve con este orden de prioridad:

1. El bloque del entorno activo: `environments[<environment>]`.
2. La raíz de la empresa.
3. Solo para `FE_URL`, `RE_URL` y `GUIA_URL`, y solo si el entorno activo se llama `beta` o `production`: las URLs oficiales
   de SUNAT (`Greenter\Ws\Services\SunatEndpoints`). Por eso `beta` y `production` se pueden activar sin definir un bloque.
4. El archivo `.env`.

> Las URLs definidas en la raíz de la empresa tienen prioridad sobre las URLs oficiales de SUNAT. Si quiere que un entorno
> `production` use las URLs de SUNAT, no defina `FE_URL`/`RE_URL`/`GUIA_URL` en la raíz (o defínalas dentro de cada bloque).

Si una empresa no tiene `environment`, se comporta como hasta ahora (raíz → `.env`), por lo que los archivos existentes siguen
funcionando sin cambios.

**Consultar el estado de una empresa** (no expone `SOL_PASS`, `CLIENT_SECRET` ni certificados):
```bash
curl "http://localhost:8000/api/v1/configuration/company/20000000001?token=123456"
```
```json
{
  "ruc": "20000000001",
  "environment": "production",
  "environments": ["production", "bizlink-prod"],
  "hasLogo": true,
  "hasCertificate": true,
  "SOL_USER": "20000000001REAL",
  "FE_URL": "https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService",
  "RE_URL": "https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService",
  "GUIA_URL": "https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService",
  "AUTH_URL": "https://api-seguridad.sunat.gob.pe/v1",
  "API_URL": "https://api-cpe.sunat.gob.pe/v1",
  "CLIENT_ID": "xxxxxxxx"
}
```

**Registrar/Actualizar un entorno** (todos los campos son opcionales; el certificado se guarda como `{ruc}-{entorno}-cert.pem`):
```bash
curl -X PUT "http://localhost:8000/api/v1/configuration/company/20000000001/environments/production?token=123456" \
  -H "Content-Type: application/json" \
  -d '{
    "SOL_USER": "20000000001REAL",
    "SOL_PASS": "clave-real",
    "certificate": "<certificado .pem en base64>"
  }'
```

**Conmutar el entorno activo** (`beta`, `production` o el nombre de un entorno definido; se persiste en `empresas.json`):
```bash
curl -X PUT "http://localhost:8000/api/v1/configuration/company/20000000001/environment?token=123456" \
  -H "Content-Type: application/json" \
  -d '{"environment": "production"}'
```

**Eliminar un entorno:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/configuration/company/20000000001/environments/production?token=123456"
```

El endpoint `PUT /company/{ruc}` también acepta `environment` y `environments` (mapa `nombre => configuración`) para
registrar todo en una sola llamada, y conserva los entornos ya definidos cuando no se envían.

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
  },
  "20000000003": {
    "SOL_USER": "20000000003MODDATOS",
    "SOL_PASS": "moddatos",
    "certificate": "20000000003-cert.pem",
    "logo": "20000000003-logo.png",
    "environment": "production",
    "environments": {
      "production": {
        "SOL_USER": "20000000003REAL",
        "SOL_PASS": "clave-real",
        "certificate": "20000000003-production-cert.pem"
      },
      "bizlink-prod": {
        "FE_URL": "https://ose.bizlinks.com/billService",
        "RE_URL": "https://ose.bizlinks.com/billService",
        "CLIENT_ID": "prod-id",
        "CLIENT_SECRET": "prod-secret"
      }
    }
  }
}
```
> La empresa `20000000003` usa las credenciales y certificado del bloque `production`, hereda el `logo` de la raíz y, al no
> definir URLs, usa las oficiales de SUNAT de producción. Si se cambia `environment` a `bizlink-prod`, usará las URLs del OSE
> y las credenciales SOL de la raíz.
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

