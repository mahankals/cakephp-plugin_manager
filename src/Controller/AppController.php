<?php

declare(strict_types=1);

namespace PluginManager\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Application Controller
 *
 * Base controller for PluginManager plugin.
 * Extends CakePHP's base Controller directly for standalone compatibility.
 */
class AppController extends Controller
{
    /**
     * Initialize controller.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // Load Flash component for user notifications
        $this->loadComponent('Flash');
    }

    /**
     * Called before the controller action.
     * Disables layout for AJAX/SPA requests.
     *
     * @param \Cake\Event\EventInterface $event Event instance
     * @return \Cake\Http\Response|null
     */
    public function beforeRender(EventInterface $event): ?\Cake\Http\Response
    {
        // Disable layout for AJAX requests (SPA navigation)
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->disableAutoLayout();
        }

        return null;
    }

    /**
     * Check if the current request is for JSON.
     *
     * @return bool
     */
    protected function isJsonRequest(): bool
    {
        $request = $this->getRequest();

        // Check for .json extension in URL
        if ($request->getParam('_ext') === 'json') {
            return true;
        }

        // Check Accept header
        $accept = $request->getHeaderLine('Accept');
        return str_contains($accept, 'application/json');
    }
}
