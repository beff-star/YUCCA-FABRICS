<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Privacy policy | Yucca Fabrics';
$legalTitle = 'Privacy policy';
$legalActive = 'privacy';
$legalDraftNote = 'This policy should be reviewed and completed for your business and applicable Kenyan privacy obligations before launch.';
$legalBody = '<p>We collect the details you provide when creating an account, placing an order, requesting a service, booking a consultation or contacting us. We use this information to respond to you and fulfil your request. Newsletter subscriptions are stored separately and can be removed by contacting us.</p>'
    . '<h2>How information is used</h2><p>Order, address and contact information is used to process purchases and communicate about delivery or service. Passwords are stored as secure hashes. Payment credentials are not stored by this site. Payment providers may process information under their own privacy terms.</p>'
    . '<h2>Your choices</h2><p>You may request access to, correction of or deletion of personal information, subject to records we must retain for legal or business purposes. Contact us using the details shown on our <a href="' . e(url('contact.php')) . '">contact page</a>.</p>';
require __DIR__ . '/includes/legal-page.php';
