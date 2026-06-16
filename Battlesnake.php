<?php

declare(strict_types=1);

/**
 * Battlesnake game logic.
 * See https://docs.battlesnake.com/quickstart and https://docs.battlesnake.com/api/webhooks
 */
final class Battlesnake
{
    private const MOVE_OFFSETS = [
        'up' => ['x' => 0, 'y' => 1],
        'down' => ['x' => 0, 'y' => -1],
        'left' => ['x' => -1, 'y' => 0],
        'right' => ['x' => 1, 'y' => 0],
    ];

    private const LOW_HEALTH_THRESHOLD = 12;
    private const HEALTH_ADVANTAGE_THRESHOLD = 2;

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

    /**
     * Called every turn (POST /move).
     * Valid moves: up, down, left, or right.
     */
    public static function move(array $gameState): array
    {
        $boardWidth = $gameState['board']['width'];
        $boardHeight = $gameState['board']['height'];
        $myId = $gameState['you']['id'] ?? null;
        $myHead = $gameState['you']['body'][0];
        $health = $gameState['you']['health'] ?? 100;
        $myBody = self::bodyForCollisionCheck($gameState['you']['body'], $health);
        $snakes = $gameState['board']['snakes'];
        $food = self::filterEdgeFood($gameState['board']['food'], $boardWidth, $boardHeight);

        $safeMoves = self::findSafeMoves(
            $myHead,
            $myBody,
            $snakes,
            $myId,
            $boardWidth,
            $boardHeight,
        );

        if ($safeMoves === []) {
            $turn = $gameState['turn'] ?? '?';
            error_log("MOVE {$turn}: No safe moves detected! Moving down");

            return ['move' => 'down'];
        }

        $occupiedKeys = self::buildOccupiedKeys($myBody, $snakes, $myId);
        $spaceAwareMoves = self::filterSpaceAwareMoves(
            $safeMoves,
            $myHead,
            $boardWidth,
            $boardHeight,
            $occupiedKeys,
            $health,
        );

        $target = self::shouldTargetFood($food, $health, $snakes, $myId)
            ? self::findClosestPoint($myHead, $food)
            : self::getRetreatPoint($boardWidth, $boardHeight, $snakes, $myId, $myHead);

        $candidateMoves = self::movesMinimizingDistanceTo(
            $spaceAwareMoves,
            $myHead,
            $target['x'],
            $target['y'],
        );
        $nextMove = $candidateMoves[array_rand($candidateMoves)];

        $turn = $gameState['turn'] ?? '?';
        error_log("MOVE {$turn}: {$nextMove}");

        return ['move' => $nextMove];
    }

    /** @param list<array{x: int, y: int}> $body */
    private static function bodyForCollisionCheck(array $body, int $health): array
    {
        if ($health < 100) {
            return array_slice($body, 0, -1);
        }

        return $body;
    }

    /** @return list<string> */
    private static function findSafeMoves(
        array $myHead,
        array $myBody,
        array $snakes,
        ?string $myId,
        int $boardWidth,
        int $boardHeight,
    ): array {
        $isMoveSafe = array_fill_keys(array_keys(self::MOVE_OFFSETS), true);

        self::blockReverseMove($isMoveSafe, $myHead, $myBody[1]);
        self::blockOutOfBoundsMoves($isMoveSafe, $myHead, $boardWidth, $boardHeight);
        self::blockMovesIntoSegments($isMoveSafe, $myHead, $myBody);

        foreach (self::opponentSnakes($snakes, $myId) as $snake) {
            self::blockMovesIntoSegments($isMoveSafe, $myHead, $snake['body']);
        }

        return self::movesMarkedSafe($isMoveSafe);
    }

    /** @param array<string, bool> $isMoveSafe */
    private static function blockReverseMove(array &$isMoveSafe, array $head, array $neck): void
    {
        if ($neck['x'] < $head['x']) {
            $isMoveSafe['left'] = false;
        } elseif ($neck['x'] > $head['x']) {
            $isMoveSafe['right'] = false;
        } elseif ($neck['y'] < $head['y']) {
            $isMoveSafe['down'] = false;
        } elseif ($neck['y'] > $head['y']) {
            $isMoveSafe['up'] = false;
        }
    }

