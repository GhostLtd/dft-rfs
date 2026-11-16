const fullPageScreenshot = require('./fullPageScreenshot.js');
const {clickOptionValue, clickButtonWithText, getScreenshotPathHelper, goto, submit, type, setupBrowserAndPage} = require("./common")

async function doLogin(page, username, password) {
    await type(page, '#passcode_login_passcode_0', username)
    await type(page, '#passcode_login_passcode_1', password)
    await submit(page, '#passcode_login_sign_in')
}

module.exports.run = async function (argv) {
    const {outputDir, baseUrl, username, password} = argv
    const screenshotPath = getScreenshotPathHelper(outputDir)

    const [browser, page] = await setupBrowserAndPage();

    try {
        await goto(page, baseUrl)

        await fullPageScreenshot.default(page, {path: screenshotPath('landing.png')})

        await submit(page, 'a[href="/login"]')
        await fullPageScreenshot.default(page, {path: screenshotPath('login.png')})

        await doLogin(page, username, password)
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/1-intro.png')})

        await clickButtonWithText(page, 'Continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/2a-company-name.png')})

        await clickOptionValue(page, 'no')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/2b-company-name-no.png')})

        await clickOptionValue(page, 'yes')
        await clickButtonWithText(page, 'Continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/3-correspondence-details.png')})

        await type(page, '#correspondence_details_contactName', 'Mark')
        await type(page, '#correspondence_details_contactTelephone', '01111 222333')
        await type(page, '#correspondence_details_contactEmail', 'mark@example.com')
        await clickButtonWithText(page, 'Continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/4a-correspondence-address.png')})

        await clickOptionValue(page, 'no')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/4b-correspondence-address-no.png')})

        await clickOptionValue(page, 'yes')
        await clickButtonWithText(page, 'Continue')

        // HGV details screen
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/5-hgv-details.png')})

        await type(page, '#hgv_details_totalHgvCount', '35')
        await type(page, '#hgv_details_internationalJourneyHgvCount', '25')
        await type(page, '#hgv_details_annualHgvJourneyEstimate', '110')
        await clickButtonWithText(page, 'Continue')

        // LGV details screen
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/6-lgv-details.png')})

        await type(page, '#lgv_details_totalLgvCount', '10')
        await type(page, '#lgv_details_internationalJourneyLgvCount', '5')
        await type(page, '#lgv_details_annualLgvJourneyEstimate', '30')
        await clickButtonWithText(page, 'Continue')

        // Business details screen
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/7-business-details.png')})

        await clickOptionValue(page, '50-249')
        await clickButtonWithText(page, 'Save and continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/8-summary.png')})

        await clickButtonWithText(page, 'Submit pre-enquiry')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/9-success.png')})
    } catch (e) {
        console.log(e.toString(), e.stack)
        await fullPageScreenshot.default(page, {path: screenshotPath('error.png')})
        console.log('Image generated: error.png')
        // Rethrown so the run fails (and bin/console rfs:dev:screenshots with it), keeping what was captured
        throw e
    } finally {
        await browser.close()
    }
}