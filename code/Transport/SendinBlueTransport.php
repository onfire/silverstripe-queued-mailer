<?php

namespace WebTorque\QueuedMailer\Transport;

class SendinBlueTransport implements Transport
{
    private $api;

    private $ipAddress;

    public function __construct($accessKey, $ipAddress)
    {
        $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $accessKey);

        $this->api = new \SendinBlue\Client\Api\TransactionalEmailsApi(
            new \GuzzleHttp\Client(),
            $config
        );

        $this->ipAddress = $ipAddress;
    }

    /**
     * @param string $app Name of app sending email
     * @param string $identifier Unique identifier for the email
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $html
     * @param string $plain
     * @param string $cc
     * @param string $bcc
     * @param array $attachments
     * @param array $headers
     * @param string|null $replyTo
     * @return bool|array
     */
    public function send($app, $identifier, $to, $from, $subject, $html, $plain, $cc, $bcc, $attachments, $headers, $replyTo = null)
    {
        if (empty($headers)) $headers = [];

        //add some extra info for tracking etc
        $headers['X-Mailin-custom'] = $identifier;
        $headers['X-Mailin-Tag'] = $app;

        if (!empty($this->ipAddress)) {
            $headers['X-Mailin-IP'] = $this->ipAddress;
        }

        $data = new \SendinBlue\Client\Model\SendSmtpEmail([
            'subject' => $subject,
            'to' => $this->createEmailList($to),
            'sender' => $this->createEmail($from),
            'htmlContent' => !empty($html) ? $html : $plain,
            'headers' => $headers
        ]);

        if (!empty($replyTo)) {
            $data['replyTo'] = $this->createEmail($replyTo);
        }

        if (!empty($attachments)) {
            $data['attachment'] = $attachments;
        }

        try {
            $result = $this->api->sendTransacEmail($data);
            return $result->getMessageId();
        } catch (\Exception $e) {
            return false;
        }
    }

    // TODO - build foreach
    public function createEmail($sender)
    {
        $from['email'] = $sender;

        return $from;
    }

    // TODO - build foreach
    public function createEmailList($list)
    {
        $return[] = [
            'email' => 'jasonloeve@gmail.com'
        ];

        return $return;
    }
}
