<?php

// Конфигурация генерации
$startDate = '2024-01-01';
$endDate = '2024-01-31';
$playersPerDay = 5;  // Сколько новых игроков регистрируется каждый день
$minEventsPerSession = 3;  // Минимум событий в первой сессии
$maxEventsPerSession = 10; // Максимум событий в первой сессии
$sessionLengthTrend = [   // Тренд средней длины сессии по дням недели
    1 => 25,  // Понедельник: 25 минут
    2 => 30,  // Вторник: 30 минут
    3 => 35,  // Среда: 35 минут
    4 => 40,  // Четверг: 40 минут
    5 => 45,  // Пятница: 45 минут
    6 => 60,  // Суббота: 60 минут
    7 => 55,  // Воскресенье: 55 минут
];

try {
    // Database connection
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Очищаем таблицу
    $pdo->exec('TRUNCATE TABLE game_events');

    // Генерируем данные для каждого дня
    $currentDate = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    $playerId = 1;

    while ($currentDate <= $endDateTime) {
        $dateStr = $currentDate->format('Y-m-d');
        $dayOfWeek = (int)$currentDate->format('N'); // 1 (понедельник) до 7 (воскресенье)
        $avgSessionLength = $sessionLengthTrend[$dayOfWeek];

        // Генерируем игроков для текущего дня
        for ($i = 0; $i < $playersPerDay; $i++) {
            // Случайное время регистрации в течение дня
            $hourOfDay = rand(8, 22); // Регистрации с 8:00 до 22:00
            $minuteOfHour = rand(0, 59);
            $sessionStart = clone $currentDate;
            $sessionStart->setTime($hourOfDay, $minuteOfHour);
            
            // Генерируем события для первой сессии
            $numEvents = rand($minEventsPerSession, $maxEventsPerSession);
            
            // Добавляем случайное отклонение к средней длине сессии (-5/+5 минут)
            $sessionLength = $avgSessionLength + rand(-5, 5);
            $timeStep = $sessionLength / ($numEvents - 1);

            $stmt = $pdo->prepare("
                INSERT INTO game_events (player_id, event_time, registration_date)
                VALUES (?, ?, ?)
            ");

            for ($j = 0; $j < $numEvents; $j++) {
                $eventTime = clone $sessionStart;
                if ($j > 0) {
                    // Добавляем небольшую случайность к интервалам между событиями
                    $minutesToAdd = round($j * $timeStep + rand(-1, 1));
                    $eventTime->modify("+{$minutesToAdd} minutes");
                }

                $stmt->execute([
                    $playerId,
                    $eventTime->format('Y-m-d H:i:s'),
                    $dateStr
                ]);
            }

            // Иногда добавляем вторую сессию (30% случаев)
            if (rand(1, 100) <= 30) {
                $secondSessionStart = clone $sessionStart;
                $secondSessionStart->modify('+2 hours'); // Через 2 часа после первой сессии
                
                $numEvents = rand(2, 5);
                for ($j = 0; $j < $numEvents; $j++) {
                    $eventTime = clone $secondSessionStart;
                    if ($j > 0) {
                        $minutesToAdd = $j * 5 + rand(-1, 1); // События каждые 5 минут ±1 минута
                        $eventTime->modify("+{$minutesToAdd} minutes");
                    }

                    $stmt->execute([
                        $playerId,
                        $eventTime->format('Y-m-d H:i:s'),
                        $dateStr
                    ]);
                }
            }

            $playerId++;
        }

        $currentDate->modify('+1 day');
    }

    echo "Successfully generated data for " . ($playerId - 1) . " players\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
