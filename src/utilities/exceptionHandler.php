<?php


namespace PluginMaster\DB\utilities;


trait exceptionHandler
{

    public function exceptionHandler()
    {
        http_response_code(400);
        echo json_encode([
            "message" => 'Unable to process query',
            "errors" => $this->connection->last_error
        ]);
        exit;
    }

     

}
