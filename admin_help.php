<?php
// admin_help.php

// Help Documentation and FAQ for Administrators

/**
 * Welcome to the Administrator Help Documentation.
 * Below are some common FAQs that may assist you in managing the system.
 */

// FAQ Section
$faqs = [
    ['question' => 'How do I reset a user password?', 'answer' => 'To reset a user password, go to the Users section and select the user. Click on the reset password option.'],
    ['question' => 'How can I manage user roles?', 'answer' => 'User roles can be managed from the Roles section under Settings.'],
    ['question' => 'Where can I find system logs?', 'answer' => 'System logs are available under the Reports section.'],
    ['question' => 'What should I do if I encounter an error?', 'answer' => 'If you encounter an error, check the system logs for more details. You may also contact support.'],
];

// Display the FAQ
foreach ($faqs as $faq) {
    echo "<h2>" . $faq['question'] . "</h2>";
    echo "<p>" . $faq['answer'] . "</p>";
}
?>
