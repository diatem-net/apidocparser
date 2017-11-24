<?php

use Diatem\ApiDocParser\ApiDocParserRender;

//Configuration de base
error_reporting(E_ALL);
ini_set("display_errors", 1);
require '../vendor/autoload.php';

ApiDocParserRender::init('relpath/tofoldertoanalyse/', 'http://urldelaracinedesservicesrest/', 'nomUser', 'cleUser', 'relpath/toapidefinefile.php', array('filestoexclude.php'));