    /** @param array<string, bool> $isMoveSafe */
    private static function blockOutOfBoundsMoves(
        array &$isMoveSafe,
        array $head,
        int $boardWidth,
        int $boardHeight,
    ): void {
        if ($head['y'] >= $boardHeight - 1) {
            $isMoveSafe['up'] = false;
        }
        if ($head['y'] <= 0) {
            $isMoveSafe['down'] = false;
        }
        if ($head['x'] <= 0) {
            $isMoveSafe['left'] = false;
        }
        if ($head['x'] >= $boardWidth - 1) {
            $isMoveSafe['right'] = false;
        }
    }

    /**
     * @param array<string, bool> $isMoveSafe
     * @param list<array{x: int, y: int}> $segments
     */
    private static function blockMovesIntoSegments(array &$isMoveSafe, array $head, array $segments): void
    {
        foreach (self::MOVE_OFFSETS as $move => $offset) {
            if (!$isMoveSafe[$move]) {
                continue;
            }

            $nextHead = self::applyOffset($head, $offset);

            foreach ($segments as $segment) {
                if (self::isSameCell($nextHead, $segment)) {
                    $isMoveSafe[$move] = false;
                    break;
                }
            }
        }
    }

    /** @param array<string, bool> $isMoveSafe @return list<string> */
    private static function movesMarkedSafe(array $isMoveSafe): array
    {
        $safeMoves = [];

        foreach ($isMoveSafe as $move => $safe) {
            if ($safe) {
                $safeMoves[] = $move;
            }
        }

        return $safeMoves;
    }

    /**
     * Prefer moves where all adjacent cells from the landing spot are also safe.
     *
     * @param list<string> $candidateMoves
     * @return list<string>
     */
    private static function filterSpaceAwareMoves(
        array $candidateMoves,
        array $myHead,
        int $boardWidth,
        int $boardHeight,
        array $occupiedKeys,
        int $health,
    ): array {
        if ($health < self::LOW_HEALTH_THRESHOLD) {
            return $candidateMoves;
        }

        $allNeighborsSafe = [];
        $neighborCounts = [];

        foreach ($candidateMoves as $move) {
            $nextHead = self::applyOffset($myHead, self::MOVE_OFFSETS[$move]);
            $safeNeighborCount = 0;
            $everyNeighborSafe = true;

            foreach (self::MOVE_OFFSETS as $neighborOffset) {
                $neighbor = self::applyOffset($nextHead, $neighborOffset);

                if (self::isCellSafe($neighbor['x'], $neighbor['y'], $boardWidth, $boardHeight, $occupiedKeys)) {
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

        return self::movesWithBestScore($candidateMoves, $neighborCounts);
    }

    /**
     * @param list<string> $candidateMoves
     * @return list<string>
     */
    private static function movesMinimizingDistanceTo(
        array $candidateMoves,
        array $from,
        int $targetX,
        int $targetY,
    ): array {
        $scores = [];

        foreach ($candidateMoves as $move) {
            $nextHead = self::applyOffset($from, self::MOVE_OFFSETS[$move]);
            $scores[$move] = self::manhattanDistance($nextHead, ['x' => $targetX, 'y' => $targetY]);
        }

        return self::movesWithBestScore($candidateMoves, $scores, lowerIsBetter: true);
    }

    /**
     * @param list<array{x: int, y: int}> $points
     * @return array{x: int, y: int}
     */
    private static function findClosestPoint(array $from, array $points): array
    {
        $closest = $points[0];
        $closestDistance = self::manhattanDistance($from, $closest);

        foreach (array_slice($points, 1) as $point) {
            $distance = self::manhattanDistance($from, $point);

            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $point;
            }
        }

        return $closest;
    }

    private static function shouldTargetFood(array $food, int $health, array $snakes, ?string $myId): bool
    {
        return $food !== []
            && !self::beatsEveryOpponentByAtLeast($health, $snakes, $myId, self::HEALTH_ADVANTAGE_THRESHOLD);
    }

    private static function beatsEveryOpponentByAtLeast(
        int $myHealth,
        array $snakes,
        ?string $myId,
        int $minimumAdvantage,
    ): bool {
        foreach (self::opponentSnakes($snakes, $myId) as $snake) {
            if ($myHealth - ($snake['health'] ?? 100) < $minimumAdvantage) {
                return false;
            }
        }

        return true;
    }

    /** @return array{x: int, y: int} */
    private static function getRetreatPoint(
        int $boardWidth,
        int $boardHeight,
        array $snakes,
        ?string $myId,
        array $myHead,
    ): array {
        $opponentHeads = array_map(
            static fn(array $snake): array => $snake['body'][0],
            self::opponentSnakes($snakes, $myId),
        );

        if ($opponentHeads === []) {
            return self::boardCenter($boardWidth, $boardHeight);
        }

        $bestPoint = self::findFarthestPointFromOpponentHeads(
            $boardWidth,
            $boardHeight,
            $opponentHeads,
            avoidMovingTowardHeads: true,
            myHead: $myHead,
        );

        if ($bestPoint === null) {
            $bestPoint = self::findFarthestPointFromOpponentHeads(
                $boardWidth,
                $boardHeight,
                $opponentHeads,
                avoidMovingTowardHeads: false,
            );
        }

        return $bestPoint ?? self::boardCenter($boardWidth, $boardHeight);
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
                    $minDistance = min($minDistance, self::manhattanDistance(['x' => $x, 'y' => $y], $head));
                }

                if ($minDistance > $bestMinDistance) {
                    $bestMinDistance = $minDistance;
                    $bestPoint = ['x' => $x, 'y' => $y];
                }
            }
        }

