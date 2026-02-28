import { test, expect } from '@playwright/test';

test.describe('Vote Buttons Visibility', () => {
  test.describe.configure({ mode: 'serial' });
  const adminHash = 'testHash';
  const playerWithoutTeamName = 'Spieler Ohne Team';
  const playerWithTeamName = 'Spieler Mit Team';
  const playerWithOtherTeamName = 'Spieler Mit Anderem Team';
  const testTeamName = 'Sichtbarkeit Test Team';
  const otherTeamName = 'Anderes Team';

  test.beforeEach(async ({ page }) => {
    // 1. Login als Admin
    await page.goto(`login.php?hash=${adminHash}`);

    // 2. Test-Teams erstellen (falls nicht vorhanden)
    await page.goto('teams.php');
    if (!(await page.locator('.event-card', { hasText: testTeamName }).isVisible())) {
      await page.click('button#add-team-btn:has-text("+")');
      await page.fill('#addTeamModal input[name="name"]', testTeamName);
      await page.click('#addTeamModal button:has-text("Mannschaft anlegen")');
    }
    if (!(await page.locator('.event-card', { hasText: otherTeamName }).isVisible())) {
      await page.click('button#add-team-btn:has-text("+")');
      await page.fill('#addTeamModal input[name="name"]', otherTeamName);
      await page.click('#addTeamModal button:has-text("Mannschaft anlegen")');
    }

    // 3. Spieler ohne Team erstellen
    await page.goto('players.php');
    if (!(await page.locator('.event-card', { hasText: playerWithoutTeamName }).isVisible())) {
      await page.click('button#add-player-btn:has-text("+")');
      await page.fill('#addPlayerModal input[name="name"]', playerWithoutTeamName);
      await page.locator('#addPlayerModal select[name="team_ids[]"]').selectOption({ label: otherTeamName });
      await page.click('#addPlayerModal button:has-text("Spieler anlegen")');
    }

    // 4. Spieler mit Team erstellen
    if (!(await page.locator('.event-card', { hasText: playerWithTeamName }).isVisible())) {
      await page.click('button#add-player-btn:has-text("+")');
      await page.fill('#addPlayerModal input[name="name"]', playerWithTeamName);
      await page.locator('#addPlayerModal select[name="team_ids[]"]').selectOption({ label: testTeamName });
      await page.click('#addPlayerModal button:has-text("Spieler anlegen")');
    }

    // Spieler mit anderem Team erstellen
    if (!(await page.locator('.event-card', { hasText: playerWithOtherTeamName }).isVisible())) {
      await page.click('button#add-player-btn:has-text("+")');
      await page.fill('#addPlayerModal input[name="name"]', playerWithOtherTeamName);
      await page.locator('#addPlayerModal select[name="team_ids[]"]').selectOption({ label: otherTeamName });
      await page.click('#addPlayerModal button:has-text("Spieler anlegen")');
    }

    // 5. Ein Training erstellen
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const testDate = tomorrow.toISOString().split('T')[0];
    await page.goto('trainings.php');
    if (!(await page.locator('.event-card', { hasText: '18:18' }).isVisible())) {
        await page.click('button#add-training-btn:has-text("+")');
        await page.fill('#addTrainingModal input[name="training_date"]', testDate);
        await page.fill('#addTrainingModal input[name="training_time"]', '18:18');
        await page.locator('#addTrainingModal select[name="team_ids[]"]').selectOption({ label: testTeamName });
        await page.click('#addTrainingModal button:has-text("Training anlegen")');
    }

    // 6. Ein Spiel erstellen, falls keins da ist
    await page.goto('games.php');
    if (!(await page.locator('.event-card', {hasText: 'Test Gegner'}).first().isVisible())) {
        await page.click('button#add-match-btn:has-text("+")');
        await page.fill('#addMatchModal input[name="opponent"]', 'Test Gegner');
        await page.fill('#addMatchModal input[name="match_date"]', testDate);
        await page.fill('#addMatchModal input[name="start_time"]', '19:19');
        await page.locator('#addMatchModal select[name="team_id"]').selectOption({ label: testTeamName });
        await page.click('#addMatchModal button:has-text("Spiel anlegen")');
    }
  });

  test('Abstimmungs-Buttons sollten für Spieler in einem falschen Team versteckt sein', async ({ page }) => {
    // Hash für Spieler in einem falschen Team holen
    await page.goto(`login.php?hash=${adminHash}`);
    await page.goto('players.php');
    const playerCard = page.locator('.event-card', { hasText: playerWithoutTeamName });
    const playerHash = await playerCard.locator('input.player-hash-input').inputValue();

    // Als Spieler in einem falschen Team einloggen
    await page.goto(`login.php?hash=${playerHash}`);

    // Trainings-Seite prüfen
    await page.goto('trainings.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();

    // Spiele-Seite prüfen
    await page.goto('games.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();
  });

  test('Abstimmungs-Buttons sollten für Spieler mit Team sichtbar sein', async ({ page }) => {
    // Hash für Spieler mit Team holen
    await page.goto(`login.php?hash=${adminHash}`);
    await page.goto('players.php');
    const playerCard = page.locator('.event-card', { hasText: playerWithTeamName });
    const playerHash = await playerCard.locator('input.player-hash-input').inputValue();

    // Als Spieler mit Team einloggen
    await page.goto(`login.php?hash=${playerHash}`);

    // Trainings-Seite prüfen
    await page.goto('trainings.php');
    await expect(page.locator('.vote-form').first()).toBeVisible();

    // Spiele-Seite prüfen
    await page.goto('games.php');
    await expect(page.locator('.vote-form').first()).toBeVisible();
  });

  test('Abstimmungs-Buttons sollten für Spieler in einem anderen Team versteckt sein', async ({ page }) => {
    // Hash für Spieler mit anderem Team holen
    await page.goto(`login.php?hash=${adminHash}`);
    await page.goto('players.php');
    const playerCard = page.locator('.event-card', { hasText: playerWithOtherTeamName });
    const playerHash = await playerCard.locator('input.player-hash-input').inputValue();

    // Als Spieler mit anderem Team einloggen
    await page.goto(`login.php?hash=${playerHash}`);

    // Trainings-Seite prüfen (Events sind für testTeamName erstellt, Spieler ist in otherTeamName)
    await page.goto('trainings.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();

    // Spiele-Seite prüfen
    await page.goto('games.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();
  });
});
