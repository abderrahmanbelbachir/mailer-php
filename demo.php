<?php

require 'vendor/autoload.php';
require_once 'Mailer.php';

$mailer = new Mailer();

// Set the necessary parameters
$mailer->selectAPI('PHPMailer');
$mailer->setSMTPAuth(true);
$mailer->setSMTPCredentials('mail.bsecom.com', 'contact@bsecom.com', 'ih-I*7^RCoEJ',
'ssl', '465');


$mailer->setFrom('contact@bsecom.com', 'BSECOM');
$mailer->addTo(['belbachirabderrahman@gmail.com']);
$mailer->setSubject('Test Email');
$mailer->addTextVersion('This is the plain text version of the email.');
$mailer->addHTMLVersion('<p>This is the HTML version of the email.</p>');

// Send the email
$result = $mailer->send();

if ($result) {
    echo 'Email sent successfully!';
} else {
    echo $mailer->getErrors()[0];
    echo 'Failed to send email.';
}
?>
