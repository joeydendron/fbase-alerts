<?php
namespace JoeyDendron\FbaseAlerts;
use Throwable, Exception, Kreait\Firebase\Factory, Kreait\Firebase\Database;

class Alerter {
    private static $instance = null;
    private $db;

    public static function instance($pathToFBCredentials, $dbUri) {
        if(self::$instance == null) {
            $db = (new Factory)->withServiceAccount($pathToFBCredentials)->withDatabaseUri($dbUri)->createDatabase();
            self::$instance = new Alerter($db);
        }
        return self::$instance;
    }

    private function __construct(Database $db) {
        $this->db = $db;
    }

    public function alert($subject, $body) {
        $params = [
            'subject' => $this->prependHostName($subject),
            'body' => $this->toString($body),
            'created_at' => mktime()
        ];

        $this->alertsRef()->push($params);
    }

    public function alertException($subject, Exception $e) {
        $this->alertThrowable($subject, $e);
    }

    public function alertThrowable($subject, Throwable $e) {
        $trace = array_slice($e->getTrace(), 1, 15, true);
        $traceSummary = [];
        $i = 0;
        foreach($trace as $traceItem) {
            if(isset($traceItem["file"]) && isset($traceItem["line"])) $traceSummary[] = "#{$i}: {$traceItem["file"]} ({$traceItem["line"]})";
            $i++;
        }

        $data = [
            'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 
            'trace' => implode("\n", $traceSummary), 
            'get' => $_GET, 'post' => $_POST, 'body_input' => file_get_contents('php://input')
        ];

        $this->alert($subject, $data, true);
    }

    private function prependHostName($subject) {
        $hostName = gethostname();
        return ($hostName ? $hostName : '') . ' ' . $subject;
    }

    private function toString($body) {
        return is_string($body) ? trim($body) : print_r($body, true);
    }

    private function alertsRef() {
        return $this->db->getReference('alerts');
    }
}
