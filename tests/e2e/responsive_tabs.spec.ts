import { test, expect } from '@playwright/test';

test.describe('Responsive Tabs Tests', () => {
  const testHash = 'testHash';

  test('Tabs sollten auf Desktop sichtbar sein und Dropdown versteckt', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');

    const tabs = page.locator('.tabs');
    const dropdown = page.locator('.tab-dropdown');

    await expect(tabs).toBeVisible();
    await expect(dropdown).not.toBeVisible();
  });

  test('Tabs sollten auf Mobile versteckt sein und Dropdown sichtbar', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');

    const tabs = page.locator('.tabs');
    const dropdown = page.locator('.tab-dropdown');

    await expect(tabs).not.toBeVisible();
    await expect(dropdown).toBeVisible();
  });

  test('Dropdown Navigation sollte funktionieren', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('games.php');

    const select = page.locator('.tab-select');
    await select.selectOption({ value: 'trainings.php' });

    await expect(page).toHaveURL(/trainings.php/);
    
    // Check if training page also has responsive dropdown
    await expect(page.locator('.tab-dropdown')).toBeVisible();
    await expect(page.locator('.tab-select')).toHaveValue('trainings.php');
  });
});
