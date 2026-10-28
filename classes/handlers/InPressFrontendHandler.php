<?php

/**
 * @file plugins/generic/inPressPlugin/classes/handlers/InPressFrontendHandler.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class InPressFrontendHandler
 * @ingroup plugins_generic_inPressPlugin
 *
 * @brief Public frontend controller for browsing In-Press articles, viewing
 *        forthcoming article landing pages, downloading proofs, and RSS feeds.
 */

namespace APP\plugins\generic\inPressPlugin\classes\handlers;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\inPressPlugin\classes\InPressService;
use APP\plugins\generic\inPressPlugin\InPressPlugin;
use PKP\core\PKPApplication;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\template\PKPTemplateManager;

class InPressFrontendHandler extends Handler
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
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextRequiredPolicy($request));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Main In Press listing page
     */
    public function index($args, $request)
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();

        // Check if plugin is enabled
        if ($this->plugin && !$this->plugin->getSetting($contextId, 'inPressEnabled', true)) {
            $request->redirect(null, 'index');
            return;
        }

        $this->setupTemplate($request);
        $templateMgr = PKPTemplateManager::getManager($request);

        $articles = $this->service->getInPressSubmissions($contextId);
        $sections = $this->service->extractUniqueSections($articles);

        $pageTitle = $this->plugin->getSetting($contextId, 'pageTitle')
            ?: __('plugins.generic.inPressPlugin.pageTitle');
        $introNotice = $this->plugin->getSetting($contextId, 'introNotice')
            ?: __('plugins.generic.inPressPlugin.introNotice');
        $badgeText = $this->plugin->getSetting($contextId, 'badgeText')
            ?: __('plugins.generic.inPressPlugin.badgeText');
        $proofNotice = $this->plugin->getSetting($contextId, 'proofNotice')
            ?: __('plugins.generic.inPressPlugin.proofNotice');

        $showGalleys = (bool) $this->plugin->getSetting($contextId, 'showGalleys', true);
        $showAbstract = (bool) $this->plugin->getSetting($contextId, 'showAbstract', true);
        $showExpectedIssue = (bool) $this->plugin->getSetting($contextId, 'showExpectedIssue', true);
        $showAcceptanceDate = (bool) $this->plugin->getSetting($contextId, 'showAcceptanceDate', true);
        $showDoi = (bool) $this->plugin->getSetting($contextId, 'showDoi', true);
        $enableFeed = (bool) $this->plugin->getSetting($contextId, 'enableFeed', true);

        $feedUrl = $enableFeed ? $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_PAGE,
            null,
            'inpress',
            'feed'
        ) : '';

        // Inject assets
        $templateMgr->addStyleSheet(
            'inPressCss',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/css/inPress.css',
            ['contexts' => ['frontend']]
        );
        $templateMgr->addJavaScript(
            'inPressJs',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/inPress.js',
            ['contexts' => ['frontend']]
        );

        $templateMgr->assign([
            'pageTitle' => $pageTitle,
            'pageTitleTranslated' => $pageTitle,
            'inPressArticles' => $articles,
            'sections' => $sections,
            'introNotice' => $introNotice,
            'badgeText' => $badgeText,
            'proofNotice' => $proofNotice,
            'showGalleys' => $showGalleys,
            'showAbstract' => $showAbstract,
            'showExpectedIssue' => $showExpectedIssue,
            'showAcceptanceDate' => $showAcceptanceDate,
            'showDoi' => $showDoi,
            'feedUrl' => $feedUrl,
            'currentContext' => $context,
        ]);

        $templateMgr->display($this->plugin->getTemplateResource('frontend/inpress.tpl'));
    }

    /**
     * Dedicated landing page for an In Press article
     */
    public function view($args, $request)
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();
        $submissionId = (int) ($args[0] ?? $request->getUserVar('submissionId'));

        if (!$submissionId) {
            $request->redirect(null, 'inpress');
            return;
        }

        $article = $this->service->getInPressSubmissionById($contextId, $submissionId);
        if (!$article) {
            // Not found or no longer in press
            $request->redirect(null, 'inpress');
            return;
        }

        $this->setupTemplate($request);
        $templateMgr = PKPTemplateManager::getManager($request);

        $citation = $this->service->formatCitation($article, $context);
        $badgeText = $this->plugin->getSetting($contextId, 'badgeText')
            ?: __('plugins.generic.inPressPlugin.badgeText');
        $proofNotice = $this->plugin->getSetting($contextId, 'proofNotice')
            ?: __('plugins.generic.inPressPlugin.proofNotice');
        $showGalleys = (bool) $this->plugin->getSetting($contextId, 'showGalleys', true);
        $showExpectedIssue = (bool) $this->plugin->getSetting($contextId, 'showExpectedIssue', true);

        // Inject Google Scholar & Dublin Core meta tags for forthcoming indexing
        $this->injectMetaTags($templateMgr, $article, $context, $request);

        // Inject assets
        $templateMgr->addStyleSheet(
            'inPressCss',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/css/inPress.css',
            ['contexts' => ['frontend']]
        );
        $templateMgr->addJavaScript(
            'inPressJs',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/inPress.js',
            ['contexts' => ['frontend']]
        );

        $templateMgr->assign([
            'pageTitle' => $article['fullTitle'],
            'pageTitleTranslated' => $article['fullTitle'],
            'article' => $article,
            'citation' => $citation,
            'badgeText' => $badgeText,
            'proofNotice' => $proofNotice,
            'showGalleys' => $showGalleys,
            'showExpectedIssue' => $showExpectedIssue,
            'inPressListUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'inpress'),
            'currentContext' => $context,
        ]);

        $templateMgr->display($this->plugin->getTemplateResource('frontend/article.tpl'));
    }

    /**
     * Securely download or display an In Press galley file (PDF)
     */
    public function download($args, $request)
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();
        $submissionId = (int) ($args[0] ?? 0);
        $galleyId = (int) ($args[1] ?? 0);

        if (!$submissionId || !$galleyId) {
            $request->redirect(null, 'inpress');
            return;
        }

        // Verify that galleys are enabled in settings
        if (!$this->plugin->getSetting($contextId, 'showGalleys', true)) {
            $request->redirect(null, 'inpress', 'view', [$submissionId]);
            return;
        }

        // Verify the article is validly in-press for this context
        $article = $this->service->getInPressSubmissionById($contextId, $submissionId);
        if (!$article) {
            $request->redirect(null, 'inpress');
            return;
        }

        $fileData = $this->service->resolveGalleyFile($submissionId, $galleyId);
        if (!$fileData) {
            $request->redirect(null, 'inpress', 'view', [$submissionId]);
            return;
        }

        if ($fileData['type'] === 'remote') {
            $request->redirectUrl($fileData['url']);
            return;
        }

        if ($fileData['type'] === 'local' && file_exists($fileData['path'])) {
            $path = $fileData['path'];
            $mime = $fileData['mime'] ?: 'application/pdf';
            $name = $fileData['name'] ?: ('article_' . $submissionId . '_inpress.pdf');

            // Send headers for streaming the file
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . basename($name) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private, max-age=10800, pre-check=10800');
            header('Pragma: private');

            readfile($path);
            exit;
        }

        $request->redirect(null, 'inpress', 'view', [$submissionId]);
    }

    /**
     * Generate standard RSS 2.0 feed for In Press articles
     */
    public function feed($args, $request)
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();

        if (!$this->plugin->getSetting($contextId, 'enableFeed', true)) {
            $request->redirect(null, 'inpress');
            return;
        }

        $articles = $this->service->getInPressSubmissions($contextId);
        $journalName = $context->getLocalizedName() ?: $context->getData('name');
        $channelLink = $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'inpress');

        header('Content-Type: application/rss+xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
        echo '  <channel>' . "\n";
        echo '    <title>' . htmlspecialchars($journalName . ' - ' . __('plugins.generic.inPressPlugin.feedTitle')) . '</title>' . "\n";
        echo '    <link>' . htmlspecialchars($channelLink) . '</link>' . "\n";
        echo '    <description>' . htmlspecialchars(__('plugins.generic.inPressPlugin.introNotice')) . '</description>' . "\n";
        echo '    <language>' . htmlspecialchars($context->getPrimaryLocale() ?? 'en') . '</language>' . "\n";
        echo '    <atom:link href="' . htmlspecialchars($request->getCompleteUrl()) . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($articles as $art) {
            $pubDate = !empty($art['dateAccepted']) ? date(DATE_RSS, strtotime($art['dateAccepted'])) : date(DATE_RSS);
            echo '    <item>' . "\n";
            echo '      <title>' . htmlspecialchars($art['fullTitle']) . '</title>' . "\n";
            echo '      <link>' . htmlspecialchars($art['viewUrl']) . '</link>' . "\n";
            echo '      <guid isPermaLink="true">' . htmlspecialchars($art['viewUrl']) . '</guid>' . "\n";
            echo '      <dc:creator>' . htmlspecialchars($art['authorString']) . '</dc:creator>' . "\n";
            echo '      <pubDate>' . $pubDate . '</pubDate>' . "\n";
            if (!empty($art['abstract'])) {
                echo '      <description><![CDATA[' . strip_tags($art['abstract']) . ']]></description>' . "\n";
            }
            echo '    </item>' . "\n";
        }

        echo '  </channel>' . "\n";
        echo '</rss>' . "\n";
        exit;
    }

    /**
     * Inject Google Scholar & Dublin Core metadata into HTML head
     */
    protected function injectMetaTags($templateMgr, array $article, $context, $request): void
    {
        $journalTitle = $context->getLocalizedName() ?: $context->getData('name');
        $onlineIssn = $context->getData('onlineIssn') ?: $context->getData('printIssn');

        $tags = [
            '<meta name="citation_title" content="' . htmlspecialchars($article['fullTitle']) . '" />',
            '<meta name="citation_journal_title" content="' . htmlspecialchars($journalTitle) . '" />',
            '<meta name="citation_publication_date" content="' . htmlspecialchars($article['dateAccepted'] ?: date('Y-m-d')) . '" />',
            '<meta name="dc.title" content="' . htmlspecialchars($article['fullTitle']) . '" />',
            '<meta name="dc.date" content="' . htmlspecialchars($article['dateAccepted'] ?: date('Y-m-d')) . '" />',
            '<meta name="dc.type" content="Text.Article" />',
            '<meta name="dc.status" content="In Press" />',
        ];

        if ($onlineIssn) {
            $tags[] = '<meta name="citation_issn" content="' . htmlspecialchars($onlineIssn) . '" />';
        }

        if (!empty($article['doi'])) {
            $tags[] = '<meta name="citation_doi" content="' . htmlspecialchars($article['doi']) . '" />';
            $tags[] = '<meta name="dc.identifier" content="' . htmlspecialchars($article['doi']) . '" />';
        }

        foreach ($article['authors'] as $author) {
            $tags[] = '<meta name="citation_author" content="' . htmlspecialchars($author['fullName']) . '" />';
            $tags[] = '<meta name="dc.creator" content="' . htmlspecialchars($author['fullName']) . '" />';
            if (!empty($author['affiliation'])) {
                $tags[] = '<meta name="citation_author_institution" content="' . htmlspecialchars($author['affiliation']) . '" />';
            }
            if (!empty($author['orcid'])) {
                $tags[] = '<meta name="citation_author_orcid" content="' . htmlspecialchars($author['orcid']) . '" />';
            }
        }

        if (!empty($article['galleys'])) {
            foreach ($article['galleys'] as $g) {
                if ($g['isPdf'] && !empty($g['downloadUrl'])) {
                    $tags[] = '<meta name="citation_pdf_url" content="' . htmlspecialchars($g['downloadUrl']) . '" />';
                    break;
                }
            }
        }

        $html = implode("\n", $tags);
        $templateMgr->addHeader('inPressMetaTags', $html);
    }
}
