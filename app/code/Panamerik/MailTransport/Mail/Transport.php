<?php
declare(strict_types=1);

namespace Panamerik\MailTransport\Mail;

use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * Mail transport using PHP mail() function.
 * Replaces Magento's default SendmailTransport which requires proc_open()
 * (blocked on Cloudways FPM).
 */
class Transport implements TransportInterface
{
    private EmailMessageInterface $message;
    private LoggerInterface $logger;

    public function __construct(
        EmailMessageInterface $message,
        ?LoggerInterface $logger = null
    ) {
        $this->message = $message;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(LoggerInterface::class);
    }

    public function sendMessage(): void
    {
        try {
            $email = $this->message->getSymfonyMessage();
            $email->ensureValidity();

            // Get headers and body from Symfony Email
            $headers = $email->getPreparedHeaders();
            $to = $headers->get('To')?->getBodyAsString() ?? '';
            $subject = $email->getSubject() ?? '(no subject)';

            // Remove headers that mail() handles
            $headers->remove('To');
            $headers->remove('Subject');

            $body = $email->getBody()->toString();
            $headerString = $headers->toString();

            // Use PHP mail() - works on Cloudways with their SMTP addon
            $result = \mail($to, $subject, $body, $headerString);

            if (!$result) {
                throw new \RuntimeException('PHP mail() returned false');
            }
        } catch (\Exception $e) {
            $this->logger->error('Mail transport error: ' . $e->getMessage());
            throw new MailException(
                new Phrase('Unable to send mail. Please try again later.'),
                $e
            );
        }
    }

    public function getMessage(): EmailMessageInterface
    {
        return $this->message;
    }
}
