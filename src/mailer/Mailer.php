<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: mailer.php
 */

namespace Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private const SUBJECT_NEW_ANSWER_NOTIFICATION = 'Nouvelle réponse à votre question';
    private PHPMailer $mailer;

    /**
     * Mailer constructor.
     * @throws Exception
     */
    public function __construct()
    {
        // Set the default timezone
        date_default_timezone_set('Europe/Zurich');

        // Create a new PHPMailer instance
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->SMTPDebug = 0;                               //Debug output: 0 = off (for production use), 1 = client messages, 2 = client and server messages
        $this->mailer->isSMTP();                                    //Send using SMTP
        $this->mailer->Host = MAILER_HOST;                          //Set the SMTP server to send through
        $this->mailer->SMTPAuth = true;                             //Enable SMTP authentication
        $this->mailer->Username = MAILER_USERNAME;                  //SMTP username
        $this->mailer->Password = MAILER_PASSWORD;                  //SMTP password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //Enable implicit TLS encryption
        $this->mailer->Port = MAILER_PORT;                          //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        // Sender
        $this->mailer->setFrom(MAILER_FROM, MAILER_FROM_NAME);

        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Send a new answer notification
     * @param string $name
     * @param string $email
     * @param string $topic
     * @return void
     * @throws Exception
     */
    public function sendNewAnswerNotification(string $name, string $email, string $topic): void
    {
        $body = file_get_contents('templates/new_answer_notification.html');
        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{topic}}', $topic, $body);
        $this->sendEmail([$email], self::SUBJECT_NEW_ANSWER_NOTIFICATION, $body);
    }

    /**
     * Email admins when an error occurs
     * @param string $errorId
     * @param string $error
     * @return void
     * @throws Exception
     */
    public function sendErrorEmail(string $errorId, string $error): void
    {
        $error = str_replace('\n', '<br />', $error);
        $body = '<h1>' . $errorId . '</h1><pre style="white-space: pre-wrap">' . $error . '</pre>';
        $this->sendEmail(API_ADMIN_EMAILS, '[' . API_NAME . '] ERROR: ' . $errorId, $body);
    }

    /**
     * Send an email
     * @param array $recipients
     * @param string $subject
     * @param string $body
     * @return void
     * @throws Exception
     */
    public function sendEmail(array $recipients, string $subject, string $body): void
    {
        try {
            // Recipients
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            // Content of the email
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            // Send the email
            $this->mailer->send();

            // Save the email in the "Sent" folder
            $this->saveEmail();

            // Catch errors
        } catch (Exception $e) {
            throw new Exception('Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * Save the email in the "Sent" folder and set it as "Seen"
     * @return void
     */
    private function saveEmail(): void
    {
        $path = MAILER_IMAP_SENT_FOLDER;
        $imapStream = imap_open($path, $this->mailer->Username, $this->mailer->Password, 0, 1, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'));
        imap_append($imapStream, $path, $this->mailer->getSentMIMEMessage());
        $check = imap_check($imapStream);
        imap_setflag_full($imapStream, $check->Nmsgs, "\\Seen");
        imap_close($imapStream);
    }
}