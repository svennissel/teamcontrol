import { test, expect } from '@playwright/test';

test.describe('Spiele-Seite Tests', () => {
  // ClubAdmin User Hash
  const testHash = 'HYmpZn_wlAwIaodR6F48SQ';
  const testPlayerName = 'E2E Test Spieler Game';
  const testTeamName = 'E2E Test Team Game';

  test.beforeEach(async ({ page }) => {
    // Login als ClubAdmin
    await page.goto(`login.php?hash=${testHash}`);
    
    // Testmannschaft erstellen
    await page.goto('teams.php');
    if (await page.locator('button#add-team-btn:has-text("+")').isVisible()) {
        await page.click('button#add-team-btn:has-text("+")');
        await page.fill('#addTeamModal input[name="name"]', testTeamName);
        await page.click('#addTeamModal button:has-text("Mannschaft anlegen")');
    }

    // Testspieler erstellen
    await page.goto('players.php');
    if (await page.locator('button#add-player-btn:has-text("+")').isVisible()) {
        await page.click('button#add-player-btn:has-text("+")');
        await page.fill('#addPlayerModal input[name="name"]', testPlayerName);
        // Die neu erstellte Mannschaft wählen
        const teamSelect = page.locator('#addPlayerModal select[name="team_ids[]"]');
        if (await teamSelect.isVisible()) {
            await teamSelect.selectOption({ label: testTeamName });
        }
        await page.click('#addPlayerModal button:has-text("Spieler anlegen")');
    }

    // Hash des neuen Spielers aus dem versteckten Feld auslesen
    const playerCard = page.locator('.event-card', { hasText: testPlayerName });
    const playerHash = await playerCard.locator('input.player-hash-input').inputValue();

    // Als neu erstellter Spieler einloggen
    await page.goto(`login.php?hash=${playerHash}`);
    
    // Zurück zur Spiele-Seite
    await page.goto('games.php');
  });

  test.afterEach(async ({ page }) => {
    // Wieder als ClubAdmin einloggen, um Löschrechte zu haben
    await page.goto(`login.php?hash=${testHash}`);

    // Testspieler wieder löschen
    await page.goto('players.php');
    const playerCard = page.locator('.event-card', { hasText: testPlayerName });
    if (await playerCard.isVisible()) {
        const deleteBtn = playerCard.locator('button#delete-player-btn');
        if (await deleteBtn.isVisible()) {
            page.once('dialog', dialog => dialog.accept());
            await deleteBtn.click();
        }
    }

    // Testmannschaft wieder löschen
    await page.goto('teams.php');
    const teamCard = page.locator('.event-card', { hasText: testTeamName });
    if (await teamCard.isVisible()) {
        const deleteBtn = teamCard.locator('button#delete-team-btn');
        if (await deleteBtn.isVisible()) {
            page.once('dialog', dialog => dialog.accept());
            await deleteBtn.click();
        }
    }
  });

  test('Kompletter Spiel-Lebenszyklus: Erstellen, Bearbeiten, Abstimmen, Löschen', async ({ page }) => {
    // 1. Spiel erstellen (Wir sind aktuell als Spieler eingeloggt, brauchen aber Admin-Rechte)
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
    
    await page.click('#addMatchModal button:has-text("Spiel anlegen")');

    // 1b. Spiel ohne Treffzeitpunkt erstellen
    const testOpponentNoMeeting = 'E2E Test Gegner kein Treffzeipunkt';
    await page.click('button#add-match-btn:has-text("+")');
    await page.fill('#addMatchModal input[name="match_date"]', new Date().toISOString().split('T')[0]);
    await page.fill('#addMatchModal input[name="start_time"]', '19:00');
    await page.fill('#addMatchModal input[name="meeting_time"]', ''); // Leer lassen
    await page.fill('#addMatchModal input[name="opponent"]', testOpponentNoMeeting);
    await page.selectOption('#addMatchModal select[name="team_id"]', { label: testTeamName });
    await page.click('#addMatchModal button:has-text("Spiel anlegen")');

    // Prüfen ob beide Spiele da sind und beim zweiten kein "Treffen" steht
    const matchCardNoMeeting = page.locator('.event-card', { hasText: testOpponentNoMeeting });
    await expect(matchCardNoMeeting).toBeVisible();
    await expect(matchCardNoMeeting).not.toContainText('Treffen');

    // 2. Als Testspieler einloggen
    // Einfacher: Wir navigieren zur Spielerseite und holen ihn uns nochmal
    await page.goto('players.php');
    const playerCard = page.locator('.event-card', { hasText: testPlayerName });
    const playerHash = await playerCard.locator('input.player-hash-input').inputValue();
    await page.goto(`login.php?hash=${playerHash}`);
    await page.goto('games.php');

    // 3. Spiel bearbeiten (Der Testspieler ist kein Admin, also wieder als Admin einloggen zum Bearbeiten)
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');
    const matchCard = page.locator('.event-card', { hasText: testOpponent });
    await expect(matchCard).toBeVisible();

    await matchCard.locator('.edit-btn').click();
    await page.fill('#editMatchModal input[name="opponent"]', editedOpponent);
    await page.click('#editMatchModal button:has-text("Änderungen speichern")');
    
    await expect(page.locator('.event-card', { hasText: editedOpponent })).toBeVisible();

    // 4. Abstimmen als Spieler
    await page.goto(`login.php?hash=${playerHash}`);
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
    page.once('dialog', dialog => dialog.accept());
    await finalMatchCard.locator('.delete-btn').click();

    await expect(page.locator('.event-card', { hasText: editedOpponent })).not.toBeVisible();

    // 5b. Das andere Spiel auch löschen
    await page.goto('games.php');
    const noMeetingMatchCard = page.locator('.event-card', { hasText: testOpponentNoMeeting });
    if (await noMeetingMatchCard.isVisible()) {
        page.once('dialog', dialog => dialog.accept());
        await noMeetingMatchCard.locator('.delete-btn').click();
    }

    // 6. Keine Warnungen prüfen
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Warning');
    expect(bodyText).not.toContain('Notice:');
    expect(bodyText).not.toContain('Fatal error:');
  });
});
