<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Terms of use | Yucca Fabrics';
$legalTitle = 'Terms of use';
$legalActive = 'terms';
$legalDraftNote = 'These draft terms should be reviewed for your business and applicable local law before launch.';
$legalBody = '<p>Product descriptions, availability and pricing are provided as accurately as possible and may be updated. Placing an order submits a purchase request; orders are confirmed after review and payment arrangements. Fabric is sold by the unit displayed on each product listing.</p>'
    . '<h2>Orders and services</h2><p>Delivery timing, service scope and quotations are confirmed with each customer. A consultation request is not confirmed until Yucca Fabrics contacts you. Please ensure delivery and project details are accurate.</p>'
    . '<h2>Contact</h2><p>Questions about these terms? <a href="' . e(url('contact.php')) . '">Contact our team</a>.</p>';
require __DIR__ . '/includes/legal-page.php';
