<?php
/**
 * Aurora Hotel Plaza - AI Assistant Controller
 */

function getAiAssistantData() {
    return [
        'user_name' => $_SESSION['full_name'] ?? 'Admin'
    ];
}
