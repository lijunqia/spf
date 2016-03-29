<?php
namespace spf;

use spf\Server\Server;

class Log
{
    use Logger;

    static function load_config()
    {
        return Server::getConfig('spf')['logger'];
    }
}