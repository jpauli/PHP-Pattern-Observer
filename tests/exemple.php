<?php
$errorHandler = Observer\ErrorHandler::getInstance();

$errorHandler->attach(new Observer\Listeners\File(__DIR__ . '/log.txt'),
                      new Observer\Listeners\Db(new PDO('sqlite:'.realpath('./errordb.sq3')), 'error', 'nom'),
                      new Observer\Listeners\Mail(new Observer\Listeners\Mail\Adapter\Mock('foo@foo.com')),
                      $mock = new Observer\Listeners\Mock())
             ->attachFile('php://output');

$errorHandler->start();

//iterating over Observer objects
foreach ($errorHandler as $writer) {
    printf("%s \n", $writer);
}

// Generating a PHP error
echo $arr[0];

// Displaying mock observer error
echo "<strong>$mock</strong>";

$errorHandler->stop();