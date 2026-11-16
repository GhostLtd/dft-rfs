const fullPageScreenshot = require('./fullPageScreenshot.js');
const {addDayStop} = require("./domestic-day-stop");
const {clickButtonWithText, clickLink, clickLinkWithText, clickOptionValue, getScreenshotPathHelper, goto, submit, type, waitForOptions,
    setupBrowserAndPage
} = require("./common");

const LOCATIONS = {
    'place-name': {
        origin: 'Flightston',
        destination: 'Destinstown',
        furthestStop: "Land's end",
        dayStopSecond: 'Secondfield',
        dayStopStart: 'Starttown',
    },
    'postcode': {
        origin: 'SO14 0YG',
        destination: 'PO1 2UP',
        furthestStop: 'EX34 9EQ',
        dayStopSecond: 'BH1 1AA',
        dayStopStart: 'BS1 5TR',
    }
};

async function doLogin(page, username, password) {
    await type(page, '#passcode_login_passcode_0', username);
    await type(page, '#passcode_login_passcode_1', password);
    await submit(page, '#passcode_login_sign_in');
}

async function doInitialContact(page) {
    await type(page, '#contact_details_contactName', 'Mark');
    await type(page, '#contact_details_contactTelephone', '01111 222222');
    await type(page, '#contact_details_contactEmail', 'mark@example.com');
    await submit(page, '#contact_details_button_group_continue');
}

async function doDriverVacancyQuestions(page, screenshotPath) {
    await page.click('#drivers_and_vacancies_hasVacancies_1');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/5b-driver-questions-vacancy-no.png')});
    await page.click('#drivers_and_vacancies_hasVacancies_0');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/5a-driver-questions-vacancy-yes.png')});
    await submit(page, '#drivers_and_vacancies_button_group_continue');

    await page.click('#deliveries_hasMissedDeliveries_0');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/6a-hgv-questions-missing-deliveries-no.png')});
    await page.click('#deliveries_hasMissedDeliveries_1');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/6b-hgv-questions-missing-deliveries-no-changed-supplier.png')});
    await page.click('#deliveries_hasMissedDeliveries_2');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/6c-hgv-questions-missing-deliveries-yes.png')});
    await submit(page, '#deliveries_button_group_continue');

    await page.click('#wages_haveWagesIncreased_0');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/7a-wage-increases-yes.png')});
    await page.click('#wages_haveWagesIncreased_1');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/7b-wage-increases-no.png')});
    await page.click('#wages_haveWagesIncreased_2');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/7c-wage-increases-planning-to.png')});
    await submit(page, '#wages_button_group_continue');

    await page.click('#bonuses_hasPaidBonus_0');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/8a-bonuses-yes.png')});
    await page.click('#bonuses_hasPaidBonus_1');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/8b-bonuses-no.png')});
    await page.click('#bonuses_hasPaidBonus_2');
    screenshotPath && await fullPageScreenshot.default(page, {path: screenshotPath('final/8c-bonuses-planning-to.png')});
    await submit(page, '#bonuses_button_group_continue');
}

async function doInitialPossesion(page, option, exemptOption='1') {
    await page.click('#in_possession_of_vehicle_isInPossessionOfVehicle_' + option);

    if (option === '0') {
        await page.click('#in_possession_of_vehicle_isExemptVehicleType_' + exemptOption);
    }

    await submit(page, '#in_possession_of_vehicle_button_group_continue');
}

async function doInitialSold(page) {
    await type(page, '#sold_details_soldDate_day', '01');
    await type(page, '#sold_details_soldDate_month', '04');
    await type(page, '#sold_details_soldDate_year', '2020');
    await type(page, '#sold_details_newOwnerName', 'Ollie Ownerston');
    await type(page, '#sold_details_newOwnerEmail', 'bob@example.com');

    await type(page, '#sold_details_newOwnerAddress_line1', '10 Fictional Road');
    await type(page, '#sold_details_newOwnerAddress_line3', 'Townston');
    await type(page, '#sold_details_newOwnerAddress_line4', 'West Sussex');
    await type(page, '#sold_details_newOwnerAddress_postcode', 'PO20 1AB');

    await submit(page, '#sold_details_button_group_continue');
}

async function doInitialScrapped(page) {
    await type(page, '#scrapped_details_scrappedDate_day', '01');
    await type(page, '#scrapped_details_scrappedDate_month', '04');
    await type(page, '#scrapped_details_scrappedDate_year', '2020');

    await submit(page, '#scrapped_details_button_group_continue');
}