        return $bestPoint;
    }

    /** @param list<array{x: int, y: int}> $opponentHeads */
    private static function isCloserToAnyOpponentHeadThan(
        int $x,
        int $y,
        array $myHead,
        array $opponentHeads,
    ): bool {
        $cell = ['x' => $x, 'y' => $y];

        foreach ($opponentHeads as $head) {
            if (self::manhattanDistance($cell, $head) < self::manhattanDistance($myHead, $head)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, true> */
    private static function buildOccupiedKeys(array $myBody, array $snakes, ?string $myId): array
    {
        $keys = [];

        foreach (array_slice($myBody, 1) as $segment) {
            $keys[self::cellKey($segment['x'], $segment['y'])] = true;
        }

        foreach (self::opponentSnakes($snakes, $myId) as $snake) {
            foreach ($snake['body'] as $segment) {
                $keys[self::cellKey($segment['x'], $segment['y'])] = true;
            }
        }

        return $keys;
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

    /** @return list<array> */
    private static function opponentSnakes(array $snakes, ?string $myId): array
    {
        $opponents = [];

        foreach ($snakes as $snake) {
            if ($myId !== null && $snake['id'] === $myId) {
                continue;
            }

            $opponents[] = $snake;
        }

        return $opponents;
    }

    /**
     * @param list<string> $candidateMoves
     * @param array<string, int> $scores
     * @return list<string>
     */
    private static function movesWithBestScore(
        array $candidateMoves,
        array $scores,
        bool $lowerIsBetter = false,
    ): array {
        $bestScore = $lowerIsBetter ? PHP_INT_MAX : -1;
        $bestMoves = [];

        foreach ($candidateMoves as $move) {
            $score = $scores[$move];

            if ($lowerIsBetter ? $score < $bestScore : $score > $bestScore) {
                $bestScore = $score;
                $bestMoves = [$move];
            } elseif ($score === $bestScore) {
                $bestMoves[] = $move;
            }
        }

        return $bestMoves;
    }

    /** @return array{x: int, y: int} */
    private static function boardCenter(int $boardWidth, int $boardHeight): array
    {
        return [
            'x' => intdiv($boardWidth - 1, 2),
            'y' => intdiv($boardHeight - 1, 2),
        ];
    }

    /** @param array{x: int, y: int} $offset */
    private static function applyOffset(array $cell, array $offset): array
    {
        return [
            'x' => $cell['x'] + $offset['x'],
            'y' => $cell['y'] + $offset['y'],
        ];
    }

    private static function isSameCell(array $a, array $b): bool
    {
        return $a['x'] === $b['x'] && $a['y'] === $b['y'];
    }

    private static function manhattanDistance(array $a, array $b): int
    {
        return abs($a['x'] - $b['x']) + abs($a['y'] - $b['y']);
    }

    private static function cellKey(int $x, int $y): string
    {
        return "{$x},{$y}";
    }
}
