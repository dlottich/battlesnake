<?php

declare(strict_types=1);

/**
 * Battlesnake game logic.
 * See https://docs.battlesnake.com/quickstart and https://docs.battlesnake.com/api/webhooks
 */
final class Battlesnake
{
    /** Called when you register your Battlesnake (GET /). */
    public static function info(): array
    {
        error_log('INFO');

        return [
            'apiversion' => '1',
            'author' => '', // TODO: Your Battlesnake username
            'color' => '#888888', // TODO: Choose color (hex, e.g. #FF5733)
            'head' => 'default', // TODO: Choose head
            'tail' => 'default', // TODO: Choose tail
        ];
    }

    /** Called when a game starts (POST /start). */
    public static function start(array $gameState): void
    {
        error_log('GAME START');
    }

    /** Called when a game ends (POST /end). */
    public static function end(array $gameState): void
    {
        error_log("GAME OVER\n");
    }

    /**
     * Called every turn (POST /move).
     * Valid moves: up, down, left, right.
     */
    public static function move(array $gameState): array
    {
        $isMoveSafe = [
            'up' => true,
            'down' => true,
            'left' => true,
            'right' => true,
        ];

        $myHead = $gameState['you']['body'][0];
        $myNeck = $gameState['you']['body'][1];

        if ($myNeck['x'] < $myHead['x']) {
            $isMoveSafe['left'] = false;
        } elseif ($myNeck['x'] > $myHead['x']) {
            $isMoveSafe['right'] = false;
        } elseif ($myNeck['y'] < $myHead['y']) {
            $isMoveSafe['down'] = false;
        } elseif ($myNeck['y'] > $myHead['y']) {
            $isMoveSafe['up'] = false;
        }

        // TODO: Step 1 - Prevent moving out of bounds
        // $boardWidth = $gameState['board']['width'];
        // $boardHeight = $gameState['board']['height'];

        // TODO: Step 2 - Prevent colliding with yourself
        // $myBody = $gameState['you']['body'];

        // TODO: Step 3 - Prevent colliding with other Battlesnakes
        // $opponents = $gameState['board']['snakes'];

        $safeMoves = [];
        foreach ($isMoveSafe as $move => $safe) {
            if ($safe) {
                $safeMoves[] = $move;
            }
        }

        if ($safeMoves === []) {
            $turn = $gameState['turn'] ?? '?';
            error_log("MOVE {$turn}: No safe moves detected! Moving down");
            return ['move' => 'down'];
        }

        $nextMove = $safeMoves[array_rand($safeMoves)];

        // TODO: Step 4 - Move towards food instead of random
        // $food = $gameState['board']['food'];

        $turn = $gameState['turn'] ?? '?';
        error_log("MOVE {$turn}: {$nextMove}");

        return ['move' => $nextMove];
    }
}
