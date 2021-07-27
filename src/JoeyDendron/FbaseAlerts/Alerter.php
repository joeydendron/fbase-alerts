<?php
namespace JoeyDendron\FbaseAlerts;
use Throwable, Exception, Kreait\Firebase\Factory, Kreait\Firebase\Database;

class Alerter {
    private static $instance = null;
    private $db;

    public static function alert($subject, $body) {

        try {
            if(!$instance = self::instance()) return;
            $instance->alertsRef()->push([
                'subject' => ($hostName = gethostname()) ? "{$hostName} - {$subject}" : $subject,
                'body' => is_string($body) ? trim($body) : print_r($body, true), 'created_at' => time()
            ]);
        }
        catch(Throwable $e) {
            return;//   Don't throw any internally-raised exceptions...
        }
    }

    public static function alertException($subject, Exception $e) {
        self::alertThrowable($subject, $e);
    }

    public static function alertThrowable($subject, Throwable $throwable) {
        $trace = array_slice($throwable->getTrace(), 1, 15, true);
        $traceSummary = [];

        foreach($trace as $i => $traceItem) {
            if(isset($traceItem["file"]) && isset($traceItem["line"])) $traceSummary[] = "#{$i}: {$traceItem["file"]} ({$traceItem["line"]})";
        }

        self::alert($subject, [
            'message' => $throwable->getMessage(), 'file' => $throwable->getFile(), 'line' => $throwable->getLine(), 
            'trace' => implode("\n", $traceSummary), 
            'get' => $_GET, 'post' => $_POST, 'body_input' => file_get_contents('php://input')
        ]);
    }

    public static function instance() {
        if(!$pathToFBCredentials = getenv('JD_FIREBASE_PATH_TO_CREDENTIALS')) return false;
        if(!$dbUri = getenv('JD_FIREBASE_DB_URI')) return false;

        if(self::$instance == null) {
            $db = (new Factory)->withServiceAccount($pathToFBCredentials)->withDatabaseUri($dbUri)->createDatabase();
            self::$instance = new Alerter($db);
        }

        return self::$instance;
    }

    private function __construct(Database $db) {
        $this->db = $db;
    }

    private function alertsRef() {
        return $this->db->getReference('alerts');
    }
}
