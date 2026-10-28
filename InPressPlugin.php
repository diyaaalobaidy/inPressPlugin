<?php

/**
 * @file plugins/generic/inPressPlugin/InPressPlugin.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class InPressPlugin
 * @ingroup plugins_generic_inPressPlugin
 *
 * @brief OJS 3.5 plugin to showcase In-Press (Ahead of Print / Forthcoming) articles
 *        that are accepted and scheduled for future publication.
 */

namespace APP\plugins\generic\inPressPlugin;

use APP\core\Application;
use APP\plugins\generic\inPressPlugin\classes\handlers\InPressFrontendHandler;
use APP\plugins\generic\inPressPlugin\classes\handlers\InPressSettingsHandler;
use APP\plugins\generic\inPressPlugin\classes\InPressService;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class InPressPlugin extends GenericPlugin
{
    public const NMI_TYPE_IN_PRESS = 'NMI_TYPE_IN_PRESS';

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            // Frontend and backend routing hook
            Hook::add('LoadHandler', [$this, 'callbackLoadHandler']);

            // Asset injection and homepage block integration
            Hook::add('TemplateManager::display', [$this, 'callbackTemplateDisplay']);

            // Navigation Menu Item integration
            Hook::add('NavigationMenus::itemTypes', [$this, 'callbackNavMenuItemTypes']);
            Hook::add('NavigationMenus::displayItem', [$this, 'callbackNavMenuDisplayItem']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.inPressPlugin.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.inPressPlugin.description');
    }

    /**
     * Add "Settings" action link in the Plugins list
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        $settingsAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($actions, $settingsAction);
        return $actions;
    }

    /**
     * Handle settings actions from the plugin management grid
     */
    public function manage($args, $request)
    {
        $verb = $request->getUserVar('verb');
        $handler = new InPressSettingsHandler($this);

        if ($verb === 'settings') {
            return $handler->settings($args, $request);
        } elseif ($verb === 'save') {
            return $handler->save($args, $request);
        }

        return parent::manage($args, $request);
    }

    /**
     * Route hook callback for 'inpress' and 'inPress' pages
     */
    public function callbackLoadHandler(string $hookName, array $args): bool
    {
        $page = $args[0];

        if ($page === 'inpress' || $page === 'inPress') {
            $args[3] = new InPressFrontendHandler($this);
            return true;
        }

        return false;
    }

    /**
     * @copydoc Plugin::getSetting()
     *
     * Provides default fallback values when settings have not yet been stored in the database.
     */
    public function getSetting($contextId, $name, $default = null)
    {
        $val = parent::getSetting($contextId, $name);
        if ($val === null) {
            $defaults = [
                'inPressEnabled' => true,
                'pageTitle' => '',
                'badgeText' => '',
                'introNotice' => '',
                'detectionMode' => 'scheduled_unpublished',
                'designatedIssueId' => 0,
                'showGalleys' => true,
                'proofNotice' => '',
                'showAbstract' => true,
                'showExpectedIssue' => true,
                'showAcceptanceDate' => true,
                'showDoi' => true,
                'showOnHomepage' => true,
                'homepageItemCount' => 3,
                'enableFeed' => true,
            ];

            if ($default !== null) {
                return $default;
            }

            if (array_key_exists($name, $defaults)) {
                return $defaults[$name];
            }
        }

        return $val;
    }

    /**
     * Hook into TemplateManager to inject CSS/JS and optional homepage block
     */
    public function callbackTemplateDisplay(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1] ?? '';
        $request = Application::get()->getRequest();
        $context = $request ? $request->getContext() : null;

        if (!$context) {
            return false;
        }

        $contextId = (int) $context->getId();

        // Register frontend stylesheets and scripts
        $templateMgr->addStyleSheet(
            'inPressFrontendCss',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/inPress.css',
            ['contexts' => ['frontend']]
        );

        $templateMgr->addJavaScript(
            'inPressFrontendJs',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/inPress.js',
            ['contexts' => ['frontend']]
        );

        // Inject homepage section if viewing journal index
        $isIndexTemplate = in_array($template, [
            'frontend/pages/indexJournal.tpl',
            'frontend/pages/index.tpl',
            'templates/frontend/pages/indexJournal.tpl',
            'templates/frontend/pages/index.tpl'
        ], true);

        if ($isIndexTemplate
            && (bool) $this->getSetting($contextId, 'inPressEnabled')
            && (bool) $this->getSetting($contextId, 'showOnHomepage')
        ) {
            $service = new InPressService($this);
            $allArticles = $service->getInPressSubmissions($contextId);
            $count = max(1, min(20, (int) ($this->getSetting($contextId, 'homepageItemCount') ?: 3)));
            $homeArticles = array_slice($allArticles, 0, $count);

            if (!empty($homeArticles)) {
                $pageTitle = $this->getSetting($contextId, 'pageTitle')
                    ?: __('plugins.generic.inPressPlugin.pageTitle');
                $badgeText = $this->pluginSettingsFallback($contextId, 'badgeText', 'plugins.generic.inPressPlugin.badgeText');
                $proofNotice = $this->pluginSettingsFallback($contextId, 'proofNotice', 'plugins.generic.inPressPlugin.proofNotice');

                $templateMgr->assign([
                    'inPressHomeArticles' => $homeArticles,
                    'inPressPageUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'inpress'),
                    'inPressTitle' => $pageTitle,
                    'inPressBadgeText' => $badgeText,
                    'inPressProofNotice' => $proofNotice,
                    'inPressShowGalleys' => (bool) $this->getSetting($contextId, 'showGalleys'),
                    'inPressShowExpectedIssue' => (bool) $this->getSetting($contextId, 'showExpectedIssue'),
                    'inPressShowAcceptanceDate' => (bool) $this->getSetting($contextId, 'showAcceptanceDate'),
                    'inPressShowDoi' => (bool) $this->getSetting($contextId, 'showDoi'),
                ]);

                // Append to additionalHomeContent so standard OJS themes automatically render it
                $existingContent = (string) $templateMgr->getTemplateVars('additionalHomeContent');
                $inPressHtml = $templateMgr->fetch($this->getTemplateResource('frontend/homepageBlock.tpl'));
                $templateMgr->assign('additionalHomeContent', $existingContent . "\n" . $inPressHtml);
            }
        }

        return false;
    }

    /**
     * Register Navigation Menu Item type for "In Press"
     */
    public function callbackNavMenuItemTypes(string $hookName, array $args): bool
    {
        $types = &$args[0];
        $types[self::NMI_TYPE_IN_PRESS] = [
            'title' => 'plugins.generic.inPressPlugin.navMenuItem',
            'description' => 'plugins.generic.inPressPlugin.navMenuItem.description',
        ];
        return false;
    }

    /**
     * Set dynamic URL when "In Press" Navigation Menu Item is rendered
     */
    public function callbackNavMenuDisplayItem(string $hookName, array $args): bool
    {
        $navigationMenuItem = &$args[0];
        if ($navigationMenuItem->getType() === self::NMI_TYPE_IN_PRESS) {
            $request = Application::get()->getRequest();
            $navigationMenuItem->setUrl($request->getDispatcher()->url(
                $request,
                PKPApplication::ROUTE_PAGE,
                null,
                'inpress'
            ));
        }
        return false;
    }

    /**
     * Safe setting getter with fallback to translation key
     */
    protected function pluginSettingsFallback(int $contextId, string $settingKey, string $transKey): string
    {
        $val = (string) $this->getSetting($contextId, $settingKey);
        return !empty($val) ? $val : __($transKey);
    }
}
