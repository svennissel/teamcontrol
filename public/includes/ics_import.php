<?php

function fetchIcsContent(string $url): string {
    $validatedUrl = filter_var($url, FILTER_VALIDATE_URL);
    if (!$validatedUrl) {
        throw new RuntimeException('Ungültige URL.');
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 12,
            'follow_location' => 1,
            'user_agent' => 'TeamControl/1.0 ICS Import'
        ]
    ]);

    $content = @file_get_contents($validatedUrl, false, $context);
    if ($content === false || trim($content) === '') {
        throw new RuntimeException('ICS-Datei konnte nicht geladen werden.');
    }

    return $content;
}

function parseIcsMatches(string $icsContent): array {
    $lines = preg_split('/\R/', $icsContent);
    if ($lines === false) {
        return [];
    }

    $unfolded = [];
    foreach ($lines as $line) {
        if (($line !== '') && (str_starts_with($line, ' ') || str_starts_with($line, "\t")) && !empty($unfolded)) {
            $unfolded[count($unfolded) - 1] .= substr($line, 1);
            continue;
        }
        $unfolded[] = $line;
    }

    $rawEvents = [];
    $current = null;
    foreach ($unfolded as $line) {
        if ($line === 'BEGIN:VEVENT') {
            $current = [];
            continue;
        }
        if ($line === 'END:VEVENT') {
            if (is_array($current)) {
                $rawEvents[] = $current;
            }
            $current = null;
            continue;
        }
        if ($current !== null && strpos($line, ':') !== false) {
            [$rawKey, $value] = explode(':', $line, 2);
            $key = strtoupper(trim(explode(';', $rawKey)[0]));
            $current[$key] = trim($value);
        }
    }

    $ownTeamName = detectOwnTeamNameFromEvents($rawEvents);

    $events = [];
    foreach ($rawEvents as $rawEvent) {
        $match = normalizeIcsEventToMatch($rawEvent, $ownTeamName);
        if ($match !== null) {
            $events[] = $match;
        }
    }

    return $events;
}

function detectOwnTeamNameFromEvents(array $events): string {
    $candidateTeams = null;
    $originalNames = [];

    foreach ($events as $event) {
        $summary = decodeIcsText($event['SUMMARY'] ?? '');
        $cleanSummary = trim(removeTrailingScoreFromSummary($summary));
        if ($cleanSummary === '') {
            continue;
        }

        $parts = splitTeamsFromSummary($cleanSummary);
        if ($parts === false || count($parts) !== 2) {
            continue;
        }

        $teamsInEvent = [];
        foreach ($parts as $part) {
            $team = trim($part);
            if ($team === '') {
                continue;
            }

            $normalized = normalizeTeamNameForComparison($team);
            if ($normalized === '') {
                continue;
            }

            $teamsInEvent[$normalized] = true;
            if (!isset($originalNames[$normalized])) {
                $originalNames[$normalized] = $team;
            }
        }

        if (count($teamsInEvent) !== 2) {
            continue;
        }

        if ($candidateTeams === null) {
            $candidateTeams = $teamsInEvent;
            continue;
        }

        $candidateTeams = array_intersect_key($candidateTeams, $teamsInEvent);
        if (count($candidateTeams) === 1) {
            break;
        }
    }

    if ($candidateTeams === null || count($candidateTeams) !== 1) {
        return '';
    }

    $ownTeamNormalized = array_key_first($candidateTeams);
    return $ownTeamNormalized !== null ? ($originalNames[$ownTeamNormalized] ?? '') : '';
}

function normalizeIcsEventToMatch(array $event, string $ownTeamName = ''): ?array {
    $start = parseIcsDateTime($event['DTSTART'] ?? '');
    if ($start === null) {
        return null;
    }

    $summary = decodeIcsText($event['SUMMARY'] ?? 'Importiertes Spiel');
    $location = decodeIcsText($event['LOCATION'] ?? '');

    $isHomeGame = isHomeGameFromSummary($summary, $ownTeamName);

    return [
        'match_date' => $start->format('Y-m-d'),
        'start_time' => $start->format('H:i'),
        'meeting_time' => '',
        'opponent' => mb_substr(extractOpponentFromSummary($summary, $ownTeamName), 0, 255),
        'is_home_game' => $isHomeGame,
        'location' => $isHomeGame ? '' : mb_substr($location, 0, 255)
    ];
}

