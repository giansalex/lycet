# LYCET - Greenter
[![Travis-CI](https://img.shields.io/travis/giansalex/lycet.svg?branch=master&style=flat-square)](https://travis-ci.org/giansalex/lycet)

Lycet is a REST API based on [greenter](https://github.com/giansalex/greenter) and Symfony 4 Framework.

### Requirements
- Php >=7.1 or above
- Php Extensions (gd, soap, xml, openssl, zlib)
- WkhtmltoPdf executable (PDf report)
- Pem Certificate

### Steps

**Install project**
```
git clone https://github.com/giansalex/lycet
cd lycet
composer install --no-dev -o
```

**Configure Settings**   
In `.env`  file in project root directory, change these settings.
```
###> greenter/greenter ###
WKHTMLTOPDF_PATH=full/path/wkhtmltopdf.exe
CLIENT_TOKEN=123456
SOL_USER=20000000001MODDATOS
SOL_PASS=moddatos
###< greenter/greenter ###
```

**Run**    
Using Php Built-in Web Server.
```
php -S 0.0.0.0:8000 -t public
```
Go http://localhost:8000/

### Docs
View [swagger documentation](http://petstore.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml), you can create a client with [swagger editor](http://editor.swagger.io/?url=https://raw.githubusercontent.com/giansalex/lycet/master/public/swagger.yaml).

