import { test, expect } from '@playwright/test';

test.describe('Trainings-Filter Tests', () => {
  test.describe.configure({ mode: 'serial' });
  const adminHash = 'testHash';
  const team1Name = 'Filter Team 1';
  const team2Name = 'Filter Team 2';

  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    // Login als Admin
    await page.goto(`login.php?hash=${adminHash}`);

    // Teams erstellen falls nicht vorhanden
    await page.goto('teams.php');
    if (!(await page.locator('.event-card', { hasText: team1Name }).isVisible())) {
      await page.click('button#add-team-btn:has-text("+")');
      await page.fill('#addTeamModal input[name="name"]', team1Name);
      await page.click('#addTeamModal button:has-text("Mannschaft anlegen")');
    }
    if (!(await page.locator('.event-card', { hasText: team2Name }).isVisible())) {
      await page.click('button#add-team-btn:has-text("+")');
      await page.fill('#addTeamModal input[name="name"]', team2Name);
      await page.click('#addTeamModal button:has-text("Mannschaft anlegen")');
    }

    // Trainings erstellen
    await page.goto('trainings.php');
    
    // Training für Team 1
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const testDate = tomorrow.toISOString().split('T')[0];
    await page.click('button#add-training-btn:has-text("+")');
    await page.fill('#addTrainingModal input[name="training_date"]', testDate);
    await page.fill('#addTrainingModal input[name="training_time"]', '10:00');
    await page.locator('#addTrainingModal select[name="team_ids[]"]').selectOption({ label: team1Name });
    const responsePromise = page.waitForResponse('**/action.php');
    const responsePromise2 = page.waitForResponse('**/trainings.php');
    await page.click('#addTrainingModal button:has-text("Training anlegen")');
    const response = await responsePromise;
    const response2 = await responsePromise2;
    await page.waitForTimeout(500);

    // Training für Team 2
    await expect(page.locator('button#add-training-btn:has-text("+")')).toBeVisible();
    await page.click('button#add-training-btn:has-text("+")');
    await page.fill('#addTrainingModal input[name="training_date"]', testDate);
    await page.fill('#addTrainingModal input[name="training_time"]', '11:00');
    await page.locator('#addTrainingModal select[name="team_ids[]"]').selectOption({ label: team2Name });
    await page.click('#addTrainingModal button:has-text("Training anlegen")');
  });

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage();
    // Login als Admin
    await page.goto(`login.php?hash=${adminHash}`);

    // Trainings löschen
    await page.goto('trainings.php');
    const teams = [team1Name, team2Name];
    for (const team of teams) {
      let trainingCard = page.locator('.event-card', { hasText: team }).first();
      while (await trainingCard.isVisible()) {
        const deleteBtn = trainingCard.locator('.delete-btn');
        if (await deleteBtn.isVisible()) {
          page.once('dialog', dialog => dialog.accept());
          await deleteBtn.click();
          await page.waitForTimeout(500); // Warten auf das Löschen
        } else {
          break;
        }
        trainingCard = page.locator('.event-card', { hasText: team }).first();
      }
    }

    // Teams löschen
    await page.goto('teams.php');
    for (const team of teams) {
      const teamCard = page.locator('.event-card', { hasText: team });
      if (await teamCard.isVisible()) {
        const deleteBtn = teamCard.locator('button#delete-team-btn');
        if (await deleteBtn.isVisible()) {
          page.once('dialog', dialog => dialog.accept());
          await deleteBtn.click();
          await page.waitForTimeout(500);
        }
      }
    }
    await page.close();
  });

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
