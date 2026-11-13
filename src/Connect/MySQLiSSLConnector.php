<?php
namespace Obj63mc\ORM\Connect;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Connect\MySQLiConnector;

/** MySQLiConnector with a CA-only SSL connection that verifies the server certificate */
class MySQLiSSLConnector extends MySQLiConnector
{

    public function connect($parameters, $selectDB = false)
    {
        // Normally $selectDB is set to false by the MySQLDatabase controller, as per convention
        $selectedDB = ($selectDB && !empty($parameters['database'])) ? $parameters['database'] : null;

        // Connection charset and collation
        $connCharset = Config::inst()->get(MySQLDatabase::class, 'connection_charset');
        $connCollation = Config::inst()->get(MySQLDatabase::class, 'connection_collation');
        $socket = Environment::getEnv('SS_DATABASE_SOCKET');

        $this->dbConn = mysqli_init();

        // Without this, mysqli returns ints and floats as strings for non-prepared queries
        // (e.g. DataList::byIDs()), so a Boolean "0" is truthy in templates
        if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
            $this->dbConn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        } else {
            user_error(
                'mysqlnd PHP library is not available, numeric values will be fetched from the DB as strings',
                E_USER_NOTICE
            );
        }

        $this->dbConn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        $this->dbConn->ssl_set(null, null, $this->caFilePath(), null, null);

        $this->dbConn->real_connect(
            $parameters['server'],
            $parameters['username'],
            $parameters['password'],
            $selectedDB,
            !empty($parameters['port']) ? $parameters['port'] : ini_get("mysqli.default_port"),
            $socket ?: null,
            $this->connectionFlags()
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

    private function caFilePath(): string
    {
        $root = Environment::getEnv('SSL_CA_FILEROOT') ?: ($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '.';

        return $root . '/' . Environment::getEnv('SSL_CA_FILENAME');
    }

    /** SS_DATABASE_FLAGS as a bitmask, e.g. "MYSQLI_CLIENT_COMPRESS,MYSQLI_CLIENT_INTERACTIVE" */
    private function connectionFlags(): int
    {
        $flags = 0;
        $names = Environment::getEnv('SS_DATABASE_FLAGS');
        if ($names) {
            foreach (explode(',', $names) as $name) {
                $flags |= constant(trim($name));
            }
        }

        return $flags;
    }

}
