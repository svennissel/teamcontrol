<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/includes/ics_import.php';

class IcsImportTest extends TestCase
{
    public function testParseIcsMatchesWithFullSeasonTemplateChecksAllFields()
    {
        $ics = <<<'ICS'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:BFS-Ergebnisdienst Rheinland
METHOD:PUBLISH
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:STANDARD
DTSTART:20241027T010000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:20250330T010000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
END:DAYLIGHT
BEGIN:STANDARD
DTSTART:20251026T010000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:20260329T010000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
END:DAYLIGHT
BEGIN:STANDARD
DTSTART:20261025T010000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:20270328T010000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:69eca5c84ad68a734fe54061202bee3e
SUMMARY:ASV Sankt Augustin – TuS Schladern II 3:0
DESCRIPTION:Bezirksklasse 2 Männer\, 1. Spieltag\, Spiel 1\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 25:21\, 25:17\, 25:23
DTSTART;TZID=Europe/Berlin:20250924T200000
DTEND;TZID=Europe/Berlin:20250924T223000
LOCATION:Dreifach Sporthalle des Rhein-Sieg-Gym.\,
  An der Post 80\, 53757 Sankt Augustin
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:b7b91bf034542dbd23a3161a0753ab76
SUMMARY:TuS Schladern II – Bröltaler SC 03 3:1
DESCRIPTION:Bezirksklasse 2 Männer\, 2. Spieltag\, Spiel 6\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 26:24\, 21:25\, 25:13\, 25:19
DTSTART;TZID=Europe/Berlin:20251006T200000
DTEND;TZID=Europe/Berlin:20251006T223000
LOCATION:Bodenbergschule Schladern\,
  Elsternweg 8\, 51570 Windeck
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:0a37eab7087295a3a9fb560efe063eb2
SUMMARY:BSG FlgH Wahn II – TuS Schladern II 2:3
DESCRIPTION:Bezirksklasse 2 Männer\, 3. Spieltag\, Spiel 8\n
 Einlass 19:15 Uhr\, Spielbeginn 19:45 Uhr\n
 20:25\, 25:19\, 13:25\, 25:20\, 15:17
DTSTART;TZID=Europe/Berlin:20251106T191500
DTEND;TZID=Europe/Berlin:20251106T214500
LOCATION:Luftwaffenkaserne Halle 212\,
  Flughafenstr. 1\, 51147 Köln
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:8b4a8a5f976e35c0e19dead386bb8183
SUMMARY:TuS Schladern II – Siegburger TV II 3:0
DESCRIPTION:Bezirksklasse 2 Männer\, 4. Spieltag\, Spiel 11\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 25:18\, 25:10\, 25:14
DTSTART;TZID=Europe/Berlin:20251117T200000
DTEND;TZID=Europe/Berlin:20251117T223000
LOCATION:Bodenbergschule Schladern\,
  Elsternweg 8\, 51570 Windeck
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:037135adc843353d38b65e6e1272ba2d
SUMMARY:TSV Seelscheid – TuS Schladern II 3:1
DESCRIPTION:Bezirksklasse 2 Männer\, 5. Spieltag\, Spiel 15\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 25:22\, 25:22\, 21:25\, 25:16
DTSTART;TZID=Europe/Berlin:20251201T200000
DTEND;TZID=Europe/Berlin:20251201T223000
LOCATION:Mehrzweckhalle Am Gansberg\,
  Am Gansberg 1\, 53819 Seelscheid
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:47e03a4a55d565844789797d77c657d3
SUMMARY:TV Eitorf – TuS Schladern II 1:3
DESCRIPTION:Bezirksklasse 2 Männer\, 6. Spieltag\, Spiel 16\n
 Einlass 18:30 Uhr\, Spielbeginn 19:00 Uhr\n
 25:23\, 16:25\, 22:25\, 15:25
DTSTART;TZID=Europe/Berlin:20251216T183000
DTEND;TZID=Europe/Berlin:20251216T210000
LOCATION:Realschule Herchen\,
  An der Realschule\, 51570 Windeck
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:2760ba7ab098481570f6dd9141bd43de
SUMMARY:TuS Schladern II – ASV Sankt Augustin 3:2
DESCRIPTION:Bezirksklasse 2 Männer\, 8. Spieltag\, Spiel 22\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 23:25\, 23:25\, 25:23\, 25:22\, 15:12
DTSTART;TZID=Europe/Berlin:20260209T200000
DTEND;TZID=Europe/Berlin:20260209T223000
LOCATION:Bodenbergschule Schladern\,
  Elsternweg 8\, 51570 Windeck
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:1cb94a62561f2363c3073525c744a166
SUMMARY:Bröltaler SC 03 – TuS Schladern II 3:1
DESCRIPTION:Bezirksklasse 2 Männer\, 9. Spieltag\, Spiel 27\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 23:25\, 25:15\, 25:11\, 25:22
DTSTART;TZID=Europe/Berlin:20260305T200000
DTEND;TZID=Europe/Berlin:20260305T223000
LOCATION:Bröltalhalle Ruppichteroth\,
  Dr.-Herzfeld-Str. 7\, 53809 Ruppichteroth
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:0219c952f801bfc144a74ced68ce5def
SUMMARY:TuS Schladern II – BSG FlgH Wahn II 0:3
DESCRIPTION:Bezirksklasse 2 Männer\, 10. Spieltag\, Spiel 29\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 11:25\, 21:25\, 23:25
DTSTART;TZID=Europe/Berlin:20260316T200000
DTEND;TZID=Europe/Berlin:20260316T223000
LOCATION:Bodenbergschule Schladern\,
  Elsternweg 8\, 51570 Windeck
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:370d5031fb88f99fe6d0de4f0f4c2ad5
SUMMARY:Siegburger TV II – TuS Schladern II 0:3
DESCRIPTION:Bezirksklasse 2 Männer\, 11. Spieltag\, Spiel 32\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr\n
 21:25\, 14:25\, 23:25
DTSTART;TZID=Europe/Berlin:20260421T200000
DTEND;TZID=Europe/Berlin:20260421T223000
LOCATION:Berufskolleg Siegburg Halle E\,
  Hochstraße 9\, 53721 Siegburg
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:64e0ddce0996d7934e628fe7eae8708b
SUMMARY:TuS Schladern II – TSV Seelscheid
DESCRIPTION:Bezirksklasse 2 Männer\, 12. Spieltag\, Spiel 36\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr
DTSTART;TZID=Europe/Berlin:20260504T200000
DTEND;TZID=Europe/Berlin:20260504T223000
LOCATION:Bodenbergschule Schladern\,
  Elsternweg 8\, 51570 Windeck
END:VEVENT
BEGIN:VEVENT
CLASS:PUBLIC
DTSTAMP:20260427T203854
UID:e6484ca3283e8ee7b60302634a7e73dd
SUMMARY:TuS Schladern II – TV Eitorf
DESCRIPTION:Bezirksklasse 2 Männer\, 13. Spieltag\, Spiel 37\n
 Einlass 20:00 Uhr\, Spielbeginn 20:30 Uhr
DTSTART;TZID=Europe/Berlin:20260518T200000
DTEND;TZID=Europe/Berlin:20260518T223000
LOCATION:Bodenbergschule Schladern\,
  Elsternweg 8\, 51570 Windeck
END:VEVENT
END:VCALENDAR
ICS;

        $matches = parseIcsMatches($ics);

        $this->assertSame([
            ['match_date' => '2025-09-24', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'ASV Sankt Augustin', 'is_home_game' => false, 'location' => 'Dreifach Sporthalle des Rhein-Sieg-Gym., An der Post 80, 53757 Sankt Augustin'],
            ['match_date' => '2025-10-06', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'Bröltaler SC 03', 'is_home_game' => true, 'location' => ''],
            ['match_date' => '2025-11-06', 'start_time' => '19:15', 'meeting_time' => '', 'opponent' => 'BSG FlgH Wahn II', 'is_home_game' => false, 'location' => 'Luftwaffenkaserne Halle 212, Flughafenstr. 1, 51147 Köln'],
            ['match_date' => '2025-11-17', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'Siegburger TV II', 'is_home_game' => true, 'location' => ''],
            ['match_date' => '2025-12-01', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'TSV Seelscheid', 'is_home_game' => false, 'location' => 'Mehrzweckhalle Am Gansberg, Am Gansberg 1, 53819 Seelscheid'],
            ['match_date' => '2025-12-16', 'start_time' => '18:30', 'meeting_time' => '', 'opponent' => 'TV Eitorf', 'is_home_game' => false, 'location' => 'Realschule Herchen, An der Realschule, 51570 Windeck'],
            ['match_date' => '2026-02-09', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'ASV Sankt Augustin', 'is_home_game' => true, 'location' => ''],
            ['match_date' => '2026-03-05', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'Bröltaler SC 03', 'is_home_game' => false, 'location' => 'Bröltalhalle Ruppichteroth, Dr.-Herzfeld-Str. 7, 53809 Ruppichteroth'],
            ['match_date' => '2026-03-16', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'BSG FlgH Wahn II', 'is_home_game' => true, 'location' => ''],
            ['match_date' => '2026-04-21', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'Siegburger TV II', 'is_home_game' => false, 'location' => 'Berufskolleg Siegburg Halle E, Hochstraße 9, 53721 Siegburg'],
            ['match_date' => '2026-05-04', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'TSV Seelscheid', 'is_home_game' => true, 'location' => ''],
            ['match_date' => '2026-05-18', 'start_time' => '20:00', 'meeting_time' => '', 'opponent' => 'TV Eitorf', 'is_home_game' => true, 'location' => ''],
        ], $matches);
    }

