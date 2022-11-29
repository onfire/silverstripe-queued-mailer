<?php namespace WebTorque\QueuedMailer\Transport;


class SendinBlueTransport implements Transport
{

    private $api;

    /**
     * IP Address of SendinBlue server
     * @var string
     */
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

        $toAddresses = [];

        $addresses = explode(',', $to);

        foreach ($addresses as $address) {
            $extracted = $this->extractEmailToDetails($address);
            $toAddresses[$extracted['email']] = $extracted['name'];
        }

        $data = new \SendinBlue\Client\Model\SendSmtpEmail([
            'to' => $toAddresses,
            'sender' => [$from],
            'subject' => $subject,
            'htmlContent' => !empty($html) ? $html : $plain,
            'headers' => $headers
        ]);

        if (!empty($attachments)) {
            $data['attachment'] = $attachments;
        }

        if (!empty($replyTo)) {
            $data['replyTo'] = $replyTo;
        }

        if (!empty($cc)) {
            $ccs = explode(',', $cc);
            foreach ($ccs as $aCc) {
                $ccDetails = $this->extractEmailToDetails($aCc);
                $data['cc'][$ccDetails['email']] = $ccDetails['name'];
            }
        }

        if (!empty($bcc)) {
            $bccs = explode(',', $bcc);
            foreach ($bccs as $aBcc) {
                $bccDetails = $this->extractEmailToDetails($aBcc);
                $data['bcc'][$bccDetails['email']] = $bccDetails['name'];
            }
        }

        try {
            $result = $this->api->sendTransacEmail($data);
            return $result->getMessageId();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns array in the format:
     * <code>
     * array(
     *     'name' => 'John Smith',
     *     'email' => 'john.smith@email.com'
     * );
     * </code>
     *
     * @param $to
     * @return array
     */
    protected function extractEmailToDetails($to)
    {
        $email = $to;
        $name = '';

        if (stripos($to, '<') !== false) {

            $parts = explode('<', $to);
            $name = $parts[0];

            preg_match('/\\<(.*?)\\>/', $to, $matches);

            if (!empty($matches)) {
                $email = $matches[1];
            }
        }

        return [
            'name' => $name,
            'email' => $email
        ];
    }
}
