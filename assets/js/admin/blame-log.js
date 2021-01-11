;(function (global) {
    'use strict'
    let GOVUK = global.GOVUK || {};

    const renderjson = require('renderjson');
    let propertiesRow;

    GOVUK.rfsBlameLog = {
        initPropertyViewLinks: () => {
            const showProperties = function(event) {
                event.preventDefault();
                let thisRow = this.closest('tr');
                let hide = (thisRow.nextElementSibling === propertiesRow);
                if (propertiesRow !== undefined) {
                    propertiesRow.parentNode.removeChild(propertiesRow);
                    propertiesRow = undefined;
                }
                if (!hide) {
                    let properties = JSON.parse(this.attributes.getNamedItem('data-properties').value);
                    propertiesRow = document.createElement('tr');
                    propertiesRow.setAttribute('class', 'govuk-table__row');
                    propertiesRow.insertAdjacentHTML('beforeend', '<td class="govuk-table__cell" colspan="6"></td>')
                    thisRow.insertAdjacentElement('afterend', propertiesRow);

                    renderjson
                        .set_show_to_level(1)
                        .set_icons('+', '-');
                    propertiesRow.querySelector('td').insertAdjacentElement('beforeend', renderjson(properties));
                }

                return false;
            };
            Array.from(document.getElementsByClassName('js-govuk-blamelog--properties')).forEach(
                (link) => link.addEventListener('click', showProperties)
            );
        },

        initAll: () => {
            GOVUK.rfsBlameLog.initPropertyViewLinks();
        },
    }

    global.GOVUK = GOVUK
})(window); // eslint-disable-line semi
