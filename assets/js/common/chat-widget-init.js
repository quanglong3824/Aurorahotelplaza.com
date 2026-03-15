/**
 * chat-widget-init.js — Aurora Hotel Plaza
 * Customer-side chat widget initialization and data passing
 */

document.addEventListener('DOMContentLoaded', function () {
    // Load existing conversation if logged in
    const cwBtn = document.getElementById('cwBtn');
    if (cwBtn && cwBtn.dataset.loggedIn === '1') {
        if (typeof ChatWidget !== 'undefined') {
            ChatWidget.checkExistingConversation();
        }
    }
});
