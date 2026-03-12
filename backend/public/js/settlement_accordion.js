document.addEventListener('DOMContentLoaded', function () {
    var accordions = document.querySelectorAll('[data-accordion]');

    accordions.forEach(function (accordion) {
        var buttons = accordion.querySelectorAll('[data-accordion-button]');

        function closeAll() {
            var panels = accordion.querySelectorAll('[data-accordion-panel]');
            panels.forEach(function (panel) {
                panel.classList.add('hidden');
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-accordion-target');
                if (!target) {
                    return;
                }

                var panel = accordion.querySelector('[data-accordion-panel="' + target + '"]');
                if (!panel) {
                    return;
                }

                var isCurrentlyOpen = !panel.classList.contains('hidden');

                closeAll();

                if (!isCurrentlyOpen) {
                    panel.classList.remove('hidden');
                }
            });
        });
    });
});

