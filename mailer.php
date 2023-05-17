<?php

use PHPMailer\PHPMailer\PHPMailer;
use SendGrid\Mail\Mail;
use Mailgun\Mailgun;


class Mailer
{
    private string $fromEmail;
    private string $fromName;
    private array $to = [];
    private array $replyTo = [];
    private array $cc = [];
    private array $bcc = [];
    private array $attachments = [];
    private bool $isHTML = true;
    private bool $addAltBody = true;
    private array $errors = [];
    private string $selectedAPI;
    private array $embeddedImages = [];
    private bool $useSMTPAuth = false;
    private array $allowedAttachments = [];
    private string $htmlVersion = '';
    private string $textVersion = '';
    private string $language = 'en';
    private string $subject = '';
    private string $body = '';


    private string $altBody = '';
    private string $smtpHost = '';
    private string $smtpUsername = '';
    private string $smtpPassword = '';
    private string $smtpEncryption = '';
    private int $smtpPort = 0;
    private string $sendgridApiKey = '';
    private string $mailgunApiKey = '';

    private string $mailgunDomain = '';


    public function __construct()
    {}

    public function setFrom(string $email, string $name = ''): void
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    public function addTo(array $recipients): void
    {
        $this->to = array_merge($this->to, $recipients);
    }

    public function addReplyTo(array $recipients): void
    {
        $this->replyTo = array_merge($this->replyTo, $recipients);
    }

    public function addCc(array $recipients): void
    {
        $this->cc = array_merge($this->cc, $recipients);
    }

    public function addBcc(array $recipients): void
    {
        $this->bcc = array_merge($this->bcc, $recipients);
    }

    public function addAttachment(string $filePath): void
    {
        $this->attachments[] = $filePath;
    }

    public function setHTML(bool $isHTML): void
    {
        $this->isHTML = $isHTML;
    }

