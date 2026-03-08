import { test, expect } from '@playwright/test';

test.describe('Spieler-Suche Tests', () => {
  const adminHash = 'testHash';

  test.beforeEach(async ({ page }) => {
    await page.goto(`login.php?hash=${adminHash}`);
    await page.goto('players.php');
  });

  test('Suchfeld ist sichtbar', async ({ page }) => {
    await expect(page.locator('#player-search')).toBeVisible();
  });

  test('Alle Spieler sind initial sichtbar', async ({ page }) => {
    const cards = page.locator('#players .events .event-card');
    const count = await cards.count();
    expect(count).toBeGreaterThanOrEqual(6);
    for (let i = 0; i < count; i++) {
      await expect(cards.nth(i)).toBeVisible();
    }
  });

  test('Suche filtert Spieler nach Name', async ({ page }) => {
    await page.fill('#player-search', 'Admin');
    // Warten auf Debounce (500ms)
    await page.waitForTimeout(600);

    const adminCard = page.locator('#players .events .event-card', { hasText: 'Admin' });
    await expect(adminCard.first()).toBeVisible();

    // Spieler ohne "Admin" im Namen sollten ausgeblendet sein
    const gameCard = page.locator('#players .events .event-card', { hasText: 'E2E Test Spieler Game' });
    await expect(gameCard).toBeHidden();
  });

  test('Suche ist case-insensitiv', async ({ page }) => {
    await page.fill('#player-search', 'admin');
    await page.waitForTimeout(600);

    const adminCard = page.locator('#players .events .event-card', { hasText: 'Admin' });
    await expect(adminCard.first()).toBeVisible();

    await page.fill('#player-search', 'ADMIN');
    await page.waitForTimeout(600);

    await expect(adminCard.first()).toBeVisible();
  });

  test('Leere Suche zeigt alle Spieler wieder an', async ({ page }) => {
    await page.fill('#player-search', 'Admin');
    await page.waitForTimeout(600);

    // Einige Spieler sind ausgeblendet
    const gameCard = page.locator('#players .events .event-card', { hasText: 'E2E Test Spieler Game' });
    await expect(gameCard).toBeHidden();

    // Suchfeld leeren
    await page.fill('#player-search', '');
    await page.waitForTimeout(600);

    // Alle Spieler wieder sichtbar
    const cards = page.locator('#players .events .event-card');
    const count = await cards.count();
    for (let i = 0; i < count; i++) {
      await expect(cards.nth(i)).toBeVisible();
    }
  });

  test('Suche ohne Treffer blendet alle Spieler aus', async ({ page }) => {
    await page.fill('#player-search', 'xyzNichtVorhanden');
    await page.waitForTimeout(600);

    const cards = page.locator('#players .events .event-card');
    const count = await cards.count();
    for (let i = 0; i < count; i++) {
      await expect(cards.nth(i)).toBeHidden();
    }
  });

  test('Suche mit Teilstring findet Spieler', async ({ page }) => {
    await page.fill('#player-search', 'Spieler');
    await page.waitForTimeout(600);

    // Alle Spieler mit "Spieler" im Namen sichtbar
    const gameCard = page.locator('#players .events .event-card', { hasText: 'E2E Test Spieler Game' });
    await expect(gameCard).toBeVisible();

    const trainingCard = page.locator('#players .events .event-card', { hasText: 'E2E Test Spieler Training' });
    await expect(trainingCard).toBeVisible();

    // "Admin" hat kein "Spieler" im Namen - nur die erste Card mit exakt "Admin" prüfen
    const allCards = page.locator('#players .events .event-card');
    const count = await allCards.count();
    let adminOnlyHidden = false;
    for (let i = 0; i < count; i++) {
      const name = await allCards.nth(i).locator('.card-header h3').textContent();
      if (name?.trim() === 'Admin') {
        await expect(allCards.nth(i)).toBeHidden();
        adminOnlyHidden = true;
      }
    }
    expect(adminOnlyHidden).toBe(true);
  });

  test('Debounce verzögert die Filterung', async ({ page }) => {
    await page.fill('#player-search', 'xyzNichtVorhanden');

    // Direkt nach Eingabe sollten Spieler noch sichtbar sein (Debounce noch nicht ausgelöst)
    const cards = page.locator('#players .events .event-card');
    const firstCard = cards.first();
    await expect(firstCard).toBeVisible();

    // Nach Debounce sollten alle ausgeblendet sein
    await page.waitForTimeout(600);
    const count = await cards.count();
    for (let i = 0; i < count; i++) {
      await expect(cards.nth(i)).toBeHidden();
    }
  });
});
