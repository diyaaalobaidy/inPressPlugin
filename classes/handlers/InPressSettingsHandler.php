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
            'inPressEnabled' => (bool) $this->plugin->getSetting($contextId, 'inPressEnabled'),
            'pageTitle' => $this->plugin->getSetting($contextId, 'pageTitle') ?: __('plugins.generic.inPressPlugin.pageTitle'),
            'badgeText' => $this->plugin->getSetting($contextId, 'badgeText') ?: __('plugins.generic.inPressPlugin.badgeText'),
            'introNotice' => $this->plugin->getSetting($contextId, 'introNotice') ?: __('plugins.generic.inPressPlugin.introNotice'),
            'detectionMode' => $this->plugin->getSetting($contextId, 'detectionMode') ?: 'scheduled_unpublished',
            'designatedIssueId' => (int) $this->plugin->getSetting($contextId, 'designatedIssueId'),
            'showGalleys' => (bool) $this->plugin->getSetting($contextId, 'showGalleys'),
            'proofNotice' => $this->plugin->getSetting($contextId, 'proofNotice') ?: __('plugins.generic.inPressPlugin.proofNotice'),
            'showAbstract' => (bool) $this->plugin->getSetting($contextId, 'showAbstract'),
            'showExpectedIssue' => (bool) $this->plugin->getSetting($contextId, 'showExpectedIssue'),
            'showAcceptanceDate' => (bool) $this->plugin->getSetting($contextId, 'showAcceptanceDate'),
            'showDoi' => (bool) $this->plugin->getSetting($contextId, 'showDoi'),
            'showOnHomepage' => (bool) $this->plugin->getSetting($contextId, 'showOnHomepage'),
            'homepageItemCount' => (int) ($this->plugin->getSetting($contextId, 'homepageItemCount') ?: 3),
            'enableFeed' => (bool) $this->plugin->getSetting($contextId, 'enableFeed'),
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

        $this->plugin->updateSetting($contextId, 'inPressEnabled', (bool) $request->getUserVar('inPressEnabled'), 'bool');
        $this->plugin->updateSetting($contextId, 'pageTitle', (string) $request->getUserVar('pageTitle'), 'string');
        $this->plugin->updateSetting($contextId, 'badgeText', (string) $request->getUserVar('badgeText'), 'string');
        $this->plugin->updateSetting($contextId, 'introNotice', (string) $request->getUserVar('introNotice'), 'string');
        $this->plugin->updateSetting($contextId, 'detectionMode', (string) $request->getUserVar('detectionMode') ?: 'scheduled_unpublished', 'string');
        $this->plugin->updateSetting($contextId, 'designatedIssueId', (int) $request->getUserVar('designatedIssueId'), 'int');
        $this->plugin->updateSetting($contextId, 'showGalleys', (bool) $request->getUserVar('showGalleys'), 'bool');
        $this->plugin->updateSetting($contextId, 'proofNotice', (string) $request->getUserVar('proofNotice'), 'string');
        $this->plugin->updateSetting($contextId, 'showAbstract', (bool) $request->getUserVar('showAbstract'), 'bool');
        $this->plugin->updateSetting($contextId, 'showExpectedIssue', (bool) $request->getUserVar('showExpectedIssue'), 'bool');
        $this->plugin->updateSetting($contextId, 'showAcceptanceDate', (bool) $request->getUserVar('showAcceptanceDate'), 'bool');
        $this->plugin->updateSetting($contextId, 'showDoi', (bool) $request->getUserVar('showDoi'), 'bool');
        $this->plugin->updateSetting($contextId, 'showOnHomepage', (bool) $request->getUserVar('showOnHomepage'), 'bool');
        $this->plugin->updateSetting($contextId, 'homepageItemCount', max(1, min(20, (int) $request->getUserVar('homepageItemCount'))), 'int');
        $this->plugin->updateSetting($contextId, 'enableFeed', (bool) $request->getUserVar('enableFeed'), 'bool');

        return new JSONMessage(true);
    }
}
