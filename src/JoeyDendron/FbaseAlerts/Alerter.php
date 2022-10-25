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

    public static function alertException($subject, Exception $e, $extraContent = []) {
        self::alertThrowable($subject, $e, $extraContent);
    }

    public static function alertThrowable($subject, Throwable $throwable, $extraContent = []) {
        $trace = array_slice($throwable->getTrace(), 1, 15, true);
        $traceSummary = [];

        foreach($trace as $i => $traceItem) {
            if(isset($traceItem["file"]) && isset($traceItem["line"])) $traceSummary[] = "#{$i}: {$traceItem["file"]} ({$traceItem["line"]})";
        }

        $content = [
            'message' => $throwable->getMessage(), 'file' => $throwable->getFile(), 'line' => $throwable->getLine(), 
            'trace' => implode("\n", $traceSummary), 'class' => get_class($throwable), 'get' => $_GET, 'post' => $_POST, 
            'body_input' => file_get_contents('php://input')
        ];
        if(is_array($extraContent) && !empty($extraContent)) $content = array_merge($content, $extraContent);

        self::alert($subject, $content);
    }

    public static function reset() {
        try {
            if(!$instance = self::instance()) return;
            $instance->alertsRef()->remove();
        }
        catch(Throwable $e) {
            return;//   Don't throw any internally-raised exceptions...
        }
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

    public static function deleteMany($n = 100) {
        self::instance()->deleteNAlerts($n);
    }

    public static function childElementCount($key) {
        return self::instance()->countOfChildElements($key);
    }

    private function __construct(Database $db) {
        $this->db = $db;
    }

    public function delete($key) {
        set_time_limit(20);
        $r = $this->db->getReference($key)->remove();
    }

    public function countOfChildElements($key) {
        set_time_limit(20);
        $numChildren = $this->db->getReference($key)->shallow()->getSnapshot()->numChildren();
        return $numChildren;
    }

    public function deleteNAlerts($n) {
        $keys = $this->db->getReference('alerts')->shallow()->limitToFirst($n)->getSnapshot()->getValue();

        foreach($keys as $key => $val) {
            $this->delete("alerts/{$key}");
        }
    }

    public function alertsRef() {
        return $this->db->getReference('alerts');
    }
}
