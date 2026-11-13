# SilverStripe MySQLi SSL Connector

SilverStripe module to allow MySQLi SSL connection with CA certificate only

# Setup -

1. Install Module
        
        composer require obj63mc/silverstripe-mysqlisslconnector

2. Place your CA certificate bundle in the root of your project folder '/' eg. $_SERVER['DOCUMENT_ROOT']
3. Add an Environment variable for your CA filename eg.

        SSL_CA_FILENAME='rds-combined-ca-bundle.pem'

Once setup your DB will now be under an SSL Connection.

# Optional environment variables

- `SSL_CA_FILEROOT`: folder that holds the CA file, for when `$_SERVER['DOCUMENT_ROOT']` isn't set (e.g. cron tasks)
- `SS_DATABASE_SOCKET` and `SS_DATABASE_FLAGS`: work the same as with the framework's `MySQLiConnector`
