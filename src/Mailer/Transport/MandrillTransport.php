<?php
/**
 * @copyright
 * @link
 * @since
 * @license
 */
namespace MandrillTransport\Mailer\Transport;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\LogTrait;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Network\Exception\SocketException;
use Cake\Utility\Hash;
use InvalidArgumentException;

class MandrillTransport extends AbstractTransport
{
    use LogTrait;

    public $transportConfig = [
        'host' => 'mandrillapp.com',
        'api_key' => null,
        'api_key_test' => null,
        'merge_language' => 'handlebars',
        'inline_css' => true,
        'subaccount' => null,
        'headers' => [],
        'proxy' => false,
    ];

    public $defaultRequest = [
        'key' => null,
        'template_name' => null,
        'template_content' => [],
        'message' => [],
        'async' => false,
        'ip_pool' => 'Main Pool',
    ];
    public $isDebug;
    public $http;

    /**
     * @param  Email $email Email instance
     * @return String Response object, json formatted
     */
    public function send(Email $email)
    {

        $this->isDebug = Configure::read('debug');
        $this->transportConfig = Hash::merge($this->transportConfig, $this->_config);

        $clientOptions = [
            'host' => $this->transportConfig['host'],
            'scheme' => 'https',
            'headers' => $this->transportConfig['headers'],
        ];

        if ($this->transportConfig['proxy']) {
            $clientOptions['proxy'] = $this->transportConfig['proxy'];
        }

        $this->http = new Client($clientOptions);

        $request = $this->defaultRequest;

        if ($this->isDebug) {
            $request['key'] = (empty($this->transportConfig['api_key_test']) ? $this->transportConfig['api_key'] : $this->transportConfig['api_key_test']);
        } else {
            $request['key'] = $this->transportConfig['api_key'];
        }

        $request['message'] += $this->_from($email);
        $request['message'] += $this->_to($email);
        $request['message'] += $this->_attachments($email);

        if (!empty($email->getSubject())) {
            $request['message']['subject'] = $email->getSubject();
        }

        foreach (['merge_language', 'inline_css', 'subaccount'] as $key) {
            $request['message'][$key] = $this->transportConfig[$key];
        }

        foreach ($email->viewVars as $key => $value) {
            $request['message']['global_merge_vars'][] = [
                'name' => $key,
                'content' => $value,
            ];
        }

        if (isset($email->viewVars['template_name']) && !empty($email->viewVars['template_name'])) {
            $request['template_name'] = $email->viewVars['template_name'];
            $request['message']['tags'] = [$email->viewVars['template_name']];
            $response = $this->_send($email, 'send-template', $request);
        } else {
            $request['message']['html'] = $email->message(\Cake\Mailer\Email::MESSAGE_HTML);
            $request['message']['text'] = $email->message(\Cake\Mailer\Email::MESSAGE_TEXT);
            $response = $this->_send($email, 'send', $request);
        }
        if (!$response) {
            throw new SocketException($response->code);
        }
        return $response->json;
    }

    /**
     * @param  Email $email Email instance
     * @param  String $service 'send' or 'send-template'
     * @param  Array $request Mandril request object
     * @return String Response object
     */
    protected function _send(Email $email, $service, $request)
    {
        return $this->http->post(
            '/api/1.0/messages/' . $service . '.json',
            json_encode($request),
            ['type' => 'json']
        );
    }

    /**
     * @param  Email $email Email instance
     * @return Array
     */
    protected function _from(Email $email)
    {
        return [
            'from_email' => key($email->getFrom()),
            'from_name' => current($email->getFrom()),
        ];
    }

    /**
     * @param  Email $email Email instance
     * @return Array
     */
    protected function _to(Email $email)
    {
        foreach ([
            'getTo' => 'to',
            'getCc' => 'cc',
            'getBcc' => 'bcc',
        ] as $functionName => $type) {
            foreach ($email->{$functionName}() as $mail => $name) {
                $to['to'][] = [
                    'email' => $mail,
                    'name' => $name,
                    'type' => $type,
                ];
            }
        }
        return $to;
    }

    /**
     * @param  Email $email Email instance
     * @return Array
     */
    protected function _attachments(Email $email)
    {
        $message = [];
        foreach ($email->getAttachments() as $filename => $file) {
            if (!isset($file['file'])) {
                if (!isset($file['data'])) {
                    throw new InvalidArgumentException("No file or data specified, " . $filename);
                }
                $content = $file['data'];
            } else {
                $content = file_get_contents($file['file']);
            }
            
            if (isset($file['contentId'])) {
                $message['images'][] = [
                    'type' => $file['mimetype'],
                    'name' => $file['contentId'],
                    'content' => $content,
                ];
            } else {
                $message['attachments'][] = [
                    'type' => $file['mimetype'],
                    'name' => $filename,
                    'content' => $content,
                ];
            }
        }
        return $message;
    }

}
