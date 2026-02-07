import { test, expect } from '@playwright/test';

test.describe('Teilen-Button Tests', () => {
    const testHash = 'HYmpZn_wlAwIaodR6F48SQ';

    test('Teilen-Button ist im Header sichtbar und kopiert Link', async ({ page, context }) => {
        // Berechtigungen für Clipboard setzen
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);

        // Login
        await page.goto(`login.php?hash=${testHash}`);
        
        // Prüfen ob der Button da ist
        const shareBtn = page.locator('.share-btn');
        await expect(shareBtn).toBeVisible();
        
        // Klick auf den Button
        await shareBtn.click();
        
        // Prüfen ob sich die Farbe/Inhalt kurz ändert (Feedback)
        // Da es schnell geht, prüfen wir eher auf das Resultat im Clipboard
        
        // In Playwright kann man das Clipboard auslesen
        const clipboardText = await page.evaluate(() => navigator.clipboard.readText());
        expect(clipboardText).toContain('login.php?hash=' + testHash);
    });
});