async function doInitialHire(page) {
    await type(page, '#hiree_details_hireeName', 'Hugo Hireston');
    await type(page, '#hiree_details_hireeEmail', 'hugo@example.com');

    await type(page, '#hiree_details_hireeAddress_line1', '20 Temporal Drive');
    await type(page, '#hiree_details_hireeAddress_line3', 'Hirchester');
    await type(page, '#hiree_details_hireeAddress_line4', 'West Sussex');
    await type(page, '#hiree_details_hireeAddress_postcode', 'PO20 2AB');

    await submit(page, '#hiree_details_button_group_continue');
}

async function doBusinessDetails(page) {
    await type(page, '#business_details_contactBusinessName', 'Form test ltd');
    await page.click('#business_details_numberOfEmployees_1');
    await page.click('#business_details_operationType_1');
    await type(page, '#business_details_businessNature', 'Haulage');

    await submit(page, '#business_details_button_group_continue');
}

async function doBusinessVehicleWeights(page) {
    await type(page, '#vehicle_weights_grossWeight', '40000');
    await type(page, '#vehicle_weights_carryingCapacity', '26000');

    await submit(page, '#vehicle_weights_button_group_continue');
}

async function doBusinessTrailerChoice(page, option) {
    await page.click('#vehicle_trailer_configuration_trailerConfiguration_' + option);
    await submit(page, '#vehicle_trailer_configuration_button_group_continue');
}

async function doBusinessAxleChoice(page, option) {
    await page.click('#vehicle_axle_configuration_axleConfiguration_' + option);
    await submit(page, '#vehicle_axle_configuration_button_group_continue');
}

async function doBusinessBodyChoice(page, option) {
    await page.click('#vehicle_body_bodyType_' + option);
    await submit(page, '#vehicle_body_button_group_continue');
}

async function doNiBorderCrossingScreenshots(argv) {
    const {outputDir, baseUrl, username, password} = argv;
    const screenshotPath = getScreenshotPathHelper(outputDir);
    const locationMode = argv.locationMode || argv['location-mode'] || 'place-name';

    const [browser, page] = await setupBrowserAndPage();

    try {
        // Login
        await goto(page, baseUrl);
        await submit(page, 'a[href="/login"]');
        await doLogin(page, username, password);

        // Fast-track initial details (no screenshots)
        await submit(page, '#form_button_group_continue'); // intro
        await doInitialContact(page); // contact
        await doInitialPossesion(page, '0', '1'); // possession: yes, not exempt

        // Fast-track business details (no screenshots)
        await goto(page, baseUrl + 'domestic-survey/vehicle-and-business-details');
        await doBusinessDetails(page);
        await doBusinessTrailerChoice(page, '2'); // artic
        await doBusinessAxleChoice(page, '2');
        await doBusinessBodyChoice(page, '3');
        await doBusinessVehicleWeights(page);

        // Day-stop border crossing screenshot
        await goto(page, baseUrl + 'domestic-survey/day-1/add');
        await page.click('#number_of_stops_hasMoreThanFiveStops_0'); // 5-or-fewer
        await submit(page, '#number_of_stops_buttons_continue');
        await submit(page, '#form_button_group_continue'); // intro

        await page.click('#origin_goodsLoaded_1'); // not loaded
        await type(page, '#origin_originLocation', 'Belfast');
        if (locationMode === 'postcode') {
            await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/2-NI_ONLY-journey-start.png')});
        }
        await submit(page, '#origin_button_group_continue');

        await page.click('#destination_goodsUnloaded_1'); // not unloaded
        await type(page, '#destination_destinationLocation', 'Dublin');
        if (locationMode === 'postcode') {
            await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/3-NI_ONLY-journey-end.png')});
        }
        await submit(page, '#destination_button_group_continue');

        await page.click('#border_crossing_borderCrossed_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/4-NI_ONLY-border-crossing.png')});

        // Submit through rest of wizard
        await type(page, '#border_crossing_borderCrossingLocation', 'Newry');
        await submit(page, '#border_crossing_button_group_continue');

        await page.click('#distance_travelled_distanceTravelled_unit_0');
        await type(page, '#distance_travelled_distanceTravelled_value', '100');
        await submit(page, '#distance_travelled_button_group_continue');

        await clickOptionValue(page, 'empty');
        await submit(page, '#goods_description_button_group_continue');

        // Delete the stop
        await page.goto(baseUrl + 'domestic-survey/day-1/delete-day-stop-1', waitForOptions);
        await submit(page, '#confirm_action_button_group_confirm');

        // Day-summary border crossing screenshot
        await goto(page, baseUrl + 'domestic-survey/day-2/add');
        await page.click('#number_of_stops_hasMoreThanFiveStops_1'); // 6-or-more
        await submit(page, '#number_of_stops_buttons_continue');
        await submit(page, '#form_button_group_continue'); // intro

        await page.click('#origin_goodsLoaded_1'); // not loaded
        await type(page, '#origin_originLocation', 'Belfast');
        if (locationMode === 'postcode') {
            await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/2-NI_ONLY-journey-start.png')});
        }
        await submit(page, '#origin_button_group_continue');

        await page.click('#destination_goodsUnloaded_1'); // not unloaded
        await type(page, '#destination_destinationLocation', 'Dublin');
        if (locationMode === 'postcode') {
            await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/3-NI_ONLY-journey-end.png')});
        }
        await submit(page, '#destination_button_group_continue');

        await type(page, '#furthest_stop_furthestStop', 'Newry');
        await submit(page, '#furthest_stop_button_group_continue');

        await page.click('#border_crossing_borderCrossed_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/5-NI_ONLY-border-crossing.png')});
    } catch (e) {
        console.log(e.toString(), e.stack);
        await fullPageScreenshot.default(page, {path: screenshotPath('error-ni-border-crossing.png')});
        console.log('Image generated: error-ni-border-crossing.png');
        throw e;
    } finally {
        await browser.close();
    }
}

