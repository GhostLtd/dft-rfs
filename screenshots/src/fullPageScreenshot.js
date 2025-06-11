module.exports.default = async function (page, options) {
    // we sometimes get blank pages
    await new Promise(r => setTimeout(r, 200));
    await page.screenshot({path: options.path, fullPage: true});
};
