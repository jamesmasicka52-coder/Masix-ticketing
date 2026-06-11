Visual layout tests (Playwright)

Prerequisites
- Node.js (16+)
- The webapp running locally (XAMPP) at http://localhost/SYSTM

Install dependencies:

```bash
npm install
npx playwright install
```

Run the visual capture script (headless):

```bash
npm run visual:run
```

Or run headed to watch the browser:

```bash
npm run visual:run:headed
```

Screenshots will be saved to the `visual-screenshots/` folder grouped by viewport (e.g. `visual-screenshots/1920x1080/home.png`).

Notes:
- The test does not assert pixel-diffs — it captures baseline screenshots for manual review or to plug into a CI visual-diff workflow.
- To change the pages or viewports, edit `tests/visual.spec.js`.
