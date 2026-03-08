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

    await page.locator('button#add-training-btn:has-text("+"):visible').click();
    await page.fill('#addTrainingModal input[name="training_date"]', testDate);
    await page.fill('#addTrainingModal input[name="training_time"]', testTime);
    
    // Mannschaft auswählen
    const teamSelect = page.locator('#addTrainingModal select[name="team_ids[]"]');
    await teamSelect.selectOption({ label: testTeamName });
    
    await page.click('#addTrainingModal button:has-text("Anlegen")');

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

  test('Wöchentliches Training: Mehrere Termine einzeln abstimmen', async ({ page }) => {
    // 1. Wöchentliches Training erstellen als Admin
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');

    const testTime = '09:09';

    await page.locator('button#add-training-btn:has-text("+"):visible').click();

    // Auf wöchentlich umschalten
    await page.locator('#addTrainingModal input[name="training_type"][value="weekly"]').click();

    // Wochentag wählen (Montag = 1)
    const daySelect = page.locator('#addTrainingModal select[name="day_of_week"]');
    await daySelect.selectOption('1');

    await page.fill('#addTrainingModal input[name="training_time"]', testTime);

    const teamSelect = page.locator('#addTrainingModal select[name="team_ids[]"]');
    await teamSelect.selectOption({ label: testTeamName });

    await page.click('#addTrainingModal button:has-text("Anlegen")');

    // 2. Als Spieler einloggen und prüfen, dass mehrere Termine sichtbar sind
    await page.goto(`login.php?hash=${testPlayerHash}`);
    await page.goto('trainings.php');

    const weeklyCards = page.locator('.event-card', { hasText: testTime });
    const cardCount = await weeklyCards.count();
    expect(cardCount).toBeGreaterThanOrEqual(2);

    // 3. Beim ersten Termin zusagen
    const firstCard = weeklyCards.nth(0);
    await firstCard.locator('button[value="yes"]').click();
    // Warten bis AJAX fertig
    await expect(firstCard.locator('button[value="yes"]')).toHaveClass(/active/);

    // 4. Beim zweiten Termin absagen
    const secondCard = weeklyCards.nth(1);
    await secondCard.locator('button[value="no"]').click();
    await expect(secondCard.locator('button[value="no"]')).toHaveClass(/active/);

    // 5. Prüfen: Erster Termin hat immer noch Zusage (nicht durch zweiten überschrieben)
    await expect(firstCard.locator('button[value="yes"]')).toHaveClass(/active/);
    // Erster Termin hat KEINE Absage
    await expect(firstCard.locator('button[value="no"]')).not.toHaveClass(/active/);

    // 6. Teilnehmerliste des ersten Termins prüfen
    await firstCard.locator('.btn-attendance').click();
    await expect(page.locator('#attendanceModal')).toBeVisible();
    await expect(page.locator('#list-yes')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // 7. Teilnehmerliste des zweiten Termins prüfen
    await secondCard.locator('.btn-attendance').click();
    await expect(page.locator('#attendanceModal')).toBeVisible();
    await expect(page.locator('#list-no')).toContainText(testPlayerName);
    await page.click('#attendanceModal .close');

    // 8. Nach Reload prüfen, dass die Abstimmungen erhalten bleiben
    await page.reload();
    const reloadedCards = page.locator('.event-card', { hasText: testTime });
    await expect(reloadedCards.nth(0).locator('button[value="yes"]')).toHaveClass(/active/);
    await expect(reloadedCards.nth(1).locator('button[value="no"]')).toHaveClass(/active/);

    // 9. Aufräumen: Serie löschen als Admin
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');
    const adminCard = page.locator('.event-card', { hasText: testTime }).first();
    await adminCard.locator('.delete-btn').click();
    await page.locator('#confirmModalDeleteSeries').click();
    await expect(page.locator('.event-card', { hasText: testTime })).not.toBeVisible();
  });

  test('Zwei wöchentliche Trainingsserien: Bei beiden abstimmen', async ({ page }) => {
    const time1 = '08:08';
    const time2 = '10:10';

    // 1. Zwei wöchentliche Trainings erstellen als Admin
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');

    // Erstes wöchentliches Training (Montag)
    await page.locator('button#add-training-btn:has-text("+"):visible').click();
    await page.locator('#addTrainingModal input[name="training_type"][value="weekly"]').click();
    await page.locator('#addTrainingModal select[name="day_of_week"]').selectOption('1');
    await page.fill('#addTrainingModal input[name="training_time"]', time1);
    await page.locator('#addTrainingModal select[name="team_ids[]"]').selectOption({ label: testTeamName });
    await page.click('#addTrainingModal button:has-text("Anlegen")');

    // Zweites wöchentliches Training (Mittwoch)
    await page.locator('button#add-training-btn:has-text("+"):visible').click();
    await page.locator('#addTrainingModal input[name="training_type"][value="weekly"]').click();
    await page.locator('#addTrainingModal select[name="day_of_week"]').selectOption('3');
    await page.fill('#addTrainingModal input[name="training_time"]', time2);
    await page.locator('#addTrainingModal select[name="team_ids[]"]').selectOption({ label: testTeamName });
    await page.click('#addTrainingModal button:has-text("Anlegen")');

    // 2. Als Spieler einloggen
    await page.goto(`login.php?hash=${testPlayerHash}`);
    await page.goto('trainings.php');

    // 3. Beim ersten wöchentlichen Training (08:08) zusagen
    const card1 = page.locator('.event-card', { hasText: time1 }).first();
    await card1.locator('button[value="yes"]').click();
    await expect(card1.locator('button[value="yes"]')).toHaveClass(/active/);

    // 4. Beim zweiten wöchentlichen Training (10:10) absagen
    const card2 = page.locator('.event-card', { hasText: time2 }).first();
    await card2.locator('button[value="no"]').click();
    await expect(card2.locator('button[value="no"]')).toHaveClass(/active/);

    // 5. Prüfen: Erstes Training hat immer noch Zusage
    await expect(card1.locator('button[value="yes"]')).toHaveClass(/active/);
    await expect(card1.locator('button[value="no"]')).not.toHaveClass(/active/);

    // 6. Nach Reload prüfen
    await page.reload();
    const reloadCard1 = page.locator('.event-card', { hasText: time1 }).first();
    const reloadCard2 = page.locator('.event-card', { hasText: time2 }).first();
    await expect(reloadCard1.locator('button[value="yes"]')).toHaveClass(/active/);
    await expect(reloadCard2.locator('button[value="no"]')).toHaveClass(/active/);

    // 7. Aufräumen
    await page.goto(`login.php?hash=${testHash}`);
    await page.goto('trainings.php');
    const adminCard1 = page.locator('.event-card', { hasText: time1 }).first();
    await adminCard1.locator('.delete-btn').click();
    await page.locator('#confirmModalDeleteSeries').click();
    await page.waitForTimeout(500);
    const adminCard2 = page.locator('.event-card', { hasText: time2 }).first();
    await adminCard2.locator('.delete-btn').click();
    await page.locator('#confirmModalDeleteSeries').click();
  });
});
