# Mailer Class

The Mailer class is a versatile email-sending class that provides a unified interface for sending emails using different email service providers. It supports PHPMailer, SendGrid, and Mailgun APIs. You can easily switch between these providers without changing your codebase.

## Features

- Supports multiple email service providers: PHPMailer, SendGrid, and Mailgun.
- Provides a simple and consistent interface for sending emails.
- Supports sending HTML and plain text emails.
- Allows adding attachments to emails.
- Supports embedding images in HTML emails.
- Provides error handling and reporting.

## Prerequisites

- PHP version 7.1 or later.
- Composer dependency manager.

## Installation

1. Clone the repository or download the code as a ZIP file.

2. Navigate to the project directory in your command-line interface.

3. Run the following command to install the required dependencies:

`composer install`


## Usage ( PHPMailer sample)

1. Include the `Mailer.php` file in your project:
    `require_once 'Mailer.php';`
2. Create an instance of Mailer :
    `$mailer = new Mailer();`
3. Select the API :
    `$mailer->selectAPI('PHPMailer');`
4. Enable SMTPAuth :
   `$mailer->setSMTPAuth(true);`
5. Past your SMTPCredentials:
   `$mailer->setSMTPCredentials('host', 'email', 'password',
   'encryption', 'port');`
6. Set From email and name :
   `$mailer->setFrom('email@domain.com', 'From_name');`
7. Add recipient :
   `$mailer->addTo(['recipient@domain.com']);`
8. Set subject :
    `$mailer->setSubject('Test Email');`
9. If you want to send a text message :
   `$mailer->addTextVersion('This is the plain text version of the email.');`
10. If you want to send an html message :
    `$mailer->addHTMLVersion('<p>This is the HTML version of the email.</p>');`
11. Send your mail :
    `$result = $mailer->send();`


# Demo

`php demo.php`

# API Configuration
Each email service provider requires specific API credentials to send emails. Make sure to configure the API credentials for the desired provider in the Mailer class before sending emails.

* For PHPMailer: No API credentials required. SMTP settings (if needed) should be configured in the class.
* For SendGrid: Set the SendGrid API key using the $sendgridApiKey property in the Mailer class.
* For Mailgun: Set the Mailgun API key using the $mailgunApiKey property in the Mailer class.

# Error Handling
The Mailer class provides error handling and reporting. If an error occurs during the email sending process, the class captures the error message and stores it in the errors property. You can retrieve the error messages using the getErrors() method.

# Customization
You can customize the Mailer class according to your specific requirements. The class provides various methods for setting different email properties and behaviors. You can extend the class or modify its methods to add