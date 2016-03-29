<?php
function exception_handler($exception) {
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
    return true;
}

set_exception_handler('exception_handler');

throw new Exception('test exception');
echo "Not Executed\n";