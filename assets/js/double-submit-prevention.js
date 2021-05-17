import nodeListForEach from "./node-list";

function doubleSubmitPrevention() {
    var $forms = document.querySelectorAll('form[data-prevent-double-click="true"]');
    nodeListForEach($forms, function ($form) {
        $form.addEventListener('submit', function() {
            var $buttons = $form.querySelectorAll('button');
            nodeListForEach($buttons, function ($button) {
                $button.disabled = true;
            })
        })
    });
}

export default doubleSubmitPrevention;