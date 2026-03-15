import { test, expect, Page } from '@playwright/test';
import jsQR from 'jsqr';

const adminHash = 'testHash';

async function login(page: Page) {
  await page.goto(`login.php?hash=${adminHash}`);
  await page.waitForURL(/.*\/(games|trainings|teams|players)\.php/);
}

async function decodeQrFromModal(page: Page): Promise<string> {
  const container = page.locator('#qrCodeContainer');
  // QRCode.js erzeugt entweder ein canvas oder ein img Element
  // Warten bis QR-Code generiert wurde (img oder canvas vorhanden)
  await expect(container.locator('img, canvas').first()).toBeAttached();

  const qrData = await page.evaluate(() => {
    const container = document.getElementById('qrCodeContainer')!;
    const canvas = container.querySelector('canvas');
    const img = container.querySelector('img') as HTMLImageElement | null;

    // Canvas zum Auslesen der Pixeldaten erstellen
    const c = document.createElement('canvas');
    const ctx = c.getContext('2d')!;

    if (canvas) {
      c.width = canvas.width;
      c.height = canvas.height;
      ctx.drawImage(canvas, 0, 0);
    } else if (img) {
      c.width = img.naturalWidth || img.width;
      c.height = img.naturalHeight || img.height;
      ctx.drawImage(img, 0, 0, c.width, c.height);
    } else {
      throw new Error('Kein QR-Code Element gefunden');
    }

    const imageData = ctx.getImageData(0, 0, c.width, c.height);
    return {
      data: Array.from(imageData.data),
      width: imageData.width,
      height: imageData.height
    };
  });

  const result = jsQR(new Uint8ClampedArray(qrData.data), qrData.width, qrData.height);
  expect(result).not.toBeNull();
  return result!.data;
}

test.describe('QR-Code Tests', () => {

  test('Spieler QR-Code enthält den Login-Link', async ({ page }) => {
    await login(page);
    await page.goto('players.php');

    // Erste Spielerkarte mit Login-Link finden
    const firstCard = page.locator('.event-card').filter({ has: page.locator('.copy-link-row') }).first();
    await expect(firstCard).toBeVisible();

    // Login-Link-Wert auslesen
    const loginInput = firstCard.locator('.copy-link-row input[type="text"]');
    const expectedUrl = await loginInput.inputValue();
    expect(expectedUrl).toContain('login.php?hash=');

    // QR-Code-Button klicken
    await firstCard.locator('button[title="QR-Code anzeigen"]').click();

    // Modal sichtbar
    await expect(page.locator('#qrCodeModal')).toBeVisible();

    // QR-Code dekodieren und prüfen
    const decodedUrl = await decodeQrFromModal(page);
    expect(decodedUrl).toBe(expectedUrl);

    // Modal schließen
    await page.locator('#qrCodeModal .close').click();
    await expect(page.locator('#qrCodeModal')).not.toBeVisible();
  });

  test('Mannschaft QR-Code enthält den Anmeldelink', async ({ page }) => {
    await login(page);
    await page.goto('teams.php');

    // Erste Mannschaftskarte mit Anmeldelink finden
    const firstCard = page.locator('.event-card').filter({ has: page.locator('.reg-link-row') }).first();
    await expect(firstCard).toBeVisible();

    // Anmeldelink-Wert auslesen
    const regInput = firstCard.locator('.reg-link-row input[type="text"]');
    const expectedUrl = await regInput.inputValue();
    expect(expectedUrl).toContain('register');

    // QR-Code-Button klicken
    await firstCard.locator('button[title="QR-Code anzeigen"]').click();

    // Modal sichtbar
    await expect(page.locator('#qrCodeModal')).toBeVisible();

    // QR-Code dekodieren und prüfen
    const decodedUrl = await decodeQrFromModal(page);
    expect(decodedUrl).toBe(expectedUrl);

    // Modal schließen
    await page.locator('#qrCodeModal .close').click();
    await expect(page.locator('#qrCodeModal')).not.toBeVisible();
  });

  test('QR-Code Modal zeigt den Spielernamen als Titel', async ({ page }) => {
    await login(page);
    await page.goto('players.php');

    const firstCard = page.locator('.event-card').filter({ has: page.locator('.copy-link-row') }).first();
    const playerName = await firstCard.locator('.card-header h3').innerText();

    await firstCard.locator('button[title="QR-Code anzeigen"]').click();
    await expect(page.locator('#qrCodeModal')).toBeVisible();
    await expect(page.locator('#qrCodeTitle')).toHaveText(playerName);

    await page.locator('#qrCodeModal .close').click();
  });

  test('QR-Code Modal zeigt den Mannschaftsnamen als Titel', async ({ page }) => {
    await login(page);
    await page.goto('teams.php');

    const firstCard = page.locator('.event-card').filter({ has: page.locator('.reg-link-row') }).first();
    const teamName = await firstCard.locator('.card-header h3').innerText();

    await firstCard.locator('button[title="QR-Code anzeigen"]').click();
    await expect(page.locator('#qrCodeModal')).toBeVisible();
    await expect(page.locator('#qrCodeTitle')).toHaveText(teamName);

    await page.locator('#qrCodeModal .close').click();
  });
});
