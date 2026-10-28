<?php

/**
 * @file plugins/generic/inPressPlugin/classes/handlers/InPressSettingsHandler.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class InPressSettingsHandler
 * @ingroup plugins_generic_inPressPlugin
 *
 * @brief Controller for managing In Press plugin configuration in OJS 3.5.
 */

namespace APP\plugins\generic\inPressPlugin\classes\handlers;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\inPressPlugin\classes\InPressService;
use APP\plugins\generic\inPressPlugin\InPressPlugin;
use PKP\core\JSONMessage;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;
use PKP\template\PKPTemplateManager;

class InPressSettingsHandler extends Handler
{
    /** @var InPressPlugin|null */
    protected $plugin;

    /** @var InPressService */
    protected $service;

    public function __construct($plugin = null)
    {
        parent::__construct();
        $this->plugin = $plugin ?: PluginRegistry::getPlugin('generic', 'inPressPlugin');
        $this->service = new InPressService($this->plugin);

        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['index', 'settings', 'save']
        );
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Display settings modal
     */
    public function settings($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();

        $unpublishedIssues = $this->service->getUnpublishedIssues($contextId);

        $templateMgr = PKPTemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'inPressEnabled' => (bool) $this->plugin->getSetting($contextId, 'inPressEnabled', true),
            'pageTitle' => $this->plugin->getSetting($contextId, 'pageTitle') ?: __('plugins.generic.inPressPlugin.pageTitle'),
            'badgeText' => $this->plugin->getSetting($contextId, 'badgeText') ?: __('plugins.generic.inPressPlugin.badgeText'),
            'introNotice' => $this->plugin->getSetting($contextId, 'introNotice') ?: __('plugins.generic.inPressPlugin.introNotice'),
            'detectionMode' => $this->plugin->getSetting($contextId, 'detectionMode') ?: 'scheduled_unpublished',
            'designatedIssueId' => (int) $this->plugin->getSetting($contextId, 'designatedIssueId', 0),
            'showGalleys' => (bool) $this->plugin->getSetting($contextId, 'showGalleys', true),
            'proofNotice' => $this->plugin->getSetting($contextId, 'proofNotice') ?: __('plugins.generic.inPressPlugin.proofNotice'),
            'showAbstract' => (bool) $this->plugin->getSetting($contextId, 'showAbstract', true),
            'showExpectedIssue' => (bool) $this->plugin->getSetting($contextId, 'showExpectedIssue', true),
            'showAcceptanceDate' => (bool) $this->plugin->getSetting($contextId, 'showAcceptanceDate', true),
            'showDoi' => (bool) $this->plugin->getSetting($contextId, 'showDoi', true),
            'showOnHomepage' => (bool) $this->plugin->getSetting($contextId, 'showOnHomepage', true),
            'homepageItemCount' => (int) ($this->plugin->getSetting($contextId, 'homepageItemCount') ?: 3),
            'enableFeed' => (bool) $this->plugin->getSetting($contextId, 'enableFeed', true),
            'unpublishedIssues' => $unpublishedIssues,
        ]);

        return new JSONMessage(true, $templateMgr->fetch($this->plugin->getTemplateResource('settings/settings.tpl')));
    }

    /**
     * Save settings form
     */
    public function save($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();

        $this->plugin->updateSetting($contextId, 'inPressEnabled', (bool) $request->getUserVar('inPressEnabled'));
        $this->plugin->updateSetting($contextId, 'pageTitle', (string) $request->getUserVar('pageTitle'));
        $this->plugin->updateSetting($contextId, 'badgeText', (string) $request->getUserVar('badgeText'));
        $this->plugin->updateSetting($contextId, 'introNotice', (string) $request->getUserVar('introNotice'));
        $this->plugin->updateSetting($contextId, 'detectionMode', (string) $request->getUserVar('detectionMode') ?: 'scheduled_unpublished');
        $this->plugin->updateSetting($contextId, 'designatedIssueId', (int) $request->getUserVar('designatedIssueId'));
        $this->plugin->updateSetting($contextId, 'showGalleys', (bool) $request->getUserVar('showGalleys'));
        $this->plugin->updateSetting($contextId, 'proofNotice', (string) $request->getUserVar('proofNotice'));
        $this->plugin->updateSetting($contextId, 'showAbstract', (bool) $request->getUserVar('showAbstract'));
        $this->plugin->updateSetting($contextId, 'showExpectedIssue', (bool) $request->getUserVar('showExpectedIssue'));
        $this->plugin->updateSetting($contextId, 'showAcceptanceDate', (bool) $request->getUserVar('showAcceptanceDate'));
        $this->plugin->updateSetting($contextId, 'showDoi', (bool) $request->getUserVar('showDoi'));
        $this->plugin->updateSetting($contextId, 'showOnHomepage', (bool) $request->getUserVar('showOnHomepage'));
        $this->plugin->updateSetting($contextId, 'homepageItemCount', max(1, min(20, (int) $request->getUserVar('homepageItemCount'))));
        $this->plugin->updateSetting($contextId, 'enableFeed', (bool) $request->getUserVar('enableFeed'));

        return new JSONMessage(true);
    }
}
