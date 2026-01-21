;(function (global) {
    'use strict'
    let GOVUK = global.GOVUK || {};

    let debounceTimer;

    const debounce = (callback, delay) => {
        return (...args) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => callback(...args), delay);
        };
    };

    const escapeHtml = (str) => {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    const renderLcniDisplay = (displayElement, data) => {
        // Check if DVLA import is in progress
        if (data && data.locked) {
            displayElement.innerHTML = '<strong class="govuk-tag govuk-tag--yellow">LCNI</strong> Lookup temporarily unavailable; DVLA sample import in progress';
            displayElement.classList.remove('lcni-display--hidden');
            return;
        }

        if (!data || !data.found || !data.emails || data.emails.length === 0) {
            displayElement.innerHTML = '<strong class="govuk-tag govuk-tag--grey">LCNI</strong> No match found';
            displayElement.classList.remove('lcni-display--hidden');
            return;
        }

        // Check for blacklisted emails (case-insensitive comparison)
        const blacklistedEmails = (data.blacklistedEmails || []).map(e => e.toLowerCase());

        // Always blue - LCNI supplements local emails, doesn't override
        displayElement.innerHTML = data.emails.map(email => {
            const isBlacklisted = blacklistedEmails.includes(email.toLowerCase());
            const blacklistTag = isBlacklisted ? '<strong class="govuk-tag govuk-tag--red">Blacklisted</strong> ' : '';
            return `<div class="govuk-!-margin-bottom-2">${blacklistTag}<strong class="govuk-tag govuk-tag--blue">LCNI</strong> ${escapeHtml(email)}</div>`;
        }).join('');
        displayElement.classList.remove('lcni-display--hidden');
    };

    const lookupLcni = async (companyName, surveyType, displayElement) => {
        if (!companyName || companyName.trim() === '') {
            displayElement.classList.add('lcni-display--hidden');
            return;
        }

        try {
            const url = new URL('/admin/api/lcni-lookup', window.location.origin);
            url.searchParams.set('companyName', companyName.trim());
            url.searchParams.set('surveyType', surveyType);

            const response = await fetch(url);
            const data = await response.json();

            renderLcniDisplay(displayElement, data);
        } catch (error) {
            console.error('LCNI lookup failed:', error);
            displayElement.classList.add('lcni-display--hidden');
        }
    };

    GOVUK.rfsLcniLookup = {
        init: () => {
            const containers = document.querySelectorAll('[data-lcni-lookup]');
            containers.forEach(container => {
                const inputSelector = container.getAttribute('data-lcni-input');
                const surveyType = container.getAttribute('data-lcni-survey-type');
                const displayElement = container.querySelector('[data-lcni-display]');

                if (!inputSelector || !surveyType || !displayElement) {
                    return;
                }

                const input = document.querySelector(inputSelector);
                if (!input) {
                    return;
                }

                const debouncedLookup = debounce((value) => {
                    lookupLcni(value, surveyType, displayElement);
                }, 300);

                input.addEventListener('input', (e) => {
                    debouncedLookup(e.target.value);
                });

                // Do initial lookup if field already has a value
                if (input.value) {
                    lookupLcni(input.value, surveyType, displayElement);
                }
            });
        },
    };

    global.GOVUK = GOVUK;
})(window);
