const fullPageScreenshot = require('./fullPageScreenshot.js');
const {clickButtonWithText, setupBrowserAndPage} = require("./common");
const {clickLink} = require("./common");
const {predicate} = require("./common");
const {clickLinkWithText, enterDate, getScreenshotPathHelper, goto, submit, type, waitForOptions} = require("./common")

async function doLogin(page, username, password) {
    await type(page, '#passcode_login_passcode_0', username)
    await type(page, '#passcode_login_passcode_1', password)
    await submit(page, '#passcode_login_sign_in')
}

async function doInitialContact(page) {
    await type(page, '#contact_details_contactName', 'Mark')
    await type(page, '#contact_details_contactTelephone', '01111 222222')
    await type(page, '#contact_details_contactEmail', 'mark@example.com')
    await submit(page, '#contact_details_buttons_continue')
}

async function doEstimatedTrips(page, hgvValue, lgvValue) {
    await type(page, '#number_of_trips_annualInternationalHGVJourneyCount', hgvValue)
    await type(page, '#number_of_trips_annualInternationalLGVJourneyCount', lgvValue)
    await submit(page, '#number_of_trips_buttons_continue')
}

async function doHaulStatus(page, option) {
    await page.click('#activity_status_activityStatus_' + option)
    await submit(page, '#activity_status_buttons_continue')
}

async function doVehicleWeights(page) {
    await type(page, '#vehicle_weight_grossWeight', '40000')
    await type(page, '#vehicle_weight_carryingCapacity', '26000')
    await submit(page, '#vehicle_weight_buttons_continue')
}

async function doVehicleTrailerChoice(page, option) {
    await page.click('#vehicle_trailer_configuration_trailerConfiguration_' + option)
    await submit(page, '#vehicle_trailer_configuration_buttons_continue')
}

async function doVehicleAxleChoice(page, option) {
    await page.click('#vehicle_axle_configuration_axleConfiguration_' + option)
    await submit(page, '#vehicle_axle_configuration_buttons_continue')
}

