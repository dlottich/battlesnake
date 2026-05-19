<?php

/**
 * Implements Battlesnake API response functions.
 *
 * @see https://docs.battlesnake.com/references/api
 */

/**
 * @param string $apiversion Must be the apiversion use by the snake
 * @param string $author     Must be empty or contain the username of the snake' author
 * @param string $color      Must be a hexadecimal color string, preceded with a hash symbol, e.g. "#ff00ff"
 * @param string $headType   Head type string, see API docs for accepted values
 * @param string $tailType   Tail type string, see API docs for accepted values
 */
function indexResponse(string $apiversion , string $author, string $color, string $headType , string $tailType) : void
{
    outputJsonResponse(['apiversion' => $apiversion, 'author' => $author, 'color' => $color, 'head' => $headType, 'tail' => $tailType]);
}

/**
 * Outputs a move response to the Battlesnake game engine.
 *
 * @param string $move Must be one of 'up', 'down', 'left', 'right', as per Battlesnake API
 *
 * @throws Exception
 */
function moveResponse(string $move) : void
{
    if (! in_array($move, ['up', 'down', 'left', 'right']))
    {
        throw new \Exception('Move must be one of [up, down, left, right]');
    }

    outputJsonResponse(['move' => $move]);
}

/**
 * Responses to requests to the /start endpoint are ignored by the Battlesnake engine, so this function outputs nothing.
 */
function startResponse() : void
{
    echo '';
}


/**
 * Responses to requests to the /end endpoint are ignored by the Battlesnake engine, so this function outputs nothing.
 */
function endResponse() : void
{
    echo '';
}


/**
 * Utility function to output an array of response data as JSON.
 *
 * @param array $responseData Array of data to be cast to an object and encoded to JSON
 */
function outputJsonResponse(array $responseData) : void
{
    $body = (object) $responseData;
    header('Content-Type: application/json');
    echo json_encode($body);
}
