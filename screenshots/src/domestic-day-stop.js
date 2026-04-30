const {submit, type, clickOptionValue} = require("./common");

async function location(page, prefix, place, isLoadedOrUnloaded) {

    const loadUnloadSuffix = (prefix === 'origin') ? 'Loaded': 'Unloaded';
    const transferSuffix = (prefix === 'origin') ? 'From' : 'To';

    await page.click(`#${prefix}_goods${loadUnloadSuffix}_${(isLoadedOrUnloaded ? '0' : '1')}`);

    if (isLoadedOrUnloaded) {
        await page.click(`#${prefix}_goodsTransferred${transferSuffix}_3`); // "Not applicable"
    }

    await type(page, `#${prefix}_${prefix}Location`, place);
    await submit(page, `#${prefix}_button_group_continue`);
}

module.exports.addDayStop = async (page, baseUrl, start, loaded, destination, unloaded, distance, border, goods, weight) => {
    await page.goto(baseUrl + "domestic-survey/day-1/stop-add/start");
    await location(page, 'origin', start, loaded);
    await location(page, 'destination', destination, unloaded);

    if (border !== null) {
        if (border === '') {
            await page.click('#border_crossing_borderCrossed_1');
        } else {
            await page.click('#border_crossing_borderCrossed_0');
            await type(page, '#border_crossing_borderCrossingLocation', border);
        }
        await submit(page, '#border_crossing_button_group_continue');
    }

    await page.click('#distance_travelled_distanceTravelled_unit_0');
    await type(page, '#distance_travelled_distanceTravelled_value', distance);
    await submit(page, '#distance_travelled_button_group_continue');

    if (goods !== null) {
        await page.click('#goods_description_goodsDescription_3');
        await type(page, '#goods_description_goodsDescriptionOther', goods);
        await submit(page, '#goods_description_button_group_continue');

        await page.click('#hazardous_goods_isHazardousGoods_1'); // "Not hazardous"
        await submit(page, '#hazardous_goods_button_group_continue');

        await page.click('#cargo_type_cargoTypeCode_6'); // "Roll cages"
        await submit(page, '#cargo_type_button_group_continue');

        await page.click('#goods_weight_wasAtCapacity_1'); // "Not at capacity"
        await type(page, '#goods_weight_weightOfGoodsCarried', weight);
        await submit(page, '#goods_weight_button_group_continue');
    } else {
        await clickOptionValue(page, 'empty'); // "None - the vehicle was empty"
        await submit(page, '#goods_description_button_group_continue');
    }
};
