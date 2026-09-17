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

### Documentación OpenAPI (Swagger)

La especificación `public/swagger.yaml` **se genera automáticamente** a partir de
las anotaciones `@OA\...` presentes en el código (controladores en `src/Controller`
y esquemas en `src/OpenApi`). Esto mantiene la documentación sincronizada con la API:
al agregar o modificar un endpoint solo hay que actualizar sus anotaciones y regenerar
el archivo.

Regenerar el swagger:
```
php bin/console app:swagger:generate
```

La opción `--output` (o `-o`) permite escribir en otra ruta, por ejemplo a un archivo
temporal (esto es lo que usa el CI para comparar sin sobrescribir el commit realizado):
```
php bin/console app:swagger:generate --output=swagger.generated.yaml
```

Flujo recomendado al cambiar la API:
1. Anota el endpoint con `@OA\Get` / `@OA\Post` junto a su `@Route`.
   > Cada controlador con anotaciones `@OA` debe incluir `use OpenApi\Annotations as OA;`.
2. Ejecuta `php bin/console app:swagger:generate`.
3. Realiza un commit del `public/swagger.yaml` actualizado.

El workflow de GitHub Actions incluye el job `swagger-sync`, que regenera la
especificación y falla si `public/swagger.yaml` quedó desactualizado respecto a las
anotaciones. Si el CI falla por este motivo, ejecuta el comando de generación y
realiza un commit del resultado.

### Docs

- [Conectando lycet desde nodejs](https://github.com/giansalex/lycet-demo-js)

Puedes visitar [greenter en postman](https://www.postman.com/greenter/) que contiene ejemplos del envío de algunos comprobantes.

Ver [swagger documentation](http://petstore.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), puedes crear un cliente en [swagger editor](http://editor.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), para tu lenguaje de preferencia.

