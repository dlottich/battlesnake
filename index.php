<?php

declare(strict_types=1);

require_once __DIR__ . '/Battlesnake.php';

header('Server: battlesnake/github/starter-snake-php');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'GET' && $path === '/') {
    header('Content-Type: application/json');
    echo json_encode(Battlesnake::info(), JSON_THROW_ON_ERROR);
    return;
}

$body = file_get_contents('php://input');
$gameState = $body !== false && $body !== '' ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : [];

if ($method === 'POST' && $path === '/start') {
    Battlesnake::start($gameState);
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return;
}

if ($method === 'POST' && $path === '/move') {
    header('Content-Type: application/json');
    echo json_encode(Battlesnake::move($gameState), JSON_THROW_ON_ERROR);
    return;
}

if ($method === 'POST' && $path === '/end') {
    Battlesnake::end($gameState);
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR);