function extractOpponentFromSummary(string $summary, string $ownTeamName = ''): string {
    $cleanSummary = trim(removeTrailingScoreFromSummary($summary));
    if ($cleanSummary === '') {
        return 'Importiertes Spiel';
    }

    $parts = splitTeamsFromSummary($cleanSummary);
    if ($parts === false || count($parts) !== 2) {
        return $cleanSummary;
    }

    $left = trim($parts[0]);
    $right = trim($parts[1]);
    if ($left === '' || $right === '') {
        return $cleanSummary;
    }

    $normalizedOwnTeam = normalizeTeamNameForComparison($ownTeamName);
    if ($normalizedOwnTeam !== '') {
        if (normalizeTeamNameForComparison($left) === $normalizedOwnTeam) {
            return $right;
        }
        if (normalizeTeamNameForComparison($right) === $normalizedOwnTeam) {
            return $left;
        }
    }

    return $cleanSummary;
}

function removeTrailingScoreFromSummary(string $summary): string {
    $withoutBracketScore = preg_replace('/\s*\[\d+\s*-\s*\d+\]\s*:\s*\[\d+\s*-\s*\d+\]\s*$/u', '', $summary);
    if ($withoutBracketScore === null) {
        return $summary;
    }

    return preg_replace('/\s+\d+\s*:\s*\d+\s*$/u', '', $withoutBracketScore) ?? $withoutBracketScore;
}

function normalizeTeamNameForComparison(string $teamName): string {
    $lower = mb_strtolower(trim($teamName));
    return preg_replace('/[^\p{L}\p{N}]+/u', '', $lower) ?? '';
}

function parseIcsDateTime(string $raw): ?DateTimeImmutable {
    $value = trim($raw);
    if ($value === '') {
        return null;
    }

    $timezone = new DateTimeZone(date_default_timezone_get());

    $formats = [
        'Ymd\\THis\\Z' => new DateTimeZone('UTC'),
        'Ymd\\THi\\Z' => new DateTimeZone('UTC'),
        'Ymd\\THis' => $timezone,
        'Ymd\\THi' => $timezone,
        'Ymd' => $timezone
    ];

    foreach ($formats as $format => $tz) {
        $dt = DateTimeImmutable::createFromFormat($format, $value, $tz);
        if ($dt instanceof DateTimeImmutable) {
            return $dt->setTimezone($timezone);
        }
    }

    return null;
}

function decodeIcsText(string $value): string {
    $decoded = str_replace(['\\\\', '\\,', '\\;', '\\n', '\\N'], ['\\', ',', ';', "\n", "\n"], $value);
    return trim($decoded);
}

function isHomeGameFromSummary(string $summary, string $ownTeamName = ''): bool {
    $parts = splitTeamsFromSummary(trim(removeTrailingScoreFromSummary($summary)));
    if ($parts !== false && count($parts) === 2) {
        $normalizedOwnTeam = normalizeTeamNameForComparison($ownTeamName);
        if ($normalizedOwnTeam !== '') {
            $left = trim($parts[0]);
            $right = trim($parts[1]);
            if (normalizeTeamNameForComparison($left) === $normalizedOwnTeam) {
                return true;
            }
            if (normalizeTeamNameForComparison($right) === $normalizedOwnTeam) {
                return false;
            }
        }
    }

    $lower = mb_strtolower($summary);
    return str_contains($lower, 'heim') || str_contains($lower, 'home');
}

function splitTeamsFromSummary(string $summary): array|false {
    return preg_split('/\s+[\-\x{2013}\x{2014}]\s+/u', $summary, 2);
}
