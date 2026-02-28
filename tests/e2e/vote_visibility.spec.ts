import { test, expect } from '@playwright/test';

test.describe('Vote Buttons Visibility', () => {
  test.describe.configure({ mode: 'serial' });
  const adminHash = 'testHash';
  const playerWithoutTeamHash = 'playerHashOhneTeam';
  const playerWithTeamHash = 'playerHashMitTeam';
  const playerWithOtherTeamHash = 'playerHashAnderesTeam';

  test('Abstimmungs-Buttons sollten für Spieler in einem falschen Team versteckt sein', async ({ page }) => {
    // Als Spieler in einem falschen Team einloggen
    await page.goto(`login.php?hash=${playerWithoutTeamHash}`);

    // Trainings-Seite prüfen
    await page.goto('trainings.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();

    // Spiele-Seite prüfen
    await page.goto('games.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();
  });

  test('Abstimmungs-Buttons sollten für Spieler mit Team sichtbar sein', async ({ page }) => {
    // Als Spieler mit Team einloggen
    await page.goto(`login.php?hash=${playerWithTeamHash}`);

    // Trainings-Seite prüfen
    await page.goto('trainings.php');
    await expect(page.locator('.vote-form').first()).toBeVisible();

    // Spiele-Seite prüfen
    await page.goto('games.php');
    await expect(page.locator('.vote-form').first()).toBeVisible();
  });

  test('Abstimmungs-Buttons sollten für Spieler in einem anderen Team versteckt sein', async ({ page }) => {
    // Als Spieler mit anderem Team einloggen
    await page.goto(`login.php?hash=${playerWithOtherTeamHash}`);

    // Trainings-Seite prüfen (Events sind für Sichtbarkeit Test Team erstellt, Spieler ist in Anderes Team)
    await page.goto('trainings.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();

    // Spiele-Seite prüfen
    await page.goto('games.php');
    await expect(page.locator('.vote-form')).not.toBeVisible();
  });
});
