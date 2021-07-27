import nodeListForEach from "./node-list";

/**
 * This previously disabled buttons to prevent double submission
 * but that meant that the clicked button was not included in POST
 * data, and symfony couldn't work out which button had been clicked.
 */
function doubleSubmitPrevention() {
    let $forms = document.querySelectorAll('form[data-prevent-double-click="true"]');
    nodeListForEach($forms, function ($form) {
        $form.addEventListener('submit', function(event) {
            if (true === $form.getAttribute('data-previously-submitted')) {
                // previously submitted
                event.preventDefault();
            } else {
                // not previously submitted
                $form.setAttribute('data-previously-submitted', true)
            }
        })
    });
}

export default doubleSubmitPrevention;