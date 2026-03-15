import { test, expect } from '@playwright/test';

/**
 * Dieser Test setzt voraus, dass in der Datenbank ein Spieler mit dem 
 * Hash 'test-hash' existiert. Da Playwright gegen eine laufende
 * Anwendung testet, muss die Datenbank entsprechend vorbereitet sein.
 */
test('Login über Hash-Link', async ({ page }) => {
  // Gehe zur Login-Seite mit einem Test-Hash
  // WICHTIG: Ersetzen Sie 'test-hash' durch einen echten Hash aus Ihrer Datenbank
  const testHash = 'testHash';
  await page.goto(`login.php?hash=${testHash}`);

  // Nach dem Login sollte localStorage gesetzt sein und eine Weiterleitung erfolgen
  // Wir prüfen, ob wir auf einen der erlaubten Tabs gelandet sind
  await expect(page).toHaveURL(/.*(games|trainings|teams|players)\.php/);

  // Prüfen ob der Header geladen wurde (Anzeichen für erfolgreichen Login)
  await expect(page.locator('header')).toBeVisible();
  
  // Optional: Prüfen ob der Spielername im Header erscheint
  // await expect(page.locator('.user-info')).toContainText('Test Spieler');
});

test('Fehlgeschlagener Login mit ungültigem Hash', async ({ page }) => {
  await page.goto('login.php?hash=ungueltiger-hash');

  // Es sollte eine Fehlermeldung erscheinen
  const errorMsg = page.locator('.error');
  await expect(errorMsg).toBeVisible();
  await expect(errorMsg).toContainText('Ungültiger Login-Link.');
});
