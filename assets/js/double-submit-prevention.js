import nodeListForEach from "./node-list";

/**
 * This previously disabled buttons to prevent double submission
 * but that meant that the clicked button was not included in POST
 * data, and symfony couldn't work out which button had been clicked.
 */
function doubleSubmitPrevention() {
    let $buttons = document.querySelectorAll('button[data-prevent-double-click="true"]');
    nodeListForEach($buttons, function ($button) {
        $button.addEventListener('click', function(event) {
            let $form = $button.closest('form');
            if ($form.getAttribute('data-prevent-double-click-submission')) {
                event.preventDefault();
            }
        });
    });

    let $forms = document.querySelectorAll('form');
    nodeListForEach($forms, function ($form) {
        $form.addEventListener('submit', function(event) {
            $form.setAttribute('data-prevent-double-click-submission', 'true');
        });
    });
}

export default doubleSubmitPrevention;