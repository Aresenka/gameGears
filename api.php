<?php
header('Content-Type: application/json');

try {
    // Database connection
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to calculate average first session length by registration date
    $query = "
        -- Step 1: Identify session boundaries
        -- For each event, determine if it starts a new session:
        -- - First event of a player always starts a new session
        -- - If gap from previous event > 10 minutes, it starts a new session
        WITH SessionBoundaries AS (
            SELECT 
                player_id,
                event_time,
                registration_date,
                -- Mark session starts (1 = new session, 0 = continue previous session)
                -- A new session starts when:
                -- a) It's the player's first event (previous event is NULL)
                -- b) There's been more than 10 minutes since last event
                CASE WHEN 
                    TIMESTAMPDIFF(
                        MINUTE,
                        LAG(event_time) OVER (PARTITION BY player_id ORDER BY event_time),
                        event_time
                    ) > 10 OR
                    LAG(event_time) OVER (PARTITION BY player_id ORDER BY event_time) IS NULL
                THEN 1 ELSE 0 END as is_session_start,
                -- Number each event for each player (1-based)
                ROW_NUMBER() OVER (PARTITION BY player_id ORDER BY event_time) as event_number
            FROM game_events
        ),

        -- Step 2: Group events into sessions
        -- Use running sum of is_session_start to assign session numbers
        -- Events with the same session_number belong to the same session
        FirstSessionEvents AS (
            SELECT 
                sb.*,
                -- Running sum creates a session identifier
                -- Every time is_session_start = 1, we start a new session number
                SUM(is_session_start) OVER (
                    PARTITION BY player_id 
                    ORDER BY event_time
                    ROWS UNBOUNDED PRECEDING
                ) as session_number
            FROM SessionBoundaries sb
        ),

        -- Step 3: Calculate first session duration for each player
        -- Get the start and end time of each player's first session
        FirstSessions AS (
            SELECT 
                player_id,
                registration_date,
                -- First event of the session
                MIN(event_time) as session_start,
                -- Last event of the session
                MAX(event_time) as session_end
            FROM FirstSessionEvents
            -- session_number = 1 means it's the player's first session
            WHERE session_number = 1
            GROUP BY player_id, registration_date
        )

        -- Step 4: Calculate average session length by registration date
        -- For each registration date:
        -- 1. Calculate session length for each player
        -- 2. Average these lengths to get the final result
        SELECT 
            registration_date,
            -- Calculate average session length:
            -- 1. TIMESTAMPDIFF gets minutes between start and end
            -- 2. AVG calculates the average across all players
            -- 3. ROUND to 2 decimal places for cleaner output
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, session_start, session_end)), 2) as avg_session_length
        FROM FirstSessions
        GROUP BY registration_date
        ORDER BY registration_date;
    ";

    $stmt = $pdo->query($query);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
