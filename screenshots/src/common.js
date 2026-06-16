const fs = require('fs')
const path = require('path')
const puppeteer = require("puppeteer");

module.exports.setupBrowserAndPage = async () => {
    const browser = await puppeteer.launch({
        args: [
            '--no-sandbox',
            '--disable-back-forward-cache',
        ],
        executablePath: '/usr/local/bin/google-chrome',
        acceptInsecureCerts: true,
        // headless: false,
    });
    const page = await browser.newPage();
    await page.setCacheEnabled(false);
    await page.setViewport({width: 1003, height: 200});

    return [browser, page];
}

module.exports.getScreenshotPathHelper = (outputDir) => {
    return (relativePath) => {
        const fullPath = outputDir + relativePath
        const dir = path.dirname(fullPath)
        fs.mkdirSync(dir, {recursive: true})
        return fullPath
    }
}

const waitForOptions = {
    timeout: 20000,
    waitUntil: "domcontentloaded",
}

module.exports.waitForOptions = waitForOptions

async function type(page, fieldId, value) {
    const $field = await page.$(fieldId)
    await $field.type(value)
}

module.exports.type = type

module.exports.goto = async function(page, url) {
    page.goto(url);
    await page.waitForNavigation(waitForOptions)
    await checkForSuccessfulResult(page)
}

module.exports.submit = async function(page, selector) {
    page.click(selector);
    await page.waitForNavigation(waitForOptions)
    await checkForSuccessfulResult(page)
}

async function checkForSuccessfulResult(page) {
    let content = await page.content();
    if (content.indexOf('there is a problem with the service') !== -1) {
        throw new Error('Service error: ' + page.url());
    }
}

const predicate = function(options)
{
    const predicates =  []

    if (options.text !== undefined) {
        predicates.push(`text() = '${options.text}'`)
    }

    if (options.textContains !== undefined) {
        predicates.push(`contains(text(), '${options.textContains}')`)
    }

    if (options.class !== undefined) {
        predicates.push(`contains(concat(' ', normalize-space(@class), ' '), ' ${options.class} ')`)
    }

    return predicates.length === 0 ?
        '' :
        `[${predicates.join(' and ')}]`
}

module.exports.predicate = predicate

const findElement = async function(page, expression, index=0) {
    const elements = await page.$$('xpath/' + expression)

    if (!elements[index]) {
        throw new Error(`Element not found for ${expression}[${index}]`);
    }

    return elements[index];
}

module.exports.findElement = findElement

const clickLink = async function(page, expression, index = 0, waitForNatigation = true) {
    let element = await findElement(page, expression, index)

    await element.click()

    if (waitForNatigation) {
        await page.waitForNavigation(waitForOptions)
        await checkForSuccessfulResult(page)
    }
}

module.exports.clickLink = clickLink

module.exports.clickElementWithText = async function(page, elementType, text, index = 0, waitForNavigation = true) {
    await clickLink(page, `//${elementType}${predicate({text})}`, index, waitForNavigation)
}

module.exports.clickElementContainingText = async function(page, elementType, textContains, index = 0, waitForNavigation = true) {
    await clickLink(page, `//${elementType}${predicate({textContains})}`, index, waitForNavigation)
}

module.exports.clickLinkWithText = async function(page, text) {
    await clickLink(page, `//a${predicate({text})}`)
}

module.exports.clickLinkContainingText = async function(page, textContains) {
    await clickLink(page, `//a${predicate({textContains})}`)
}

module.exports.clickButtonWithText = async function(page, text) {
    await clickLink(page, `//button${predicate({text})}`)
}

module.exports.enterDate = async function(page, id_prefix, year, month, day) {
    await type(page, `${id_prefix}_day`, day)
    await type(page, `${id_prefix}_month`, month)
    await type(page, `${id_prefix}_year`, year)
}

module.exports.clickOptionValue = async function(page, option_value, context_id) {
    let expression = context_id !== undefined
        ? `//*[@id="${context_id}"]//input[@value="${option_value}"]`
        : `//input[@value="${option_value}"]`;
    let element = await findElement(page, expression, 0);
    await element.click();
}

module.exports.idOfInputWhoseLabelContains = async function(page, textContains, index = 0) {
    let expression = `//label${predicate({textContains})}`;
    let element = await findElement(page, expression, index);
    return await element.evaluate(e => '#' + e.getAttribute('for'));
}