    public function setAltBody(bool $addAltBody): void
    {
        $this->addAltBody = $addAltBody;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function selectAPI(string $api): void
    {
        $this->selectedAPI = $api;
    }

    public function send(): bool
    {
        $this->errors = [];

        if (!$this->validate()) {
            return false;
        }

        switch ($this->selectedAPI) {
            case 'PHPMailer':
                return $this->sendWithPHPMailer();
            case 'SendGrid':
                return $this->sendWithSendGrid();
            case 'Mailgun':
                return $this->sendWithMailgun();
            default:
                $this->addError('Invalid API selection.');
                return false;
        }
    }

    private function sendWithPHPMailer(): bool
    {
        try {
            // Instantiate PHPMailer
            $mailer = new PHPMailer();

            // Configure SMTP settings if needed
            if ($this->useSMTPAuth) {
                $mailer->isSMTP();
                $mailer->Host = $this->smtpHost;
                $mailer->SMTPAuth = true;
                $mailer->Username = $this->smtpUsername;
                $mailer->Password = $this->smtpPassword;
                $mailer->SMTPSecure = $this->smtpEncryption;
                $mailer->Port = $this->smtpPort;
            }

            // Set the sender and recipient(s)
            $mailer->setFrom($this->fromEmail, $this->fromName);
            foreach ($this->to as $email) {
                $mailer->addAddress($email);
            }

            foreach ($this->cc as $email) {
                $mailer->addCC($email);
            }
            foreach ($this->bcc as $email) {
                $mailer->addBCC($email);
            }

            // Set email content and format
            $mailer->isHTML($this->isHTML);

            if (!empty($this->htmlVersion)) {
                $mailer->Body = $this->htmlVersion;
            } elseif (!empty($this->textVersion)) {
                $mailer->Body = $this->textVersion;
            } else {
                $mailer->Body = $this->body;
            }

            $mailer->Subject = $this->subject;
            if ($this->addAltBody) {
                $mailer->AltBody = $this->altBody;
            }


            // Add attachments
            foreach ($this->attachments as $attachment) {
                $mailer->addAttachment($attachment);
            }

            // Embed images if available
            foreach ($this->embeddedImages as $cid => $filePath) {
                $mailer->addEmbeddedImage($filePath, $cid);
            }

            // Send the email
            if ($mailer->send()) {
                return true;
            } else {
                $this->addError('PHPMailer error: ' . $mailer->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            $this->addError('An error occurred: ' . $e->getMessage());
            return false;
        }
    }


    private function sendWithSendGrid(): bool
    {
        try {
            // Instantiate SendGrid
            $sendgrid = new Mail();
            $sendgrid->setFrom($this->fromEmail);

            // Set the recipient(s)
            foreach ($this->to as $email => $name) {
                $sendgrid->addTo($email, $name);
            }
            foreach ($this->cc as $email => $name) {
                $sendgrid->addCc($email, $name);
            }
            foreach ($this->bcc as $email => $name) {
                $sendgrid->addBcc($email, $name);
            }

            // Set email content and format
            $sendgrid->setSubject($this->subject);
            $sendgrid->addContent(
                $this->isHTML ? 'text/html' : 'text/plain',
                $this->body
            );
            if ($this->addAltBody) {
                $sendgrid->addContent('text/plain', $this->altBody);
            }

            if ($this->attachments) {
                $sendgrid->addAttachments($this->attachments);
            }

            // Embed images if available
            foreach ($this->embeddedImages as $cid => $filePath) {
                $fileContent = base64_encode(file_get_contents($filePath));
                $sendgrid->addAttachment(
                    $fileContent,
                    $cid,
                    '',
                    'inline',
                    mime_content_type($filePath)
                );
            }

            // Send the email
            $sendgrid->setApiKey($this->sendgridApiKey);
            $response = $sendgrid->send($sendgrid);

            if ($response->statusCode() === 202) {
                return true;
            } else {
                $this->addError('SendGrid error: ' . $response->body());
                return false;
            }
        } catch (Exception $e) {
            $this->addError('An error occurred: ' . $e->getMessage());
            return false;
        }
    }


    private function sendWithMailgun(): bool
    {
        try {
            // Instantiate Mailgun
            $mg = Mailgun::create($this->mailgunApiKey);

            $params = [
                'from' => $this->fromEmail,
                'to' => $this->to,
                'subject' => $this->subject,
                'text' => $this->body,
                'cc' => $this->cc,
                'bcc' => $this->bcc
            ];

            if ($this->isHTML) {
                $params['html'] = $this->body;
            }

            if ($this->attachments) {
                $params['attachment'] = $this->attachments;
            }

            // Send the email
            $mg->messages()->send($this->mailgunDomain, $params);

            return true;
        } catch (Exception $e) {
            $this->addError('An error occurred: ' . $e->getMessage());
            return false;
        }
    }


    private function validate(): bool
    {
        $isValid = true;

        if (empty($this->fromEmail)) {
            $this->addError('Sender email address is required.');
            $isValid = false;
        }

        if (empty($this->to)) {
            $this->addError('At least one recipient email address is required.');
            $isValid = false;
        }

        // Perform additional validation if needed

        return $isValid;
    }

    private function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function embedImage(string $filePath, string $cid): bool
    {
        if (!file_exists($filePath)) {
            $this->addError('Image file does not exist: ' . $filePath);
            return false;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            $this->addError('Invalid image file extension: ' . $extension);
            return false;
        }

        $this->embeddedImages[$cid] = $filePath;
        return true;
    }


    private function validateEmail(string $email): bool
    {
        // Check if email is empty
        if (empty($email)) {
            $this->addError('Email address is empty.');
            return false;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('Invalid email address: ' . $email);
            return false;
        }

        return true;
    }


    // Additional method examples (can be customized further):

    public function setSMTPAuth(bool $enable): void
    {
        $this->useSMTPAuth = $enable;
    }

    public function setSMTPCredentials(string $host, string $username, string $password, string $encryption, string $port): void {
        $this->smtpHost = $host;
        $this->smtpUsername = $username;
        $this->smtpPassword = $password;
        $this->smtpEncryption = $encryption;
        $this->smtpPort = $port;
    }

    public function setSubject(string $subject): void {
        $this->subject = $subject;
    }


    public function setAllowedAttachments(array $extensions): void
    {
        $this->allowedAttachments = $extensions;
    }


    public function addHTMLVersion(string $html): void
    {
        $this->htmlVersion = $html;
    }


    public function addTextVersion(string $text): void
    {
        $this->textVersion = $text;
    }


    // Localization examples (can be customized further):

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }


    public function translateErrorMessage(string $key): string
    {
        $translations = [
            'en' => [
                'invalid_email' => 'Invalid email address.',
                // Add more English translations as needed
            ],
            'nl' => [
                'invalid_email' => 'Ongeldig e-mailadres.',
                // Add more Dutch translations as needed
            ]
        ];

        $language = $this->language ?? 'en';

        if (isset($translations[$language][$key])) {
            return $translations[$language][$key];
        }

        // If translation is not found, return the key itself
        return $key;
    }

    public function setMailgunDomain(string $domain): void {
        $this->mailgunDomain = $domain;
    }

}