module.exports.run = async function(argv) {
    if (argv.niBorderCrossingOnly || argv['ni-border-crossing-only']) {
        return doNiBorderCrossingScreenshots(argv);
    }

    const {outputDir, baseUrl, username, password} = argv;
    const screenshotPath = getScreenshotPathHelper(outputDir);
    const locationMode = argv.locationMode || argv['location-mode'] || 'place-name';
    const locations = LOCATIONS[locationMode];

    const [browser, page] = await setupBrowserAndPage();

    try {
        await goto(page, baseUrl);

        await fullPageScreenshot.default(page, {path: screenshotPath('landing.png')});

        await submit(page, 'a[href="/login"]');
        await fullPageScreenshot.default(page, {path: screenshotPath('login.png')});

        await doLogin(page, username, password);
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/1-intro.png')});

        await submit(page, '#form_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/2-contact.png')});

        await doInitialContact(page);
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/3a-possession.png')});

        await doInitialPossesion(page, '3');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/sold/1-details.png')});

        await doInitialSold(page);
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/sold/2-summary.png')});

        await goto(page, baseUrl + 'domestic-survey/initial-details/in-possession');
        await doInitialPossesion(page, '2');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/scrapped/1-details.png')});

        await doInitialScrapped(page);
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/scrapped/2-summary.png')});

        await goto(page, baseUrl + 'domestic-survey/initial-details/in-possession');
        await doInitialPossesion(page, '1');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/on-hire/1-details.png')});

        await doInitialHire(page);
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/on-hire/2-summary.png')});

        await goto(page, baseUrl + 'domestic-survey/initial-details/in-possession');
        await page.click('#in_possession_of_vehicle_isInPossessionOfVehicle_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/3b-possession-yes.png')});

        await doInitialPossesion(page, '0', '0');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/4b-summary-after-exempt-vehicle.png')});

        await clickLink(page,'//dt[text() = "Is your vehicle exempt from taking part in this survey?"]/../dd[contains(concat(" ",normalize-space(@class)," ")," govuk-summary-list__actions ")]/a')
        await doInitialPossesion(page, '0', '1');
        await fullPageScreenshot.default(page, {path: screenshotPath('initial-details/4a-summary.png')});

        await goto(page, baseUrl + 'domestic-survey/vehicle-and-business-details');
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/1-details.png')});

        await doBusinessDetails(page);
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/2-trailer-configuration.png')});

        await doBusinessTrailerChoice(page, '0');
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/3a-rigid.png')});

        await page.goBack(waitForOptions);
        await doBusinessTrailerChoice(page, '1');
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/3b-rigid-with-trailer.png')});

        await page.goBack(waitForOptions);
        await doBusinessTrailerChoice(page, '2');
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/3c-artic.png')});

        await doBusinessAxleChoice(page, '2');
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/4-body-type.png')});

        await doBusinessBodyChoice(page, '3');
        await fullPageScreenshot.default(page, {path: screenshotPath('business-details/5-vehicle-weights.png')});

        await doBusinessVehicleWeights(page);
        await fullPageScreenshot.default(page, {path: screenshotPath('dashboard/1-empty.png')});

        await clickLink(page, "//a[text()='View/change the contact, business and vehicle details you have already entered']", 0);
        await fullPageScreenshot.default(page, {path: screenshotPath('dashboard/2-view-initial-details.png')});

        // Day stops
        await goto(page, baseUrl + 'domestic-survey/day-1/add')
        await page.click('#number_of_stops_hasMoreThanFiveStops_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/0-number-of-stops.png')});

        await submit(page, '#number_of_stops_buttons_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/1-introduction.png')});

        await submit(page, '#form_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/2a-journey-start-goods-not-loaded.png')});

        await page.click('#origin_goodsLoaded_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/2b-journey-start-goods-loaded.png')});

        await page.click('#origin_goodsTransferredFrom_2');
        await type(page, '#origin_originLocation', locations.origin);
        await submit(page, '#origin_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/3a-journey-end-goods-not-loaded.png')});

        await page.click('#destination_goodsUnloaded_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/3b-journey-end-goods-loaded.png')});

        await page.click('#destination_goodsUnloaded_1');
        await type(page, '#destination_destinationLocation', locations.destination);
        await submit(page, '#destination_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/5-distance-travelled.png')});

        await page.click('#distance_travelled_distanceTravelled_unit_0');
        await type(page, '#distance_travelled_distanceTravelled_value', '350');
        await submit(page, '#distance_travelled_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/6a-description-of-goods.png')});

        await page.click('#goods_description_goodsDescription_3');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/6b-description-of-goods-specific.png')});

        await type(page, '#goods_description_goodsDescriptionOther', 'Bread');
        await submit(page, '#goods_description_button_group_continue');
        // await page.click('#hazardous_goods_isHazardousGoods_1');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/7a-hazardous-no.png')});

        await page.click('#hazardous_goods_isHazardousGoods_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/7b-hazardous-yes.png')});

        await page.click('#hazardous_goods_hazardousGoodsCode_12');
        await submit(page, '#hazardous_goods_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/8-how-were-the-goods-carried.png')});

        await page.click('#cargo_type_cargoTypeCode_7');
        await submit(page, '#cargo_type_button_group_continue');
        // await page.click('#goods_weight_wasAtCapacity_1');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/9a-weight-not-at-capacity.png')});

        await page.click('#goods_weight_wasAtCapacity_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/9b-weight-at-capacity.png')});

        await page.click('#goods_weight_wasLimitedBy_1');
        await type(page, '#goods_weight_weightOfGoodsCarried', '650');
        await submit(page, '#goods_weight_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/10-summary.png')});

        await page.goto(baseUrl + 'domestic-survey/day-1/delete-day-stop-1', waitForOptions);
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops/11-delete.png')});


        await addDayStop(page, baseUrl, locations.destination, true, locations.dayStopSecond, true, '200', null, 'Apples', '300');
        await addDayStop(page, baseUrl, locations.dayStopStart, false, locations.origin, false, '100', null, null, null);
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops-reorder/1-summary-initial.png')});

        await clickLinkWithText(page, 'Re-order stages');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops-reorder/2-order-initial.png')});

        await clickLink(page, "//a[text()='Move up']", 1);
        await clickLink(page, "//a[text()='Move down']", 0);
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops-reorder/3-order-after.png')});

        await clickButtonWithText(page, 'Save order');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-stops-reorder/4-summary-after.png')});


        await page.goto(baseUrl + 'domestic-survey', waitForOptions);
        await fullPageScreenshot.default(page, {path: screenshotPath('dashboard/3-first-day-added.png')});

        // Day summary
        await goto(page, baseUrl + 'domestic-survey/day-2/add')
        await page.click('#number_of_stops_hasMoreThanFiveStops_1');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/0-number-of-stops.png')});

        await submit(page, '#number_of_stops_buttons_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/1-introduction.png')});

        await submit(page, '#form_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/2a-journey-start-goods-not-loaded.png')});

        await page.click('#origin_goodsLoaded_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/2b-journey-start-goods-loaded.png')});

        await page.click('#origin_goodsTransferredFrom_2');
        await type(page, '#origin_originLocation', locations.origin);
        await submit(page, '#origin_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/3a-journey-end-goods-not-loaded.png')});

        await page.click('#destination_goodsUnloaded_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/3b-journey-end-goods-loaded.png')});

        await page.click('#destination_goodsUnloaded_1');
        await type(page, '#destination_destinationLocation', locations.destination);
        await submit(page, '#destination_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/4-furthest-point.png')});

        await type(page, '#furthest_stop_furthestStop', locations.furthestStop);
        await submit(page, '#furthest_stop_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/6-distance-travelled.png')});

        await page.click('#distance_travelled_distanceTravelledLoaded_unit_0');
        await type(page, '#distance_travelled_distanceTravelledLoaded_value', '250');
        await page.click('#distance_travelled_distanceTravelledUnloaded_unit_0');
        await type(page, '#distance_travelled_distanceTravelledUnloaded_value', '125');
        await submit(page, '#distance_travelled_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/7a-description-of-goods.png')});

        await page.click('#goods_description_goodsDescription_2');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/7b-description-of-goods-specific.png')});

        await type(page, '#goods_description_goodsDescriptionOther', 'Bananas');
        await submit(page, '#goods_description_button_group_continue');
        // await page.click('#hazardous_goods_isHazardousGoods_1');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/8a-hazardous-no.png')});

        await page.click('#hazardous_goods_isHazardousGoods_0');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/8b-hazardous-yes.png')});

        await page.click('#hazardous_goods_hazardousGoodsCode_0');
        await submit(page, '#hazardous_goods_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/9-how-were-the-goods-carried.png')});

        await page.click('#cargo_type_cargoTypeCode_6');
        await submit(page, '#cargo_type_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/10-weight-of-goods.png')});

        await type(page, '#goods_weight_weightOfGoodsLoaded', '11000')
        await type(page, '#goods_weight_weightOfGoodsUnloaded', '11000')
        await submit(page, '#goods_weight_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/11-number-of-stops.png')});

        await type(page, '#number_of_stops_group_numberOfStopsLoading', '2');
        await type(page, '#number_of_stops_group_numberOfStopsUnloading', '0');
        await type(page, '#number_of_stops_group_numberOfStopsLoadingAndUnloading', '3');
        await submit(page, '#number_of_stops_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/12-summary.png')});

        await page.goto(baseUrl + 'domestic-survey/day-2/delete-summary-day', waitForOptions);
        await fullPageScreenshot.default(page, {path: screenshotPath('day-summary/13-delete.png')});

        await page.goto(baseUrl + 'domestic-survey', waitForOptions);
        await fullPageScreenshot.default(page, {path: screenshotPath('dashboard/4-second-day-added.png')});

        // Final questions
        await page.goto(baseUrl + 'domestic-survey/closing-details/start', waitForOptions);
        await fullPageScreenshot.default(page, {path: screenshotPath('final/1-final-questions.png')});

        await submit(page, '#form_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final/2-early-response.png')});

        await page.click('#early_response_is_correct_0');
        await submit(page, '#early_response_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final/3-missing-days.png')});

        await page.click('#missing_days_missing_days_0');
        await submit(page, '#missing_days_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final/4a-vehicle-fuel.png')});

        await page.click('#vehicle_fuel_fuelType_2');
        await type(page, '#vehicle_fuel_fuelDetails_gas_fuelQuantity_gas', '150');
        await submit(page, '#vehicle_fuel_button_group_continue');

        await doDriverVacancyQuestions(page, screenshotPath);

        await fullPageScreenshot.default(page, {path: screenshotPath('final/9-submit-survey.png')});

        // Added three stops, and so need to remove three...
        await page.goto(baseUrl + 'domestic-survey/day-1/delete-day-stop-1', waitForOptions);
        await submit(page, '#confirm_action_button_group_confirm');
        await page.goto(baseUrl + 'domestic-survey/day-1/delete-day-stop-1', waitForOptions);
        await submit(page, '#confirm_action_button_group_confirm');
        await page.goto(baseUrl + 'domestic-survey/day-1/delete-day-stop-1', waitForOptions);
        await submit(page, '#confirm_action_button_group_confirm');

        await page.goto(baseUrl + 'domestic-survey/day-2/delete-summary-day', waitForOptions);
        await submit(page, '#confirm_action_button_group_confirm');
        await page.goto(baseUrl + 'domestic-survey/closing-details/start', waitForOptions);
        await submit(page, '#form_button_group_continue');

        await page.click('#early_response_is_correct_0');
        await submit(page, '#early_response_button_group_continue');
        await page.click('#missing_days_missing_days_0');
        await submit(page, '#missing_days_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final/4b-why-survey-not-filled.png')});

        await page.click('#reason_empty_survey_reasonForEmptySurvey_0');
        await submit(page, '#reason_empty_survey_button_group_continue');

        await doDriverVacancyQuestions(page, null);

        // This page already screenshotted - 8-submit-survey.png
        await submit(page, '#form_button_group_continue');
        await fullPageScreenshot.default(page, {path: screenshotPath('final/9-success.png')});

        await clickLinkWithText(page, 'this short survey');
        await fullPageScreenshot.default(page, {path: screenshotPath('final/10-short-survey.png')});
    } catch (e) {
        console.log(e.toString(), e.stack);
        await fullPageScreenshot.default(page, {path: screenshotPath('error.png')});
        console.log('Image generated: error.png');
        throw e;
    } finally {
        await browser.close();
    }
}