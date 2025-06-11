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
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/5-vehicle-details.png')})

        await type(page, '#vehicle_questions_totalVehicleCount', '35')
        await type(page, '#vehicle_questions_internationalJourneyVehicleCount', '25')
        await type(page, '#vehicle_questions_annualJourneyEstimate', '110')
        await clickButtonWithText(page, 'Continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/6-employee-count-and-international-journeys.png')})

        await clickOptionValue(page, '50-249')
        await clickButtonWithText(page, 'Save and continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/7-summary.png')})

        await clickButtonWithText(page, 'Submit pre-enquiry')
        await fullPageScreenshot.default(page, {path: screenshotPath('pre-enquiry/8-success.png')})
    } catch (e) {
        console.log(e.toString(), e.stack)
        await fullPageScreenshot.default(page, {path: screenshotPath('error.png')})
        console.log('Image generated: error.png')
    }

    await browser.close()
}