async function doVehicleBodyChoice(page, option) {
    await page.click('#vehicle_body_bodyType_' + option)
    await submit(page, '#vehicle_body_buttons_continue')
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
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/1-intro.png')})

        await submit(page, '#form_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/2-contact.png')})

        await doInitialContact(page)
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/3a-activity-status.png')})

        // Show "No road haulage" option selected - reveals the extra "other" text field
        await page.click('#activity_status_activityStatus_2')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/3b-activity-status-no-road-haulage.png')})

        // Path 1: Ceased trading - goes straight to summary
        await goto(page, baseUrl + 'international-survey/initial-details/activity-status')
        await doHaulStatus(page, '0')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/4a-summary-ceased-trading.png')})

        // Path 2: Only domestic work - goes straight to summary
        await goto(page, baseUrl + 'international-survey/initial-details/activity-status')
        await doHaulStatus(page, '1')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/4b-summary-only-domestic-work.png')})

        // Path 3: No road haulage - fill in "other" field and show summary
        await goto(page, baseUrl + 'international-survey/initial-details/activity-status')
        await page.click('#activity_status_activityStatus_2')
        await type(page, '#activity_status_activityStatusOther', 'Passenger transport only')
        await submit(page, '#activity_status_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/4c-summary-no-road-haulage.png')})

        // Path 4: Still active - goes through business details and number of trips
        await goto(page, baseUrl + 'international-survey/initial-details/activity-status')
        await doHaulStatus(page, '3') // still-active
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/5-business-details.png')})

        await page.click('#business_details_numberOfEmployees_1')
        await type(page, '#business_details_businessNature', 'Haulage')
        await submit(page, '#business_details_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/6-number-of-trips.png')})

        // Capture summary with zero HGV trips (shows "Submit survey" button instead of continuing to add vehicles)
        await doEstimatedTrips(page, '0', '20')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/7b-summary-zero-hgv-trips.png')})

        // Use "Change" link on summary page to go back to number-of-trips and set proper values
        await clickLink(page, `//a[contains(@href, 'number-of-trips')]`)
        // Clear existing values and enter new ones
        await page.$eval('#number_of_trips_annualInternationalHGVJourneyCount', el => el.value = '')
        await page.$eval('#number_of_trips_annualInternationalLGVJourneyCount', el => el.value = '')
        await doEstimatedTrips(page, '60', '20')
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/7a-summary.png')})

        await submit(page, '#confirmation_submit')
        await fullPageScreenshot.default(page, {path: screenshotPath('dashboard/1a-empty.png')})

        await clickLinkWithText(page, 'Submit survey')
        await fullPageScreenshot.default(page, {path: screenshotPath('final-details/1-early-response.png')})

        await page.click('#early_response_is_correct_0');
        await submit(page, '#early_response_buttons_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final-details/2-why-empty-survey.png')})

        await goto(page, baseUrl + 'international-survey/add-vehicle/vehicle-registration')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/1-details.png')})

        await page.click('#vehicle_details_operationType_1')
        await type(page, '#vehicle_details_registrationMark', 'AB01 DEF')
        await submit(page, '#vehicle_details_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/2-survey-period.png')})

        await page.click('#confirm_dates_confirm_0')
        await submit(page, '#confirm_dates_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/3-trailer-configuration.png')})

        await doVehicleTrailerChoice(page, '1')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/4a-rigid.png')})

        await page.goBack(waitForOptions)
        await doVehicleTrailerChoice(page, '2')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/4b-rigid-with-trailer.png')})

        await page.goBack(waitForOptions)
        await doVehicleTrailerChoice(page, '0')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/4c-artic.png')})

        await doVehicleAxleChoice(page, '0')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/5-body-type.png')})

        await doVehicleBodyChoice(page, '3')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/6-vehicle-weight-and-capacity.png')})

        await doVehicleWeights(page)
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/7a-vehicle-summary.png')})

        await submit(page, 'a[class~="govuk-button--warning"]')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/8-delete.png')})

        await goto(page, baseUrl + 'international-survey');
        await fullPageScreenshot.default(page, {path: screenshotPath('dashboard/1b-one-vehicle.png')})

        // Trip
        await clickLinkWithText(page, 'View')
        await clickLinkWithText(page, 'Add trip')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/1-start-and-end.png')})

        await type(page, '#origin_and_destination_origin', 'Sandwich')
        await type(page, '#origin_and_destination_destination', 'Portsmouth')
        await submit(page, '#origin_and_destination_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/2-trip-dates.png')})

        const dateElement = await page.$('#main-content > div.govuk-grid-row > div.govuk-grid-column-one-third > p:nth-child(3) > span:nth-child(1)')
        const dateText = await page.evaluate(e => e.textContent, dateElement)
        const surveyStartDate = new Date(dateText)

        await enterDate(page, '#dates_outboundDate',
            '' + surveyStartDate.getFullYear(),
            '' + (surveyStartDate.getMonth() + 1),
            '' + surveyStartDate.getDate())

        await enterDate(page, '#dates_returnDate',
            '' + surveyStartDate.getFullYear(),
            '' + (surveyStartDate.getMonth() + 1),
            '' + surveyStartDate.getDate())

        await submit(page, '#dates_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/3a-outbound-trip.png')})

        await page.click('#outbound_ports_and_cargo_state_wasAtCapacity_0')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/3b-outbound-trip-at-capacity.png')})

        await page.click('#outbound_ports_and_cargo_state_wasAtCapacity_1')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/3c-outbound-trip-not-at-capacity.png')})

        await page.click('#outbound_ports_and_cargo_state_wasEmpty_1')
        await page.select('#outbound_ports_and_cargo_state_ports', '62')
        await submit(page, '#outbound_ports_and_cargo_state_buttons_continue')

        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/4a-return-trip.png')})

        await page.click('#return_ports_and_cargo_state_wasAtCapacity_1')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/4c-return-trip-not-at-capacity.png')})

        await page.click('#return_ports_and_cargo_state_wasAtCapacity_0')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/4b-return-trip-at-capacity.png')})

        await page.click('#return_ports_and_cargo_state_wasLimitedBy_0')
        await page.select('#return_ports_and_cargo_state_ports', '8')
        await submit(page, '#return_ports_and_cargo_state_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/5a-axle-configuration.png')})

        await page.click('#swapped_trailer_isSwappedTrailer_0')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/5b-axle-configuration-change.png')})

        await page.click('#swapped_trailer_isSwappedTrailer_1')
        await submit(page, '#swapped_trailer_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/6a-body-type.png')})

        await page.click('#changed_body_type_isChangedBodyType_0')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/6b-body-type-change.png')})

        await page.click('#changed_body_type_bodyType_0')
        await submit(page, '#changed_body_type_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/7-vehicle-weight-change.png')})

        await type(page, '#vehicle_weight_grossWeight', '30000')
        await type(page, '#vehicle_weight_carryingCapacity', '23000')
        await submit(page,'#vehicle_weight_buttons_continue')
        // await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/8-trip-distance.png')})
        //
        // await page.click('#distance_roundTripDistance_unit_0')
        // await type(page, '#distance_roundTripDistance_value', '350')
        // await submit(page, '#distance_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/9-countries-travelled-through.png')})

        await page.click('#countries_transitted_countriesTransitted_0')
        await page.click('#countries_transitted_countriesTransitted_1')
        await page.click('#countries_transitted_countriesTransitted_2')
        await page.click('#countries_transitted_countriesTransitted_3')
        await submit(page, '#countries_transitted_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/10a-trip-summary.png')})

        await submit(page, 'a[class~="govuk-button--warning"]')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/11-delete.png')})

        await page.goBack(waitForOptions)

        // Loading action
        await clickLinkWithText(page, 'Add loading action')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/1-loading-or-unloading.png')})

        await page.click('#place_loading_0')
        await page.click('#place_place_country_country_0')
        await type(page, '#place_place_name', 'Sandwich')
        await submit(page, '#place_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/2a-description-of-goods.png')})

        await page.click('#goods_description_goodsDescription_2')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/2b-description-of-goods-specific.png')})

        await type(page, '#goods_description_goodsDescriptionOther', 'Sandwiches')
        await submit(page, '#goods_description_buttons_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/3-weight-of-goods.png')})

        await type(page, '#goods_loaded_weight_weightOfGoods', '800')
        await submit(page, '#goods_loaded_weight_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/4a-hazardous.png')})

        await page.click('#hazardous_goods_isHazardousGoods_0')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/4a-hazardous-yes.png')})

        await page.click('#hazardous_goods_hazardousGoodsCode_12')
        await submit(page, '#hazardous_goods_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/5-how-goods-carried.png')})

        await page.click('#cargo_type_cargoTypeCode_7')
        await submit(page, '#cargo_type_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/6-any-other-loadings-or-unloadings.png')})

        await page.click('#add_another_confirm_1')
        await submit(page, '#add_another_buttons_continue')

        // Unloading action
        await clickLinkWithText(page, 'Add loading or unloading action')
        await fullPageScreenshot.default(page, {path: screenshotPath('unloading-action/1-loading-or-unloading.png')})

        await page.click('#place_loading_1')
        await page.click('#place_place_country_country_4')
        await type(page, '#place_place_name', 'Berlin')
        await submit(page, '#place_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('unloading-action/2-goods-unloaded.png')})

        await page.click('#loading_place_loadingAction_0')
        await submit(page, '#loading_place_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('unloading-action/3a-weight-unloaded.png')})

        await page.click('#goods_unloaded_weight_weightUnloadedAll_1')
        await fullPageScreenshot.default(page, {path: screenshotPath('unloading-action/3b-weight-unloaded-partial.png')})

        await type(page, '#goods_unloaded_weight_weightOfGoods', '300')
        await submit(page, '#goods_unloaded_weight_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/4-any-other-loadings-or-unloadings.png')})

        await page.click('#add_another_confirm_1')
        await submit(page, '#add_another_buttons_continue')
        await fullPageScreenshot.default(page, {path: screenshotPath('trip-details/10b-trip-summary-with-loadings-and-unloadings.png')})

        await clickLinkWithText(page, 'Re-order actions')
        await fullPageScreenshot.default(page, {path: screenshotPath('reorder-actions/1-reorder.png')})

        await page.goBack(waitForOptions)
        await clickLink(page, `//td${predicate({class: 'govuk-table__cell', text: 'Sandwich, United Kingdom'})}/..//a`)
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/7-loading-action-details.png')})

        await clickLinkWithText(page, 'Delete action')
        await fullPageScreenshot.default(page, {path: screenshotPath('loading-action/8-delete-loading-action.png')})

        await page.goBack(waitForOptions)
        await page.goBack(waitForOptions)

        await clickLink(page, `//td${predicate({class: 'govuk-table__cell', text: 'Berlin, Germany'})}/..//a`)
        await fullPageScreenshot.default(page, {path: screenshotPath('unloading-action/5-unloading-action-details.png')})

        await clickLinkWithText(page, 'Delete action')
        await fullPageScreenshot.default(page, {path: screenshotPath('unloading-action/6-delete-unloading-action.png')})

        await page.goBack(waitForOptions)
        await page.goBack(waitForOptions)

        await clickLinkWithText(page, 'Trip complete')
        await fullPageScreenshot.default(page, {path: screenshotPath('vehicle-details/7b-vehicle-summary-with-trip.png')})

        await clickLinkWithText(page, 'Vehicle complete')
        await clickLinkWithText(page, 'Submit survey')
        await page.click('#early_response_is_correct_0');
        await submit(page, '#early_response_buttons_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final-details/3-loading-without-unloading.png')})

        await page.click('#loading_without_unloading_is_correct_0');
        await submit(page, '#loading_without_unloading_buttons_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final-details/4-correspondence-details.png')})

        await clickButtonWithText(page, 'Confirm and submit survey')
        await fullPageScreenshot.default(page, {path: screenshotPath('final-details/4-success.png')})
    } catch (e) {
        console.log(e.toString(), e.stack)
        await fullPageScreenshot.default(page, {path: screenshotPath('error.png')})
        console.log('Image generated: error.png')
    }

    await browser.close()
}