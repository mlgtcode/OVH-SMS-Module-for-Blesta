<?php

use Blesta\Core\Util\Input\Fields\InputFields;

/**
 * Ovh Messenger
 *
 * @package blesta
 * @copyright Copyright (c) 2025, MLGT
 * @link https://www.mlgt.com/en MLGT
 */
class Ovh extends Messenger
{
    public function __construct()
    {   
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Loader::loadHelpers($this, ['Html']);
        Language::loadLang('ovh', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns all fields used when setting up a messenger, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param array $vars An array of post data submitted to the manage messenger page
     * @return InputFields An InputFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getConfigurationFields(&$vars = [])
    {
        $fields = new InputFields();

        // Define configuration fields
        $config_fields = [
            'from' => [
                'label' => Language::_('Ovh.configuration_fields.from', true),
                'type' => 'fieldText',
                'options' => ['id' => 'ovh_from']
            ],
            'login' => [
                'label' => Language::_('Ovh.configuration_fields.login', true),
                'type' => 'fieldText',
                'options' => ['id' => 'ovh_login']
            ],
            'password' => [
                'label' => Language::_('Ovh.configuration_fields.password', true),
                'type' => 'fieldText',
                'options' => ['id' => 'password']
            ],
            'account' => [
                'label' => Language::_('Ovh.configuration_fields.account', true),
                'type' => 'fieldText',
                'options' => ['id' => 'ovh_account']
            ]
        ];

        foreach ($config_fields as $key => $field) {
            $label = $fields->label($field['label'], 'ovh_' . $key);
            $fields->setField(
                $label->attach(
                    $fields->{$field['type']}(
                        $key,
                        (isset($vars[$key]) ? $vars[$key] : null),
                        $field['options']
                    )
                )
            );
        }

        $fields->setHtml('
            <div style="background-color:rgb(253, 252, 230); border: 1px solidrgb(78, 180, 228); padding: 15px;">
                <p><strong>' . Language::_('Ovh.doc.dat', true) . ':</strong> 
                <a href="' . Language::_('Ovh.doc.link', true) . '" target="_blank">
                    ' . Language::_('Ovh.doc.title', true) . '
                </a> | 
                <a href="' . Language::_('Ovh.cloud.link', true) . '" target="_blank">
                    ' . Language::_('Ovh.cloud.title', true) . '
                </a></p>
            </div>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    const passwordField = document.getElementById("password");
                    if (passwordField) {
                        passwordField.style.filter = "blur(4px)";
                        passwordField.addEventListener("focus", function() {
                            passwordField.style.filter = "none";
                        });
                        passwordField.addEventListener("blur", function() {
                            passwordField.style.filter = "blur(4px)";
                        });
                    }
                });
            </script>
        ');

        return $fields;
    }

    /**
     * Updates the meta data for this messenger
     *
     * @param array $vars An array of messenger info to add
     * @return array A numerically indexed array of meta fields containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function setMeta(array $vars)
    {
        $meta_fields = ['from', 'password', 'login', 'account'];
        $encrypted_fields = ['password'];

        $meta = [];
        foreach ($vars as $key => $value) {
            if (in_array($key, $meta_fields)) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Send a message.
     *
     * @param mixed $to_user_id The user ID this message is to
     * @param string $content The content of the message to send
     * @param string $type The type of the message to send (optional)
     */
    public function send($to_user_id, $content, $type = null)
    {
        $meta = $this->getMessengerMeta();

        Loader::loadModels($this, ['Staff', 'Clients', 'Contacts']);

        $is_client = true;
        if (($user = $this->Staff->getByUserId($to_user_id))) {
            $is_client = false;
        } else {
            $user = $this->Clients->getByUserId($to_user_id);

            $phone_numbers = $this->Contacts->getNumbers($user->contact_id);
            if (is_array($phone_numbers) && !empty($phone_numbers)) {
                $user->phone_number = reset($phone_numbers);
            }
        }

        $error = null;
        $success = false;
        if ($type == 'sms') {
            $to = $is_client
                ? (isset($user->phone_number->number) ? $user->phone_number->number : '')
                : (isset($user->number_mobile) ? $user->number_mobile : '');

            $params = [
                'account' => $meta->account,
                'login' => $meta->login,
                'password' => $meta->password,
                'from' => $meta->from,
                'to' => $to,
                'message' => $content,
                'noStop' => true
            ];

            $this->log($to_user_id, json_encode($params, JSON_PRETTY_PRINT), 'input', true);

            try {
                $response = $this->getApi($params);
                $response_data = json_decode($response, true);

                if (isset($response_data['status'])) {
                    switch ($response_data['status']) {
                        case 100:
                        case 101:
                            $success = true;
                            break;
                        case 201:
                            $error = 'A setting is missing (e.g., Missing login, Missing password).';
                            break;
                        case 202:
                            $error = 'A setting is incorrect (e.g., Invalid tag is too long, Invalid deferred time).';
                            break;
                        case 401:
                            $error = 'No authorized IP. Manage authorized IPs via the OVHcloud Control Panel.';
                            break;
                        default:
                            $error = $response_data['message'] ?? 'Unknown error';
                    }
                } else {
                    $error = 'Invalid response from API.';
                }

                $this->log($to_user_id, json_encode($response_data, JSON_PRETTY_PRINT), 'output', $success);
            } catch (Exception $e) {
                $error = $e->getMessage();
                $success = false;

                $this->log($to_user_id, json_encode($error, JSON_PRETTY_PRINT), 'output', $success);
            }
        }
    }

    /**
     * Handles the HTTP connection to the OVH API.
     *
     * @param array $params The parameters to send to the OVH API
     * @return string The API response
     * @throws Exception If the API request fails
     */
    private function getApi(array $params)
    {
        $params['contentType'] = 'application/json';

        $url = 'https://www.ovh.com/cgi-bin/sms/http2sms.cgi?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OvhMessenger for Blesta/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}
