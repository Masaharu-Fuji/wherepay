export default function settlementAccordion() {
    const accordions = document.querySelectorAll("[data-accordion]");

    accordions.forEach(function (accordion) {
        const buttons = accordion.querySelectorAll("[data-accordion-button]");

        function closeAll() {
            const panels = accordion.querySelectorAll("[data-accordion-panel]");
            panels.forEach(function (panel) {
                panel.classList.add("hidden");
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                const target = button.getAttribute("data-accordion-target");
                if (!target) {
                    return;
                }

                const panel = accordion.querySelector(
                    '[data-accordion-panel="' + target + '"]',
                );
                if (!panel) {
                    return;
                }

                const isCurrentlyOpen = !panel.classList.contains("hidden");

                closeAll();

                if (!isCurrentlyOpen) {
                    panel.classList.remove("hidden");
                }
            });
        });
    });
}
