<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class SenderPlugin
 * @package Grav\Plugin
 */
class SenderPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0],
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            'onFormProcessed' => ['onFormProcessed', 0],
        ]);
    }

    /**
     * Catch form submissions
     */
    public function onFormProcessed(Event $event): void
    {
        $form = $event['form'];
        $action = $event['action'];
        $params = $event['params'];

        // Catch the form action(s) we're interested in
        $result = NULL;
        switch ($action) {
            case 'sender-subscribe':
                $result = $this->senderApi(
                    'post',
                    $form->getData()->toArray(),
                    $params,
                );
                break;
        }

        // Modify form status and message to report results of Sender API call
        if (!is_null($result)){
            if ($result) {
                $form->status = 'success';
                $form->message = $this->config->get('plugins.sender.messages.success');
            } else {
                $form->status = 'error';
                $form->message = $this->config->get('plugins.sender.messages.error');
            }
        }

        return;
    }

    /**
     * Function for handling all API calls to the `subscribers` endpoint
     */
    public function senderApi(string $method, array $formdata, array $params=[]): bool
    {
        // Whitelist $method
        $methods = [
            'post' => ['email'],
        ];
        if (!array_key_exists($method, $methods)) {
            $this->grav['log']->debug("Invalid method: ${$method}");
            return false;
        }

        // Whitelist $json
        $json = [];
        foreach (['email', 'firstname', 'lastname', 'fields', 'phone'] as $key) {
            if (array_key_exists($key, $formdata)) {
                $json[$key] = $formdata[$key];
            }
        }
        foreach (['groups', 'trigger_automation'] as $key) {
            if (array_key_exists($key, $params)) {
                $json[$key] = $params[$key];
            }
        }

        // Check $json has required fields
        foreach ($methods[$method] as $key) {
            if (!array_key_exists($key, $json)) {
                $this->grav['log']->debug("Missing required field: ${$key} for method: ${$method}");
                return false;
            }
        }

        // Attempt request to sender API
        $token = $this->config->get('plugins.sender.sender_token');
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->$method(
                'https://api.sender.net/v2/subscribers',
                [
                    'headers' => [
                        'Authorization' => "Bearer ${token}",
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $json,
                ],
            );

        // Handle exceptions and failures
        } catch (\Exception $e) {
            $reason = $e->getMessage();
            $this->grav['log']->debug("Couldn't add subscriber: ${$reason}");
            return false;
        }
        if ($response->getStatusCode() != 200) {
            $reason = $response->getReasonPhrase();
            $this->grav['log']->debug("Couldn't add subscriber: ${$reason}");
            return false;
        }

        return true;
    }
}
