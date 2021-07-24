<?php
namespace JoeyDendron\FbaseAlerts;
use Kreait\Firebase\Factory, Kreait\Firebase\Database;

class Alerter {
    private static $instance = null;
    private $db;

    public static function instance($pathToFBCredentials, $dbUrl) {
        if(self::$instance == null) {
            $db = (new Factory)->withServiceAccount($pathToFBCredentials)->withDatabaseUri($dbUrl)->createDatabase();
            self::$instance = new Alerter($db);
        }
        return self::$instance;
    }

    private function __construct(Database $db) {
        $this->db = $db;
    }

    public function alert($subject, $body) {
        exit("Alert!");
    }
}
