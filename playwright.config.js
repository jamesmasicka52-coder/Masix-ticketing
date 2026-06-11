/** @type {import('@playwright/test').PlaywrightTestConfig} */
const config = {
  testDir: './tests',
  timeout: 60_000,
  retries: 0,
  use: {
    headless: true,
    viewport: { width: 1280, height: 800 },
    actionTimeout: 20_000,
    ignoreHTTPSErrors: true,
    screenshot: 'off',
    baseURL: process.env.BASE_URL || 'http://localhost/SYSTM'
  }
};

module.exports = config;
