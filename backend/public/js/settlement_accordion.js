export default function settlementAccordion() {
    const accordions = document.querySelectorAll("[data-accordion]");

    accordions.forEach(function (accordion) {
        const buttons = accordion.querySelectorAll("[data-accordion-button]");

        function panelFor(button) {
            const target = button.getAttribute("data-accordion-target");
            if (!target) {
                return null;
            }
            return accordion.querySelector(
                '[data-accordion-panel="' + target + '"]',
            );
        }

        function setOpen(button, open) {
            button.setAttribute("aria-expanded", open ? "true" : "false");
            const chevron = button.querySelector("[data-accordion-chevron]");
            if (chevron) {
                chevron.classList.toggle("rotate-180", open);
            }
        }

        function closeAll() {
            buttons.forEach(function (button) {
                const panel = panelFor(button);
                if (panel) {
                    panel.classList.add("hidden");
                }
                setOpen(button, false);
            });
        }

        buttons.forEach(function (button) {
            const panel = panelFor(button);
            setOpen(button, Boolean(panel) && !panel.classList.contains("hidden"));

            button.addEventListener("click", function () {
                const current = panelFor(button);
                if (!current) {
                    return;
                }

                const isCurrentlyOpen = !current.classList.contains("hidden");
                closeAll();
                if (!isCurrentlyOpen) {
                    current.classList.remove("hidden");
                    setOpen(button, true);
                }
            });
        });
    });
}
