import cn from "./cn.js";
import fetchItemGeoLocation from "./location.js";

window.fetchItemGeoLocation = fetchItemGeoLocation;

document.addEventListener("DOMContentLoaded", async () => {
    const [
        { default: rankingBar },
        { default: settlementAccordion },
        { default: toast },
        { default: roomHeader },
        { default: roomItemsHeight },
        { default: roomItemParticipantJump },
        { default: itemForm },
        { default: settlementHandoff },
    ] = await Promise.all([
        import("./ranking_bar.js"),
        import("./settlement_accordion.js"),
        import("./toast.js"),
        import("./room_header.js"),
        import("./room_items_height.js"),
        import("./room_item_participant_jump.js"),
        import("./item_form.js"),
        import("./settlement_handoff.js"),
    ]);
    rankingBar();
    settlementAccordion();
    toast();
    roomHeader();
    roomItemsHeight();
    roomItemParticipantJump();
    itemForm();
    settlementHandoff();
});

export { cn, fetchItemGeoLocation };
