import * as gds from "govuk-frontend";

function radiosOverride() {
    function nodeListForEach (nodes, callback) {
        if (window.NodeList.prototype.forEach) {
            return nodes.forEach(callback)
        }
        for (var i = 0; i < nodes.length; i++) {
            callback.call(window, nodes[i], i, nodes);
        }
    }

    var oldInit = gds.Radios.prototype.init;
    gds.Radios.prototype.init = function() {
        nodeListForEach(this.$inputs, function($input) {
            var targets = $input.getAttribute('data-aria-hide-controls');
            var activeTargets = [];

            if (!targets) {
                return
            }

            targets = JSON.parse(targets);

            for(var i=0; i<targets.length; i++) {
                if (document.querySelector('#' + targets[i])) {
                    activeTargets.push(targets[i]);
                }
            }

            if (activeTargets.length === 0) {
                return;
            }

            $input.setAttribute('aria-controls', activeTargets.join(' '));
            $input.removeAttribute('data-aria-hide-controls');
        })

        oldInit.apply(this);
    }

    var syncConditionalRevealWithInputState = gds.Radios.prototype.syncConditionalRevealWithInputState;
    gds.Radios.prototype.syncConditionalRevealWithInputState = function ($input) {
        var ariaControls = $input.getAttribute('aria-controls');

        if (!ariaControls) {
            return;
        }

        ariaControls = ariaControls.split(' ');

        if (ariaControls.length === 1) {
            syncConditionalRevealWithInputState.apply(this, [$input]);
            return;
        }

        for(var i=0; i<ariaControls.length; i++) {
            var $target = document.querySelector('#' + ariaControls[i]);

            if (!$target) {
                continue;
            }

            $target = $target.parentElement;
            var inputIsChecked = $input.checked;
            $target.classList.toggle('govuk-radios__conditional--hidden', inputIsChecked);
        }
    }
}

export default radiosOverride;