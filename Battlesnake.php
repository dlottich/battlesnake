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
            'author' => 'dlottich',
            'color' => '#000080',
            'head' => 'evil',
            'tail' => 'mlh-gene',
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

    private static function cellKey(int $x, int $y): string
    {
        return "{$x},{$y}";
    }

    private static function isCellSafe(
        int $x,
        int $y,
        int $boardWidth,
        int $boardHeight,
        array $occupiedKeys,
    ): bool {
        if ($x < 0 || $y < 0 || $x >= $boardWidth || $y >= $boardHeight) {
            return false;
        }

        return !isset($occupiedKeys[self::cellKey($x, $y)]);
    }

    /** @return array<string, true> */
    private static function buildOccupiedKeys(array $myBody, array $snakes, ?string $myId): array
    {
        $keys = [];

        foreach (array_slice($myBody, 1) as $segment) {
            $keys[self::cellKey($segment['x'], $segment['y'])] = true;
        }

        foreach ($snakes as $snake) {
            if ($myId !== null && $snake['id'] === $myId) {
                continue;
            }

            foreach ($snake['body'] as $segment) {
                $keys[self::cellKey($segment['x'], $segment['y'])] = true;
            }
        }

        return $keys;
    }

    /**
     * Filter to the best space-aware moves.
     * Prefers moves where all adjacent cells from the landing spot are also safe.
     *
     * @return list<string>
     */
    public static function getNextMoveBasedonOneSpaceAway(
        array $candidateMoves,
        array $myHead,
        array $moveOffsets,
        int $boardWidth,
        int $boardHeight,
        array $occupiedKeys,
        int $health,
    ): array {
        //if the snake is low on health, don't worry about being space aware.
        if ($health < 12) {
            return $candidateMoves;
        }

        $allNeighborsSafe = [];
        $neighborCounts = [];

        foreach ($candidateMoves as $move) {
            $offset = $moveOffsets[$move];
            $nextHead = [
                'x' => $myHead['x'] + $offset['x'],
                'y' => $myHead['y'] + $offset['y'],
            ];

            $safeNeighborCount = 0;
            $everyNeighborSafe = true;

            foreach ($moveOffsets as $neighborOffset) {
                $neighbor = [
                    'x' => $nextHead['x'] + $neighborOffset['x'],
                    'y' => $nextHead['y'] + $neighborOffset['y'],
                ];

                if (self::isCellSafe(
                    $neighbor['x'],
                    $neighbor['y'],
                    $boardWidth,
                    $boardHeight,
                    $occupiedKeys,
                )) {
                    $safeNeighborCount++;
                } else {
                    $everyNeighborSafe = false;
                }
            }

            if ($everyNeighborSafe) {
                $allNeighborsSafe[] = $move;
            }

            $neighborCounts[$move] = $safeNeighborCount;
        }

        if ($allNeighborsSafe !== []) {
            return $allNeighborsSafe;
        }

        $bestCount = -1;
        $bestMoves = [];

        foreach ($candidateMoves as $move) {
            $count = $neighborCounts[$move];

            if ($count > $bestCount) {
                $bestCount = $count;
                $bestMoves = [$move];
            } elseif ($count === $bestCount) {
                $bestMoves[] = $move;
            }
        }

        return $bestMoves;
    }

    /** @return list<string> */
    private static function movesTowardPoint(
        array $candidateMoves,
        array $myHead,
        array $moveOffsets,
        int $targetX,
        int $targetY,
    ): array {
        $bestMoves = [];
        $bestDistance = PHP_INT_MAX;

        foreach ($candidateMoves as $move) {
            $offset = $moveOffsets[$move];
            $nextHead = [
                'x' => $myHead['x'] + $offset['x'],
                'y' => $myHead['y'] + $offset['y'],
            ];
            $distance = abs($nextHead['x'] - $targetX) + abs($nextHead['y'] - $targetY);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMoves = [$move];
            } elseif ($distance === $bestDistance) {
                $bestMoves[] = $move;
            }
        }

        return $bestMoves;
    }

    /**
     * @param list<array{x: int, y: int}> $food
     * @return list<array{x: int, y: int}>
     */
    private static function filterEdgeFood(array $food, int $boardWidth, int $boardHeight): array
    {
        $interiorFood = array_values(array_filter(
            $food,
            static fn(array $item): bool => $item['x'] > 0
                && $item['x'] < $boardWidth - 1
                && $item['y'] > 0
                && $item['y'] < $boardHeight - 1,
        ));

        return $interiorFood !== [] ? $interiorFood : $food;
    }

    /** @param list<array{x: int, y: int}> $opponentHeads */
    private static function isCloserToAnyOpponentHeadThan(
        int $x,
        int $y,
        array $myHead,
        array $opponentHeads,
    ): bool {
        foreach ($opponentHeads as $head) {
            $distanceFromCell = abs($x - $head['x']) + abs($y - $head['y']);
            $distanceFromMe = abs($myHead['x'] - $head['x']) + abs($myHead['y'] - $head['y']);

            if ($distanceFromCell < $distanceFromMe) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{x: int, y: int}> $opponentHeads
     * @return ?array{x: int, y: int}
     */
    private static function findFarthestPointFromOpponentHeads(
        int $boardWidth,
        int $boardHeight,
        array $opponentHeads,
        bool $avoidMovingTowardHeads,
        ?array $myHead = null,
    ): ?array {
        $bestPoint = null;
        $bestMinDistance = -1;

        for ($y = 0; $y < $boardHeight; $y++) {
            for ($x = 0; $x < $boardWidth; $x++) {
                if (
                    $avoidMovingTowardHeads
                    && $myHead !== null
                    && self::isCloserToAnyOpponentHeadThan($x, $y, $myHead, $opponentHeads)
                ) {
                    continue;
                }

                $minDistance = PHP_INT_MAX;

                foreach ($opponentHeads as $head) {
                    $distance = abs($x - $head['x']) + abs($y - $head['y']);
                    $minDistance = min($minDistance, $distance);
                }

                if ($minDistance > $bestMinDistance) {
                    $bestMinDistance = $minDistance;
                    $bestPoint = ['x' => $x, 'y' => $y];
                }
            }
        }

        return $bestPoint;
    }

    /** @return array{x: int, y: int} */
    private static function getFarthestPointFromOpponentHeads(
        int $boardWidth,
        int $boardHeight,
        array $snakes,
        ?string $myId,
        array $myHead,
    ): array {
        $opponentHeads = [];

        foreach ($snakes as $snake) {
            if ($myId !== null && $snake['id'] === $myId) {
                continue;
            }

            $opponentHeads[] = $snake['body'][0];
        }

        if ($opponentHeads === []) {
            return [
                'x' => intdiv($boardWidth - 1, 2),
                'y' => intdiv($boardHeight - 1, 2),
            ];
        }

        $bestPoint = self::findFarthestPointFromOpponentHeads(
            $boardWidth,
            $boardHeight,
            $opponentHeads,
            true,
            $myHead,
        );

        if ($bestPoint === null) {
            $bestPoint = self::findFarthestPointFromOpponentHeads(
                $boardWidth,
                $boardHeight,
                $opponentHeads,
                false,
            );
        }

        return $bestPoint ?? [
            'x' => intdiv($boardWidth - 1, 2),
            'y' => intdiv($boardHeight - 1, 2),
        ];
    }

    private static function hasMoreThanTwoHealthAdvantageOverAllOpponents(
        int $myHealth,
        array $snakes,
        ?string $myId,
    ): bool {
        $hasMoreThanTwoHealthAdvantage = true;
        foreach ($snakes as $snake) {
            if ($myId !== null && $snake['id'] === $myId) {
                continue;
            }

            if ($myHealth - ($snake['health'] ?? 100) < 2) {
                $hasMoreThanTwoHealthAdvantage = false;
            }
        }

        return $hasMoreThanTwoHealthAdvantage;
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

        $boardWidth = $gameState['board']['width'];
        $boardHeight = $gameState['board']['height'];

        if ($myHead['y'] >= $boardHeight - 1) {
            $isMoveSafe['up'] = false;
        }
        if ($myHead['y'] <= 0) {
            $isMoveSafe['down'] = false;
        }
        if ($myHead['x'] <= 0) {
            $isMoveSafe['left'] = false;
        }
        if ($myHead['x'] >= $boardWidth - 1) {
            $isMoveSafe['right'] = false;
        }

        $myBody = $gameState['you']['body'];
        if (($gameState['you']['health'] ?? 100) < 100) {
            $myBody = array_slice($myBody, 0, -1);
        }

        $moveOffsets = [
            'up' => ['x' => 0, 'y' => 1],
            'down' => ['x' => 0, 'y' => -1],
            'left' => ['x' => -1, 'y' => 0],
            'right' => ['x' => 1, 'y' => 0],
        ];

        foreach ($moveOffsets as $move => $offset) {
            if (!$isMoveSafe[$move]) {
                continue;
            }

            $nextHead = [
                'x' => $myHead['x'] + $offset['x'],
                'y' => $myHead['y'] + $offset['y'],
            ];

            foreach ($myBody as $segment) {
                if ($nextHead['x'] === $segment['x'] && $nextHead['y'] === $segment['y']) {
                    $isMoveSafe[$move] = false;
                    break;
                }
            }
        }

        $myId = $gameState['you']['id'] ?? null;
        foreach ($gameState['board']['snakes'] as $snake) {
            if ($myId !== null && $snake['id'] === $myId) {
                continue;
            }

            foreach ($moveOffsets as $move => $offset) {
                if (!$isMoveSafe[$move]) {
                    continue;
                }

                $nextHead = [
                    'x' => $myHead['x'] + $offset['x'],
                    'y' => $myHead['y'] + $offset['y'],
                ];

                foreach ($snake['body'] as $segment) {
                    if ($nextHead['x'] === $segment['x'] && $nextHead['y'] === $segment['y']) {
                        $isMoveSafe[$move] = false;
                        break;
                    }
                }
            }
        }

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

        $occupiedKeys = self::buildOccupiedKeys($myBody, $gameState['board']['snakes'], $myId);
        $food = self::filterEdgeFood($gameState['board']['food'], $boardWidth, $boardHeight);
        $health = $gameState['you']['health'] ?? 100;

        $spaceAwareMoves = self::getNextMoveBasedonOneSpaceAway(
            $safeMoves,
            $myHead,
            $moveOffsets,
            $boardWidth,
            $boardHeight,
            $occupiedKeys,
            $health,
        );

        $shouldTargetFood = $food !== []
            && !self::hasMoreThanTwoHealthAdvantageOverAllOpponents(
                $health,
                $gameState['board']['snakes'],
                $myId,
            );

        if (!$shouldTargetFood) {
            $targetPoint = self::getFarthestPointFromOpponentHeads(
                $boardWidth,
                $boardHeight,
                $gameState['board']['snakes'],
                $myId,
                $myHead,
            );
            $candidateMoves = self::movesTowardPoint(
                $spaceAwareMoves,
                $myHead,
                $moveOffsets,
                $targetPoint['x'],
                $targetPoint['y'],
            );
        } else {
            $closestFood = $food[0];
            $closestDistance = abs($myHead['x'] - $closestFood['x']) + abs($myHead['y'] - $closestFood['y']);

            foreach (array_slice($food, 1) as $item) {
                $distance = abs($myHead['x'] - $item['x']) + abs($myHead['y'] - $item['y']);
                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closestFood = $item;
                }
            }

            $candidateMoves = self::movesTowardPoint(
                $spaceAwareMoves,
                $myHead,
                $moveOffsets,
                $closestFood['x'],
                $closestFood['y'],
            );
        }

        $nextMove = $candidateMoves[array_rand($candidateMoves)];

        $turn = $gameState['turn'] ?? '?';
        error_log("MOVE {$turn}: {$nextMove}");

        return ['move' => $nextMove];
    }
}
