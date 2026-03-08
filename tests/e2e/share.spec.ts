import { test, expect } from '@playwright/test';

test.describe('Teilen-Button Tests', () => {
    const testHash = 'testHash';

    test('Teilen-Button ist im Header sichtbar und kopiert Link', async ({ page, context, browserName }) => {
        // Berechtigungen für Clipboard setzen (Firefox unterstützt dies nicht via grantPermissions)
        if (browserName !== 'firefox' && browserName !== 'webkit') {
            await context.grantPermissions(['clipboard-read', 'clipboard-write']);
        }

        // Login
        await page.goto(`login.php?hash=${testHash}`);
        
        // Prüfen ob der Button da ist
        const shareBtn = page.locator('.copy-link-btn');
        await expect(shareBtn).toBeVisible();
        
        // Klick auf den Button
        await shareBtn.click();
        
        // Prüfen ob sich die Farbe/Inhalt kurz ändert (Feedback)
        // Da es schnell geht, prüfen wir eher auf das Resultat im Clipboard
        
        // In Playwright kann man das Clipboard auslesen
        if (browserName !== 'firefox' && browserName !== 'webkit') {
            const clipboardText = await page.evaluate(() => navigator.clipboard.readText());
            expect(clipboardText).toContain('login.php?hash=' + testHash);
        }
    });
});