    public function testExtractOpponentFromSummaryRemovesOwnTeamAndScore()
    {
        $summary = 'Eigene Mannschaft - TSV Gegner 3:0';

        $opponent = extractOpponentFromSummary($summary, 'Eigene Mannschaft');

        $this->assertSame('TSV Gegner', $opponent);
    }

    public function testExtractOpponentFromSummaryRemovesOwnTeamOnRightSide()
    {
        $summary = 'TSV Gegner - Eigene Mannschaft 0:3';

        $opponent = extractOpponentFromSummary($summary, 'Eigene Mannschaft');

        $this->assertSame('TSV Gegner', $opponent);
    }

    public function testExtractOpponentFromSummaryFallsBackToCleanSummaryIfNoOwnTeamMatch()
    {
        $summary = 'Team A - Team B 1:3';

        $opponent = extractOpponentFromSummary($summary, 'Eigene Mannschaft');

        $this->assertSame('Team A - Team B', $opponent);
    }

    public function testExtractOpponentFromSummaryRemovesOwnTeamOnRightSideNoScore()
    {
        $summary = 'TSV Gegner - Eigene Mannschaft';

        $opponent = extractOpponentFromSummary($summary, 'Eigene Mannschaft');

        $this->assertSame('TSV Gegner', $opponent);
    }


}
