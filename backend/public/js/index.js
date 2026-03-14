import cn from "./cn.js";
import location from "./location.js";

document.addEventListener("DOMContentLoaded", async () => {
    const [
        { default: rankingBar },
        { default: settlementAccordion },
        { default: toast },
        { default: roomHeader },
        { default: roomItemsHeight },
    ] = await Promise.all([
        import("./ranking_bar.js"),
        import("./settlement_accordion.js"),
        import("./toast.js"),
        import("./room_header.js"),
        import("./room_items_height.js"),
    ]);
    rankingBar();
    settlementAccordion();
    toast();
    roomHeader();
    roomItemsHeight();
});

export { cn, location };
