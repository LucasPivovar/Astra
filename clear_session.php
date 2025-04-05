<?php
// Start session
session_start();

// Clear the conversation history
if (isset($_SESSION['conversation_history'])) {
    $_SESSION['conversation_history'] = [];
}

// Return success message
echo json_encode(['status' => 'success', 'message' => 'Conversation history cleared']);
?>