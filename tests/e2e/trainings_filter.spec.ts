import { test, expect } from '@playwright/test';

test.describe('Trainings-Filter Tests', () => {
  test.describe.configure({ mode: 'serial' });
  const adminHash = 'testHash';
  const team1Name = 'Filter Team 1';
  const team2Name = 'Filter Team 2';

  test('Filter-Buttons sollten angezeigt werden, wenn Trainings für mehrere Teams vorhanden sind', async ({ page }) => {
    // Login als Admin
    await page.goto(`login.php?hash=${adminHash}`);

    await page.goto('trainings.php');
    await expect(page.locator('.filter-container')).toBeVisible();
    await expect(page.locator('.filter-btn', { hasText: 'Alle' })).toBeVisible();
    await expect(page.locator('.filter-btn', { hasText: team1Name })).toBeVisible();
    await expect(page.locator('.filter-btn', { hasText: team2Name })).toBeVisible();
  });

  test('Filtern sollte die Anzeige der Trainings einschränken', async ({ page }) => {
    // Login als Admin
    await page.goto(`login.php?hash=${adminHash}`);

    await page.goto('trainings.php');
    
    // Initial alle sichtbar (mindestens 2)
    const initialCount = await page.locator('.event-card').count();
    expect(initialCount).toBeGreaterThanOrEqual(2);

    // Filter auf Team 1
    await page.click(`.filter-btn:has-text("${team1Name}")`);
    
    // Nur Team 1 Trainings sichtbar
    const team1Cards = page.locator('.event-card:visible');
    const team1Count = await team1Cards.count();
    for (let i = 0; i < team1Count; i++) {
        await expect(team1Cards.nth(i)).toContainText(team1Name);
        await expect(team1Cards.nth(i)).not.toContainText(team2Name);
    }

    // Filter auf Team 2
    await page.click(`.filter-btn:has-text("${team2Name}")`);
    
    // Nur Team 2 Trainings sichtbar
    const team2Cards = page.locator('.event-card:visible');
    const team2Count = await team2Cards.count();
    for (let i = 0; i < team2Count; i++) {
        await expect(team2Cards.nth(i)).toContainText(team2Name);
        await expect(team2Cards.nth(i)).not.toContainText(team1Name);
    }

    // Zurück auf "Alle"
    await page.click('.filter-btn:has-text("Alle")');
    const finalCount = await page.locator('.event-card:visible').count();
    expect(finalCount).toBe(initialCount);
  });

});
