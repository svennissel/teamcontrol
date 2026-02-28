import { test, expect } from '@playwright/test';

test.describe('Training-Seite Tests', () => {
  // ClubAdmin User Hash
  const testHash = 'testHash';
  const testPlayerHash = 'playerHashTraining';
  const testPlayerName = 'E2E Test Spieler Training';
  const testTeamName = 'E2E Test Team Training';

  test('Kompletter Trainings-Lebenszyklus: Erstellen, Bearbeiten, Abstimmen, Löschen', async ({ page }) => {
    // 1. Training erstellen als Admin
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');
    
    const testTime = '11:11';
    const editedTime = '12:12';
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const testDate = tomorrow.toISOString().split('T')[0];

    await page.click('button#add-training-btn:has-text("+")');
    await page.fill('#addTrainingModal input[name="training_date"]', testDate);
    await page.fill('#addTrainingModal input[name="training_time"]', testTime);
    
    // Mannschaft auswählen
    const teamSelect = page.locator('#addTrainingModal select[name="team_ids[]"]');
    await teamSelect.selectOption({ label: testTeamName });
    
    await page.click('#addTrainingModal button:has-text("Anlegen")');

    // 2. Als Testspieler einloggen
    await page.goto(`login.php?hash=${testPlayerHash}`);
    await page.goto('trainings.php');

    // 3. Training bearbeiten (Der Testspieler ist kein Admin, also wieder als Admin einloggen zum Bearbeiten)
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');
    // Wir suchen das Training anhand des Datums und der Zeit (da es keinen "Gegner" wie beim Spiel gibt)
    const trainingCard = page.locator('.event-card', { hasText: testTime });
    await expect(trainingCard).toBeVisible();
    
    await trainingCard.locator('.edit-btn').click();
    await page.fill('#editTrainingModal input[name="training_time"]', editedTime);
    await page.click('#editTrainingModal button:has-text("Speichern")');
    
    await expect(page.locator('.event-card', { hasText: editedTime })).toBeVisible();

    // 4. Abstimmen als Spieler
    await page.goto(`login.php?hash=${testPlayerHash}`);
    await page.goto('trainings.php');
    const updatedTrainingCard = page.locator('.event-card', { hasText: editedTime });

    // Zusage
    await updatedTrainingCard.locator('button[value="yes"]').click();
    await updatedTrainingCard.locator('.btn-attendance').click();
    await expect(page.locator('#attendanceModal')).toBeVisible();
    await expect(page.locator('#list-yes')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // Absage
    await updatedTrainingCard.locator('button[value="no"]').click();
    await updatedTrainingCard.locator('.btn-attendance').click();
    await expect(page.locator('#list-no')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // Vielleicht
    await updatedTrainingCard.locator('button[value="maybe"]').click();
    await updatedTrainingCard.locator('.btn-attendance').click();
    await expect(page.locator('#list-maybe')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // 5. Training löschen (als Admin)
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');
    const finalTrainingCard = page.locator('.event-card', { hasText: editedTime });
    
    await finalTrainingCard.locator('.delete-btn').click();
    await page.locator('#confirmModalOk').click();

    await expect(page.locator('.event-card', { hasText: editedTime })).not.toBeVisible();

    // 6. Keine Warnungen prüfen
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Warning');
    expect(bodyText).not.toContain('Notice:');
    expect(bodyText).not.toContain('Fatal error:');
  });
});
