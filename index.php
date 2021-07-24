<?php
require(__DIR__.'/vendor/autoload.php');

use JoeyDendron\FbaseAlerts\Alerter;

(new Alerter)->alert("Subject", "Body");
