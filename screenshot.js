const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.setViewport({ width: 375, height: 812 }); // Mobile viewport
  await page.goto('http://localhost/Github/AURORA%20HOTEL%20PLAZA/DOANH%20NGHI%E1%BB%86P/Aurorahotelplaza.com/blog-detail.php?slug=top-10-khach-san-bien-hoa', { waitUntil: 'networkidle2' });
  await page.screenshot({ path: 'local-screenshot.png' });
  await browser.close();
})();
