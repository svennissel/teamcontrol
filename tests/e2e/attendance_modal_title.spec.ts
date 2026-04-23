import { test, expect } from '@playwright/test';

test.describe('Attendance-Dialog Titel', () => {
  const adminHash = 'testHash';

  async function verifyTitleStaysStable(page, pagePath: 'trainings.php' | 'games.php', expectedPrefix: string) {
    await page.goto(`login.php?hash=${adminHash}`);
    await page.goto(pagePath);

    const card = page.locator('.event-card', { has: page.locator('.vote-form') }).first();
    await expect(card).toBeVisible();
    const voteButton = card.locator('.vote-form button[value="yes"]');
    await voteButton.click({ button: 'right' });

    const voteTargetMenu = page.locator('#voteTargetMenu');
    await expect(voteTargetMenu).toBeVisible();
    await voteTargetMenu.locator('button.vote-target-menu-item', { hasText: 'Spieler Mit Team' }).click();

    const attendanceButton = card.locator('.btn-attendance');
    await attendanceButton.click();

    await expect(page.locator('#attendanceModal')).toBeVisible();
    const modalTitle = page.locator('#attendanceModalTitle');
    const firstTitle = (await modalTitle.innerText()).trim();
    expect(firstTitle).toContain(expectedPrefix);

    await page.click('#attendanceModal .close');
    await attendanceButton.click();

    const secondTitle = (await modalTitle.innerText()).trim();
    expect(secondTitle).toBe(firstTitle);
    expect(secondTitle).not.toBe('name');
  }

  test('Trainings-Seite: Titel bleibt nach Abstimmung für anderen Spieler korrekt', async ({ page }) => {
    await verifyTitleStaysStable(page, 'trainings.php', 'Training');
  });

  test('Spiele-Seite: Titel bleibt nach Abstimmung für anderen Spieler korrekt', async ({ page }) => {
    await verifyTitleStaysStable(page, 'games.php', 'Test Gegner');
  });
});
