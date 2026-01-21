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

    const parseEmails = (value) => {
        // Split by comma, newline, or semicolon and clean up
        return value.split(/[,;\n]+/)
            .map(e => e.trim())
            .filter(e => e.length > 0 && e.includes('@'));
    };

    const escapeHtml = (str) => {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    const renderBlacklistDisplay = (displayElement, emails, blacklistedEmails) => {
        if (!emails || emails.length === 0) {
            displayElement.classList.add('email-blacklist-display--hidden');
            displayElement.innerHTML = '';
            return;
        }

        const blacklistedLower = blacklistedEmails.map(e => e.toLowerCase());
        const foundBlacklisted = emails.filter(e => blacklistedLower.includes(e.toLowerCase()));

        if (foundBlacklisted.length === 0) {
            displayElement.classList.add('email-blacklist-display--hidden');
            displayElement.innerHTML = '';
            return;
        }

        displayElement.innerHTML = foundBlacklisted.map(email =>
            `<div class="govuk-!-margin-bottom-1"><strong class="govuk-tag govuk-tag--red">Blacklisted</strong> ${escapeHtml(email)}</div>`
        ).join('');
        displayElement.classList.remove('email-blacklist-display--hidden');
    };

    const checkBlacklist = async (emails, displayElement) => {
        if (!emails || emails.length === 0) {
            displayElement.classList.add('email-blacklist-display--hidden');
            displayElement.innerHTML = '';
            return;
        }

        try {
            const url = new URL('/admin/api/email-blacklist-check', window.location.origin);
            url.searchParams.set('emails', emails.join(','));

            const response = await fetch(url);
            const data = await response.json();

            renderBlacklistDisplay(displayElement, emails, data.blacklistedEmails || []);
        } catch (error) {
            console.error('Email blacklist check failed:', error);
            displayElement.classList.add('email-blacklist-display--hidden');
        }
    };

    GOVUK.rfsEmailBlacklistCheck = {
        init: () => {
            const containers = document.querySelectorAll('[data-email-blacklist-check]');
            containers.forEach(container => {
                const inputSelector = container.getAttribute('data-email-blacklist-input');
                const displayElement = container.querySelector('[data-email-blacklist-display]');

                if (!inputSelector || !displayElement) {
                    return;
                }

                const input = document.querySelector(inputSelector);
                if (!input) {
                    return;
                }

                const debouncedCheck = debounce((value) => {
                    const emails = parseEmails(value);
                    checkBlacklist(emails, displayElement);
                }, 300);

                input.addEventListener('input', (e) => {
                    debouncedCheck(e.target.value);
                });

                // Do initial check if field already has a value
                if (input.value) {
                    const emails = parseEmails(input.value);
                    checkBlacklist(emails, displayElement);
                }
            });
        },
    };

    global.GOVUK = GOVUK;
})(window);
