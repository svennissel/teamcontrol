import { test, expect } from '@playwright/test';

test.describe('Spiele-Seite Tests', () => {
  // ClubAdmin User Hash
  const testHash = 'testHash';
  const testPlayerHash = 'playerHashGame';
  const testPlayerName = 'E2E Test Spieler Game';
  const testTeamName = 'E2E Test Team Game';

  test('Kompletter Spiel-Lebenszyklus: Erstellen, Bearbeiten, Abstimmen, Löschen', async ({ page }) => {
    // 1. Spiel erstellen als Admin
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');
    
    const testOpponent = 'E2E Test Gegner mit Treffzeitpunkt';
    const editedOpponent = 'E2E Test Gegner Edit';

    await page.click('button#add-match-btn:has-text("+")');
    await page.fill('#addMatchModal input[name="match_date"]', new Date().toISOString().split('T')[0]);
    await page.fill('#addMatchModal input[name="start_time"]', '18:00');
    await page.fill('#addMatchModal input[name="meeting_time"]', '17:30');
    await page.fill('#addMatchModal input[name="opponent"]', testOpponent);
    await page.check('#addMatchModal input[name="is_home_game"]');
    
    // Mannschaft auswählen
    const teamSelect = page.locator('#addMatchModal select[name="team_id"]');
    await teamSelect.selectOption({ label: testTeamName });
    
    await page.click('#addMatchModal button:has-text("Anlegen")');

    // 1b. Spiel ohne Treffzeitpunkt erstellen
    const testOpponentNoMeeting = 'E2E Test Gegner kein Treffzeipunkt';
    await page.click('button#add-match-btn:has-text("+")');
    await page.fill('#addMatchModal input[name="match_date"]', new Date().toISOString().split('T')[0]);
    await page.fill('#addMatchModal input[name="start_time"]', '19:00');
    await page.fill('#addMatchModal input[name="meeting_time"]', ''); // Leer lassen
    await page.fill('#addMatchModal input[name="opponent"]', testOpponentNoMeeting);
    await page.selectOption('#addMatchModal select[name="team_id"]', { label: testTeamName });
    await page.click('#addMatchModal button:has-text("Anlegen")');

    // Prüfen ob beide Spiele da sind und beim zweiten kein "Treffen" steht
    const matchCardNoMeeting = page.locator('.event-card', { hasText: testOpponentNoMeeting });
    await expect(matchCardNoMeeting).toBeVisible();
    await expect(matchCardNoMeeting).not.toContainText('Treffen');

    // 2. Als Testspieler einloggen
    await page.goto(`login.php?hash=${testPlayerHash}`);
    await page.goto('games.php');

    // 3. Spiel bearbeiten (Der Testspieler ist kein Admin, also wieder als Admin einloggen zum Bearbeiten)
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');
    const matchCard = page.locator('.event-card', { hasText: testOpponent });
    await expect(matchCard).toBeVisible();

    await matchCard.locator('.edit-btn').click();
    await page.fill('#editMatchModal input[name="opponent"]', editedOpponent);
    await page.click('#editMatchModal button:has-text("Speichern")');
    
    await expect(page.locator('.event-card', { hasText: editedOpponent })).toBeVisible();

    // 4. Abstimmen als Spieler
    await page.goto(`login.php?hash=${testPlayerHash}`);
    await page.goto('games.php');
    const updatedMatchCard = page.locator('.event-card', { hasText: editedOpponent });

    // Zusage
    await updatedMatchCard.locator('button[value="yes"]').click();
    await updatedMatchCard.locator('.btn-attendance').click();
    await expect(page.locator('#attendanceModal')).toBeVisible();
    await expect(page.locator('#list-yes')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // Absage
    await updatedMatchCard.locator('button[value="no"]').click();
    await updatedMatchCard.locator('.btn-attendance').click();
    await expect(page.locator('#list-no')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // Vielleicht
    await updatedMatchCard.locator('button[value="maybe"]').click();
    await updatedMatchCard.locator('.btn-attendance').click();
    await expect(page.locator('#list-maybe')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // 5. Spiel löschen (als Admin)
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');
    const finalMatchCard = page.locator('.event-card', { hasText: editedOpponent });

    await finalMatchCard.locator('.delete-btn').isVisible();
    await finalMatchCard.locator('.delete-btn').isEnabled();
    await finalMatchCard.locator('.delete-btn').click();
    await page.locator('#confirmModalOk').click();

    await expect(page.locator('.event-card', { hasText: editedOpponent })).not.toBeVisible();

    // 5b. Das andere Spiel auch löschen
    await page.goto('games.php');
    const noMeetingMatchCard = page.locator('.event-card', { hasText: testOpponentNoMeeting });
    if (await noMeetingMatchCard.isVisible()) {
        await noMeetingMatchCard.locator('.delete-btn').click();
        await page.locator('#confirmModalOk').click();
    }

    // 6. Keine Warnungen prüfen
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Warning');
    expect(bodyText).not.toContain('Notice:');
    expect(bodyText).not.toContain('Fatal error:');
  });
});
