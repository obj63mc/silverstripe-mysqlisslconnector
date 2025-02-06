<?php
namespace Obj63mc\ORM\Connect;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Connect\MySQLiConnector;

class MySQLiSSLConnector extends MySQLiConnector
{

    public function connect($parameters, $selectDB = false)
    {
        if(Environment::getEnv('SSL_CA_FILEROOT')){
            $documentRoot = Environment::getEnv('SSL_CA_FILEROOT');
        } else {
            $documentRoot = $_SERVER['DOCUMENT_ROOT'];
            if(!$documentRoot){
                $documentRoot = '.';
            }
        }
        // Normally $selectDB is set to false by the MySQLDatabase controller, as per convention
        $selectedDB = ($selectDB && !empty($parameters['database'])) ? $parameters['database'] : null;

        // Connection charset and collation
        $connCharset = Config::inst()->get(MySQLDatabase::class, 'connection_charset');
        $connCollation = Config::inst()->get(MySQLDatabase::class, 'connection_collation');

        $this->dbConn = mysqli_init();

        //Custom SSL Config for RDS PEM only.
        $this->dbConn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        $this->dbConn->ssl_set(NULL, NULL, $documentRoot.'/'.Environment::getEnv('SSL_CA_FILENAME'), NULL, NULL);

        $this->dbConn->real_connect(
            $parameters['server'],
            $parameters['username'],
            $parameters['password'],
            $selectedDB,
            !empty($parameters['port']) ? $parameters['port'] : ini_get("mysqli.default_port")
        );

        if ($this->dbConn->connect_error) {
            $this->databaseError("Couldn't connect to MySQL database | " . $this->dbConn->connect_error);
        }

        // Set charset and collation if given and not null. Can explicitly set to empty string to omit
        $charset = isset($parameters['charset'])
        ? $parameters['charset']
        : $connCharset;

        if (!empty($charset)) {
            $this->dbConn->set_charset($charset);
        }

        $collation = isset($parameters['collation'])
        ? $parameters['collation']
        : $connCollation;

        if (!empty($collation)) {
            $this->dbConn->query("SET collation_connection = {$collation}");
        }
    }

}
