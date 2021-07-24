<?php
namespace JoeyDendron\FbaseAlerts;
use Kreait\Firebase\Factory;

class Alerter {
    public static function instance(array $config) {
        return new Alerter($config);
    }

    public function __construct(array $config) {

    }

    public function alert($subject, $body) {
        exit("Alert!");
    }
}
