const fullPageScreenshot = require('./fullPageScreenshot.js');
const {
    clickButtonWithText,
    clickElementContainingText,
    clickLinkWithText,
    clickOptionValue,
    getScreenshotPathHelper,
    goto,
    idOfInputWhoseLabelContains,
    setupBrowserAndPage,
    submit,
    type,
} = require("./common")

async function doLogin(page, email) {
    await type(page, '#ro_ro_login_username', email)
    await submit(page, '#ro_ro_login_sign_in')
}

module.exports.run = async function (argv) {
    const {outputDir, baseUrl, email, loginLink} = argv
    const screenshotPath = getScreenshotPathHelper(outputDir)

    const [browser, page] = await setupBrowserAndPage()

    try {
        await goto(page, baseUrl + 'roro')
        await fullPageScreenshot.default(page, {path: screenshotPath('1-login.png')})

        await doLogin(page, email)
        await fullPageScreenshot.default(page, {path: screenshotPath('2-check-your-email.png')})

        await goto(page, loginLink);
        await fullPageScreenshot.default(page, {path: screenshotPath('3-access-dashboard.png')})

        await clickButtonWithText(page, 'Access dashboard')
        await fullPageScreenshot.default(page, {path: screenshotPath('4-dashboard.png')})

        await clickLinkWithText(page, 'View submitted surveys')
        await fullPageScreenshot.default(page, {path: screenshotPath('5-submitted-surveys.png')})

        await clickLinkWithText(page, 'Back to dashboard')
        await clickLinkWithText(page, 'View')

        await fullPageScreenshot.default(page, {path: screenshotPath('survey/1-intro.png')})

        await clickLinkWithText(page, 'Start')
        await fullPageScreenshot.default(page, {path: screenshotPath('survey/2a-is-active.png')})

        await clickOptionValue(page, 'true')
        await fullPageScreenshot.default(page, {path: screenshotPath('survey/2b-is-active-yes.png')})

        await clickOptionValue(page, 'advanced')
        await clickButtonWithText(page, 'Continue');

        await fullPageScreenshot.default(page, {path: screenshotPath('survey/3a-bulk-data-entry-closed.png')})
        await clickElementContainingText(page, 'span', 'format description', 0, false);
        await fullPageScreenshot.default(page, {path: screenshotPath('survey/3b-bulk-data-entry-open.png')})

        await clickLinkWithText(page, 'Back')
        await clickOptionValue(page, 'manual')
        await clickButtonWithText(page, 'Continue');

        await fullPageScreenshot.default(page, {path: screenshotPath('survey/4a-manual-data-entry.png')})

        const data = {
            'Albania': '123',
            'Austria': '220',
            'Malta': '350',
            'Belgium': '10',
            'France': '943',
            'German': '821',
            'Moldova': '320',
            'Montenegro': '22',
            'Netherlands': '84',
            'Other country': '31',
            'Unaccompanied trailers': '193',
        }

        for(const [country, value] of Object.entries(data)) {
            let id = await idOfInputWhoseLabelContains(page, country)
            await type(page, id, value)
        }

        await fullPageScreenshot.default(page, {path: screenshotPath('survey/4b-filled.png')})
        await clickButtonWithText(page, 'Continue');

        await fullPageScreenshot.default(page, {path: screenshotPath('survey/5-comments.png')})
        await type(
            page,
            await idOfInputWhoseLabelContains(page, 'Comments'),
            'Most of the lorries were green'
        )
        await clickButtonWithText(page, 'Save and continue')

        await fullPageScreenshot.default(page, {path: screenshotPath('survey/6-check-data.png')})

        await clickLinkWithText(page, 'Submit survey')
        await fullPageScreenshot.default(page, {path: screenshotPath('survey/7-confirm.png')})

        await clickButtonWithText(page, 'Submit survey')
        await fullPageScreenshot.default(page, {path: screenshotPath('survey/8-submitted.png')})

        await clickLinkWithText(page, 'Back to dashboard')
        await fullPageScreenshot.default(page, {path: screenshotPath('6-dashboard-after-survey-submitted.png')})

        await clickLinkWithText(page, 'View submitted surveys')
        await fullPageScreenshot.default(page, {path: screenshotPath('7-submitted-surveys-after-survey-submitted.png')})

        await clickLinkWithText(page, 'Back to dashboard')
        await clickLinkWithText(page, 'Switch operator')
        await fullPageScreenshot.default(page, {path: screenshotPath('switch-operator/1-switch-operator.png')})

        await clickLinkWithText(page, 'Switch to Test operator - Harwich');
        await fullPageScreenshot.default(page, {path: screenshotPath('switch-operator/2-other-operator-dashboard.png')})

        await clickLinkWithText(page, 'View submitted surveys')
        await fullPageScreenshot.default(page, {path: screenshotPath('switch-operator/3-other-operator-submitted-surveys.png')})
    } catch (e) {
        console.log(e.toString(), e.stack)
        await fullPageScreenshot.default(page, {path: screenshotPath('error.png')})
        console.log('Image generated: error.png')
    }

    await browser.close()
}