import cn from "./cn.js";
import location from "./location.js";

document.addEventListener("DOMContentLoaded", async () => {
    const [
        { default: rankingBar },
        { default: settlementAccordion },
        { default: toast },
        { default: roomHeader },
        { default: roomItemsHeight },
        { default: roomItemParticipantJump },
    ] = await Promise.all([
        import("./ranking_bar.js"),
        import("./settlement_accordion.js"),
        import("./toast.js"),
        import("./room_header.js"),
        import("./room_items_height.js"),
        import("./room_item_participant_jump.js"),
    ]);
    rankingBar();
    settlementAccordion();
    toast();
    roomHeader();
    roomItemsHeight();
    roomItemParticipantJump();
});

export { cn, location };
