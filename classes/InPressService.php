<?php

/**
 * @file plugins/generic/inPressPlugin/classes/InPressService.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class InPressService
 * @ingroup plugins_generic_inPressPlugin
 *
 * @brief Service class for discovering, filtering, formatting, and presenting
 *        In-Press / Ahead-of-Print articles for OJS 3.5.
 */

namespace APP\plugins\generic\inPressPlugin\classes;

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\config\Config;
use PKP\core\PKPApplication;
use PKP\submission\PKPSubmission;

class InPressService
{
    /** @var \APP\plugins\generic\inPressPlugin\InPressPlugin */
    protected $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Retrieve In Press submissions for a given journal context
     *
     * @param int $contextId Journal context ID
     * @param array $overrideSettings Optional settings overrides
     * @return array List of formatted in-press article arrays
     */
    public function getInPressSubmissions(int $contextId, array $overrideSettings = []): array
    {
        $mode = $overrideSettings['detectionMode'] ?? $this->plugin->getSetting($contextId, 'detectionMode') ?: 'scheduled_unpublished';
        $designatedIssueId = (int) ($overrideSettings['designatedIssueId'] ?? $this->plugin->getSetting($contextId, 'designatedIssueId') ?: 0);

        $submissionIds = $this->queryInPressSubmissionIds($contextId, $mode, $designatedIssueId);
        if (empty($submissionIds)) {
            return [];
        }

        $articles = [];
        foreach ($submissionIds as $subId) {
            $article = $this->formatSubmissionData($contextId, (int) $subId);
            if ($article) {
                $articles[] = $article;
            }
        }

        // Sort articles by acceptance date descending, then submission ID descending
        usort($articles, function ($a, $b) {
            $dateA = $a['dateAcceptedTimestamp'] ?: $a['dateSubmittedTimestamp'];
            $dateB = $b['dateAcceptedTimestamp'] ?: $b['dateSubmittedTimestamp'];
            if ($dateA === $dateB) {
                return $b['id'] <=> $a['id'];
            }
            return $dateB <=> $dateA;
        });

        return $articles;
    }

    /**
     * Retrieve a single In Press article by submission ID
     */
    public function getInPressSubmissionById(int $contextId, int $submissionId): ?array
    {
        $articles = $this->getInPressSubmissions($contextId);
        foreach ($articles as $article) {
            if ((int) $article['id'] === $submissionId) {
                return $article;
            }
        }
        return null;
    }

    /**
     * Query submission IDs that qualify as "In Press" based on selected mode
     */
    protected function queryInPressSubmissionIds(int $contextId, string $mode, int $designatedIssueId = 0): array
    {
        try {
            if (!Schema::hasTable('submissions') || !Schema::hasTable('publications')) {
                return [];
            }

            // Mode 3 check: If specific issue mode is selected without a valid issue, return empty
            if ($mode === 'specific_issue' && $designatedIssueId <= 0) {
                return [];
            }

            // Gather unpublished and published issue IDs for this journal context
            $unpublishedIssueIds = [];
            $publishedIssueIds = [];
            if (Schema::hasTable('issues')) {
                $issues = DB::table('issues')
                    ->where('journal_id', $contextId)
                    ->select('issue_id', 'published')
                    ->get();
                foreach ($issues as $iss) {
                    if ((int) $iss->published === 1) {
                        $publishedIssueIds[] = (int) $iss->issue_id;
                    } else {
                        $unpublishedIssueIds[] = (int) $iss->issue_id;
                    }
                }
            }

            $unpublishedIssueStrings = array_map('strval', $unpublishedIssueIds);
            $publishedIssueStrings = array_map('strval', $publishedIssueIds);

            $hasDirectIssueColumn = Schema::hasColumn('publications', 'issue_id');

            // Build base query on active, non-declined, non-published submissions
            $query = DB::table('submissions as s')
                ->join('publications as p', 'p.publication_id', '=', 's.current_publication_id')
                ->where('s.context_id', $contextId)
                ->where('s.status', '!=', 4)  // Exclude STATUS_DECLINED
                ->where('s.status', '!=', 3)  // Exclude STATUS_PUBLISHED
                ->where('p.status', '!=', 3); // Exclude published publications

            // Join publication_settings for issueId setting
            $query->leftJoin('publication_settings as ps_issue', function ($join) {
                $join->on('ps_issue.publication_id', '=', 'p.publication_id')
                     ->where('ps_issue.setting_name', '=', 'issueId');
            });

            // Always strictly exclude any submissions assigned to an already published issue
            if (!empty($publishedIssueIds)) {
                $query->where(function ($q) use ($publishedIssueIds, $publishedIssueStrings, $hasDirectIssueColumn) {
                    if ($hasDirectIssueColumn) {
                        $q->whereNotIn('p.issue_id', $publishedIssueIds)
                          ->orWhereNull('p.issue_id');
                    }
                    $q->where(function ($subQ) use ($publishedIssueStrings) {
                        $subQ->whereNotIn('ps_issue.setting_value', $publishedIssueStrings)
                             ->orWhereNull('ps_issue.setting_value');
                    });
                });
            }

            // Apply selected detection mode filter
            if ($mode === 'specific_issue') {
                // Must be assigned specifically to the designated issue
                $query->where(function ($q) use ($designatedIssueId, $hasDirectIssueColumn) {
                    if ($hasDirectIssueColumn) {
                        $q->where('p.issue_id', '=', $designatedIssueId);
                    }
                    $q->orWhere('ps_issue.setting_value', '=', (string) $designatedIssueId);
                });
            } elseif ($mode === 'all_accepted_production') {
                // All accepted submissions in production/copyediting or scheduled in future issues
                $query->where(function ($q) use ($unpublishedIssueIds, $unpublishedIssueStrings, $hasDirectIssueColumn) {
                    // Scheduled status
                    $q->where('s.status', '=', 5) // STATUS_SCHEDULED
                      ->orWhere('p.status', '=', 5);

                    // Or assigned to any unpublished issue
                    if (!empty($unpublishedIssueIds)) {
                        if ($hasDirectIssueColumn) {
                            $q->orWhereIn('p.issue_id', $unpublishedIssueIds);
                        }
                        $q->orWhereIn('ps_issue.setting_value', $unpublishedIssueStrings);
                    }

                    // Or in Production workflow stage
                    $q->orWhere('s.stage_id', '=', 5); // WORKFLOW_STAGE_ID_PRODUCTION

                    // Or accepted editorial decision in edit_decisions
                    if (Schema::hasTable('edit_decisions')) {
                        $q->orWhereExists(function ($subQ) {
                            $subQ->select(DB::raw(1))
                                 ->from('edit_decisions as ed')
                                 ->whereColumn('ed.submission_id', 's.submission_id')
                                 ->where('ed.decision', '=', 1); // SUBMISSION_EDITOR_DECISION_ACCEPT
                        });
                    }

                    // Or has explicit dateAccepted setting
                    $q->orWhereExists(function ($subQ) {
                        $subQ->select(DB::raw(1))
                             ->from('publication_settings as ps_acc')
                             ->whereColumn('ps_acc.publication_id', 'p.publication_id')
                             ->where('ps_acc.setting_name', '=', 'dateAccepted')
                             ->whereNotNull('ps_acc.setting_value')
                             ->where('ps_acc.setting_value', '!=', '');
                    });
                });
            } else {
                // Default: 'scheduled_unpublished'
                // Must be scheduled or assigned to an unpublished / future issue
                $query->where(function ($q) use ($unpublishedIssueIds, $unpublishedIssueStrings, $hasDirectIssueColumn) {
                    $q->where('s.status', '=', 5) // STATUS_SCHEDULED
                      ->orWhere('p.status', '=', 5);

                    if (!empty($unpublishedIssueIds)) {
                        if ($hasDirectIssueColumn) {
                            $q->orWhereIn('p.issue_id', $unpublishedIssueIds);
                        }
                        $q->orWhereIn('ps_issue.setting_value', $unpublishedIssueStrings);
                    }
                });
            }

            $results = $query->distinct()
                ->pluck('s.submission_id')
                ->toArray();

            return array_map('intval', $results);
        } catch (\Throwable $e) {
            error_log('[InPressPlugin] queryInPressSubmissionIds error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format a submission and its publication into a normalized article array
     */
    public function formatSubmissionData(int $contextId, int $submissionId): ?array
    {
        $request = Application::get()->getRequest();
        $journal = $request ? $request->getContext() : null;
        $primaryLocale = $journal ? $journal->getPrimaryLocale() : 'en';

        // 1. Try high-level Repo first
        if (class_exists(Repo::class) && method_exists(Repo::class, 'submission')) {
            try {
                $submission = Repo::submission()->get($submissionId);
                if ($submission && (int) $submission->getData('contextId') === $contextId) {
                    $publication = $submission->getCurrentPublication();
                    if ($publication) {
                        return $this->formatFromModels($submission, $publication, $contextId, $primaryLocale);
                    }
                }
            } catch (\Throwable $e) {
                // Fall back to direct DB extraction below
            }
        }

        // 2. Direct database extraction fallback
        return $this->formatFromDatabase($submissionId, $contextId, $primaryLocale);
    }

    /**
     * Format submission data using OJS Domain Models
     */
    protected function formatFromModels($submission, $publication, int $contextId, string $primaryLocale): array
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request ? $request->getDispatcher() : null;

        $submissionId = (int) $submission->getId();
        $publicationId = (int) $publication->getId();

        // Localized Title & Subtitle
        $title = $publication->getLocalizedTitle($primaryLocale);
        $subtitle = $publication->getLocalizedData('subtitle', $primaryLocale);
        $fullTitle = $title . ($subtitle ? ': ' . $subtitle : '');

        // Abstract
        $abstract = (string) $publication->getLocalizedData('abstract', $primaryLocale);

        // Authors
        $authors = [];
        $authorNames = [];
        $rawAuthors = $publication->getData('authors') ?? [];
        foreach ($rawAuthors as $author) {
            $fullName = method_exists($author, 'getFullName')
                ? $author->getFullName(true, false, $primaryLocale)
                : ($author->getData('givenName') . ' ' . $author->getData('familyName'));
            $fullName = trim($fullName);
            if (!empty($fullName)) {
                $authorNames[] = $fullName;
                $authors[] = [
                    'id' => (int) $author->getId(),
                    'fullName' => $fullName,
                    'givenName' => $author->getLocalizedData('givenName', $primaryLocale),
                    'familyName' => $author->getLocalizedData('familyName', $primaryLocale),
                    'affiliation' => $author->getLocalizedData('affiliation', $primaryLocale),
                    'orcid' => (string) $author->getData('orcid'),
                    'email' => (string) $author->getData('email'),
                    'isPrimary' => (bool) $author->getData('primaryContact'),
                ];
            }
        }
        $authorString = implode(', ', $authorNames);

        // Section
        $sectionId = (int) $publication->getData('sectionId');
        $sectionTitle = '';
        if ($sectionId && class_exists(Repo::class) && method_exists(Repo::class, 'section')) {
            $section = Repo::section()->get($sectionId);
            $sectionTitle = $section ? (string) $section->getLocalizedTitle($primaryLocale) : '';
        }

        // Future Issue
        $issueId = (int) ($publication->getData('issueId') ?: $publication->getData('issue_id'));
        $futureIssue = null;
        if ($issueId && class_exists(Repo::class) && method_exists(Repo::class, 'issue')) {
            $issue = Repo::issue()->get($issueId);
            if ($issue) {
                $futureIssue = [
                    'id' => $issueId,
                    'volume' => $issue->getVolume(),
                    'number' => $issue->getNumber(),
                    'year' => $issue->getYear(),
                    'title' => $issue->getLocalizedTitle($primaryLocale),
                    'identification' => $this->formatIssueIdentification($issue),
                ];
            }
        }

        // Dates
        $dateSubmitted = (string) $submission->getData('dateSubmitted');
        $dateAccepted = (string) ($publication->getData('dateAccepted') ?: $submission->getData('dateLastActivity'));
        $dateScheduled = (string) $publication->getData('datePublished');

        // DOI
        $doi = '';
        if (method_exists($publication, 'getStoredPubId')) {
            $doi = (string) $publication->getStoredPubId('doi');
        }
        if (empty($doi) && method_exists($publication, 'getData')) {
            $doiObj = $publication->getData('doiObject');
            if ($doiObj && method_exists($doiObj, 'getDoi')) {
                $doi = (string) $doiObj->getDoi();
            }
        }

        // Galleys (Proofs)
        $galleys = [];
        $rawGalleys = $publication->getData('galleys') ?? [];
        if (empty($rawGalleys) && class_exists(Repo::class) && method_exists(Repo::class, 'galley')) {
            $rawGalleys = Repo::galley()->getCollector()->filterByPublicationIds([$publicationId])->getMany();
        }

        foreach ($rawGalleys as $galley) {
            $galleyId = (int) $galley->getId();
            $label = $galley->getLabel() ?: 'PDF';
            $downloadUrl = $dispatcher ? $dispatcher->url(
                $request,
                PKPApplication::ROUTE_PAGE,
                null,
                'inpress',
                'download',
                [$submissionId, $galleyId]
            ) : '';

            $galleys[] = [
                'id' => $galleyId,
                'label' => $label,
                'fileId' => (int) $galley->getData('submissionFileId'),
                'downloadUrl' => $downloadUrl,
                'isPdf' => (bool) $galley->isPdfGalley(),
            ];
        }

        // URLs
        $viewUrl = $dispatcher ? $dispatcher->url(
            $request,
            PKPApplication::ROUTE_PAGE,
            null,
            'inpress',
            'view',
            [$submissionId]
        ) : '';

        // Badge & Notice configuration
        $badgeText = $this->plugin->getSetting($contextId, 'badgeText') ?: __('plugins.generic.inPressPlugin.badgeText');
        $proofNotice = $this->plugin->getSetting($contextId, 'proofNotice') ?: __('plugins.generic.inPressPlugin.proofNotice');

        return [
            'id' => $submissionId,
            'publicationId' => $publicationId,
            'title' => $title,
            'subtitle' => $subtitle,
            'fullTitle' => $fullTitle,
            'abstract' => $abstract,
            'authors' => $authors,
            'authorString' => $authorString,
            'sectionId' => $sectionId,
            'sectionTitle' => $sectionTitle,
            'futureIssue' => $futureIssue,
            'dateSubmitted' => $dateSubmitted ? date('Y-m-d', strtotime($dateSubmitted)) : '',
            'dateAccepted' => $dateAccepted ? date('Y-m-d', strtotime($dateAccepted)) : '',
            'dateScheduled' => $dateScheduled ? date('Y-m-d', strtotime($dateScheduled)) : '',
            'dateAcceptedTimestamp' => $dateAccepted ? strtotime($dateAccepted) : 0,
            'dateSubmittedTimestamp' => $dateSubmitted ? strtotime($dateSubmitted) : 0,
            'doi' => $doi,
            'doiUrl' => $doi ? 'https://doi.org/' . ltrim($doi, '/') : '',
            'galleys' => $galleys,
            'viewUrl' => $viewUrl,
            'badgeText' => $badgeText,
            'proofNotice' => $proofNotice,
        ];
    }

    /**
     * Fallback formatter directly querying MySQL tables
     */
    protected function formatFromDatabase(int $submissionId, int $contextId, string $primaryLocale): ?array
    {
        try {
            $sub = DB::table('submissions')
                ->where('submission_id', $submissionId)
                ->where('context_id', $contextId)
                ->first();

            if (!$sub) {
                return null;
            }

            $pub = DB::table('publications')
                ->where('publication_id', $sub->current_publication_id)
                ->first();

            if (!$pub) {
                return null;
            }

            $pubId = (int) $pub->publication_id;

            // Fetch publication settings
            $pubSettings = DB::table('publication_settings')
                ->where('publication_id', $pubId)
                ->get()
                ->groupBy('setting_name');

            $getSetting = function (string $name) use ($pubSettings, $primaryLocale) {
                if (!$pubSettings->has($name)) {
                    return '';
                }
                $rows = $pubSettings->get($name);
                foreach ($rows as $r) {
                    if ($r->locale === $primaryLocale && !empty($r->setting_value)) {
                        return (string) $r->setting_value;
                    }
                }
                foreach ($rows as $r) {
                    if (!empty($r->setting_value)) {
                        return (string) $r->setting_value;
                    }
                }
                return '';
            };

            $title = $getSetting('title');
            $subtitle = $getSetting('subtitle');
            $fullTitle = $title . ($subtitle ? ': ' . $subtitle : '');
            $abstract = $getSetting('abstract');
            $dateAccepted = $getSetting('dateAccepted') ?: $sub->date_last_activity;
            $doi = $getSetting('pub-id::doi');

            // Authors
            $authors = [];
            $authorNames = [];
            $rawAuthors = DB::table('authors')
                ->where('publication_id', $pubId)
                ->orderBy('seq')
                ->get();

            foreach ($rawAuthors as $a) {
                $aSettings = DB::table('author_settings')
                    ->where('author_id', $a->author_id)
                    ->get()
                    ->groupBy('setting_name');

                $getAuthorSetting = function (string $sName) use ($aSettings, $primaryLocale) {
                    if (!$aSettings->has($sName)) {
                        return '';
                    }
                    $sRows = $aSettings->get($sName);
                    foreach ($sRows as $sr) {
                        if ($sr->locale === $primaryLocale && !empty($sr->setting_value)) {
                            return (string) $sr->setting_value;
                        }
                    }
                    foreach ($sRows as $sr) {
                        if (!empty($sr->setting_value)) {
                            return (string) $sr->setting_value;
                        }
                    }
                    return '';
                };

                $givenName = $getAuthorSetting('givenName') ?: ($a->given_name ?? '');
                $familyName = $getAuthorSetting('familyName') ?: ($a->family_name ?? '');
                $affiliation = $getAuthorSetting('affiliation');
                $orcid = (string) ($a->orcid ?? $getAuthorSetting('orcid'));
                $email = (string) ($a->email ?? '');
                $fullName = trim($givenName . ' ' . $familyName);

                if (!empty($fullName)) {
                    $authorNames[] = $fullName;
                    $authors[] = [
                        'id' => (int) $a->author_id,
                        'fullName' => $fullName,
                        'givenName' => $givenName,
                        'familyName' => $familyName,
                        'affiliation' => $affiliation,
                        'orcid' => $orcid,
                        'email' => $email,
                        'isPrimary' => (bool) ($a->primary_contact ?? false),
                    ];
                }
            }
            $authorString = implode(', ', $authorNames);

            // Section
            $sectionId = (int) $pub->section_id;
            $sectionTitle = '';
            if ($sectionId && Schema::hasTable('section_settings')) {
                $secRow = DB::table('section_settings')
                    ->where('section_id', $sectionId)
                    ->where('setting_name', 'title')
                    ->where(function ($q) use ($primaryLocale) {
                        $q->where('locale', $primaryLocale)->orWhere('locale', '');
                    })
                    ->first();
                $sectionTitle = $secRow ? (string) $secRow->setting_value : '';
            }

            // Future Issue
            $issueId = (int) ($pub->issue_id ?? $getSetting('issueId'));
            $futureIssue = null;
            if ($issueId && Schema::hasTable('issues')) {
                $issRow = DB::table('issues')->where('issue_id', $issueId)->first();
                if ($issRow) {
                    $futureIssue = [
                        'id' => $issueId,
                        'volume' => $issRow->volume,
                        'number' => $issRow->number,
                        'year' => $issRow->year,
                        'title' => '',
                        'identification' => sprintf('Vol. %s No. %s (%s)', $issRow->volume ?: '-', $issRow->number ?: '-', $issRow->year ?: '-'),
                    ];
                }
            }

            // Galleys
            $galleys = [];
            if (Schema::hasTable('publication_galleys')) {
                $request = Application::get()->getRequest();
                $dispatcher = $request ? $request->getDispatcher() : null;

                $galRows = DB::table('publication_galleys')
                    ->where('publication_id', $pubId)
                    ->orderBy('seq')
                    ->get();

                foreach ($galRows as $g) {
                    $galleyId = (int) $g->galley_id;
                    $label = (string) ($g->label ?: 'PDF');
                    $downloadUrl = $dispatcher ? $dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_PAGE,
                        null,
                        'inpress',
                        'download',
                        [$submissionId, $galleyId]
                    ) : '';

                    $galleys[] = [
                        'id' => $galleyId,
                        'label' => $label,
                        'fileId' => (int) ($g->submission_file_id ?? 0),
                        'downloadUrl' => $downloadUrl,
                        'isPdf' => (stripos($label, 'pdf') !== false),
                    ];
                }
            }

            $request = Application::get()->getRequest();
            $dispatcher = $request ? $request->getDispatcher() : null;
            $viewUrl = $dispatcher ? $dispatcher->url(
                $request,
                PKPApplication::ROUTE_PAGE,
                null,
                'inpress',
                'view',
                [$submissionId]
            ) : '';

            $badgeText = $this->plugin->getSetting($contextId, 'badgeText') ?: __('plugins.generic.inPressPlugin.badgeText');
            $proofNotice = $this->plugin->getSetting($contextId, 'proofNotice') ?: __('plugins.generic.inPressPlugin.proofNotice');

            return [
                'id' => $submissionId,
                'publicationId' => $pubId,
                'title' => $title,
                'subtitle' => $subtitle,
                'fullTitle' => $fullTitle,
                'abstract' => $abstract,
                'authors' => $authors,
                'authorString' => $authorString,
                'sectionId' => $sectionId,
                'sectionTitle' => $sectionTitle,
                'futureIssue' => $futureIssue,
                'dateSubmitted' => $sub->date_submitted ? date('Y-m-d', strtotime($sub->date_submitted)) : '',
                'dateAccepted' => $dateAccepted ? date('Y-m-d', strtotime($dateAccepted)) : '',
                'dateScheduled' => $pub->date_published ? date('Y-m-d', strtotime($pub->date_published)) : '',
                'dateAcceptedTimestamp' => $dateAccepted ? strtotime($dateAccepted) : 0,
                'dateSubmittedTimestamp' => $sub->date_submitted ? strtotime($sub->date_submitted) : 0,
                'doi' => $doi,
                'doiUrl' => $doi ? 'https://doi.org/' . ltrim($doi, '/') : '',
                'galleys' => $galleys,
                'viewUrl' => $viewUrl,
                'badgeText' => $badgeText,
                'proofNotice' => $proofNotice,
            ];
        } catch (\Throwable $e) {
            error_log('[InPressPlugin] formatFromDatabase error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get list of unpublished / forthcoming issues for journal settings
     */
    public function getUnpublishedIssues(int $contextId): array
    {
        $issues = [];
        try {
            if (class_exists(Repo::class) && method_exists(Repo::class, 'issue')) {
                try {
                    $collector = Repo::issue()->getCollector()
                        ->filterByContextIds([$contextId])
                        ->filterByPublished(false);
                    $rawIssues = $collector->getMany();
                    foreach ($rawIssues as $issue) {
                        $issues[] = [
                            'id' => (int) $issue->getId(),
                            'identification' => $this->formatIssueIdentification($issue),
                            'year' => $issue->getYear(),
                            'volume' => $issue->getVolume(),
                            'number' => $issue->getNumber(),
                        ];
                    }
                } catch (\Throwable $e) {
                    // Fall back to direct DB extraction below
                }
            }

            if (empty($issues) && Schema::hasTable('issues')) {
                $rows = DB::table('issues')
                    ->where('journal_id', $contextId)
                    ->where('published', 0)
                    ->orderByDesc('year')
                    ->orderByDesc('volume')
                    ->orderByDesc('number')
                    ->get();

                foreach ($rows as $r) {
                    $issues[] = [
                        'id' => (int) $r->issue_id,
                        'identification' => $this->formatIssueIdentification($r),
                        'year' => $r->year,
                        'volume' => $r->volume,
                        'number' => $r->number,
                    ];
                }
            }
        } catch (\Throwable $e) {
            error_log('[InPressPlugin] getUnpublishedIssues error: ' . $e->getMessage());
        }

        return $issues;
    }

    /**
     * Extract unique sections from the in-press articles
     */
    public function extractUniqueSections(array $articles): array
    {
        $sections = [];
        foreach ($articles as $article) {
            $secTitle = trim($article['sectionTitle'] ?? '');
            if (!empty($secTitle) && !isset($sections[$secTitle])) {
                $sections[$secTitle] = [
                    'id' => $article['sectionId'] ?? 0,
                    'title' => $secTitle,
                ];
            }
        }
        ksort($sections);
        return array_values($sections);
    }

    /**
     * Format academic citation for In Press articles
     */
    public function formatCitation(array $article, $journal, bool $showDoi = true): string
    {
        $authors = !empty($article['authorString']) ? $article['authorString'] : 'Unknown';
        $year = !empty($article['dateAccepted']) ? date('Y', strtotime($article['dateAccepted'])) : date('Y');
        $title = rtrim($article['fullTitle'], '.');
        $journalName = $journal ? ($journal->getLocalizedName() ?: $journal->getData('name')) : 'Journal';
        $doiPart = ($showDoi && !empty($article['doiUrl'])) ? ' ' . $article['doiUrl'] : '';

        return sprintf('%s (%s). %s. %s, In Press.%s', $authors, $year, $title, $journalName, $doiPart);
    }

    /**
     * Format human-readable identification of an issue
     */
    protected function formatIssueIdentification($issue): string
    {
        if (method_exists($issue, 'getIssueIdentification')) {
            $ident = $issue->getIssueIdentification();
            if (!empty($ident)) {
                return $ident;
            }
        }

        $vol = method_exists($issue, 'getVolume') ? $issue->getVolume() : ($issue->volume ?? '');
        $num = method_exists($issue, 'getNumber') ? $issue->getNumber() : ($issue->number ?? '');
        $yr  = method_exists($issue, 'getYear') ? $issue->getYear() : ($issue->year ?? '');

        $parts = [];
        if ($vol) $parts[] = 'Vol. ' . $vol;
        if ($num) $parts[] = 'No. ' . $num;
        if ($yr)  $parts[] = '(' . $yr . ')';

        return !empty($parts) ? implode(' ', $parts) : ('Issue #' . (method_exists($issue, 'getId') ? $issue->getId() : $issue->issue_id));
    }

    /**
     * Resolve the physical disk path or remote URL for a galley
     */
    public function resolveGalleyFile(int $submissionId, int $galleyId): ?array
    {
        try {
            if (class_exists(Repo::class) && method_exists(Repo::class, 'galley')) {
                $galley = Repo::galley()->get($galleyId);
                if ($galley && (int) $galley->getData('submissionId') === $submissionId) {
                    if ($galley->getData('urlRemote')) {
                        return ['type' => 'remote', 'url' => $galley->getData('urlRemote')];
                    }

                    $fileId = (int) $galley->getData('submissionFileId');
                    if ($fileId && class_exists(Repo::class) && method_exists(Repo::class, 'submissionFile')) {
                        $subFile = Repo::submissionFile()->get($fileId);
                        if ($subFile) {
                            $path = $subFile->getData('path');
                            $mime = $subFile->getData('mimetype') ?: 'application/pdf';
                            $fileName = $subFile->getLocalizedData('name') ?: ('in_press_' . $submissionId . '.pdf');

                            $filesDir = Config::getVar('files', 'files_dir');
                            $fullPath = rtrim($filesDir, '/') . '/' . ltrim($path, '/');
                            if (file_exists($fullPath)) {
                                return [
                                    'type' => 'local',
                                    'path' => $fullPath,
                                    'mime' => $mime,
                                    'name' => $fileName,
                                ];
                            }
                        }
                    }
                }
            }

            // DB fallback
            if (Schema::hasTable('publication_galleys')) {
                $gRow = DB::table('publication_galleys')->where('galley_id', $galleyId)->first();
                if ($gRow) {
                    if (!empty($gRow->url_remote)) {
                        return ['type' => 'remote', 'url' => $gRow->url_remote];
                    }
                    if (!empty($gRow->submission_file_id) && Schema::hasTable('submission_files')) {
                        $fRow = DB::table('submission_files')->where('submission_file_id', $gRow->submission_file_id)->first();
                        if ($fRow && !empty($fRow->path)) {
                            $filesDir = Config::getVar('files', 'files_dir');
                            $fullPath = rtrim($filesDir, '/') . '/' . ltrim($fRow->path, '/');
                            if (file_exists($fullPath)) {
                                return [
                                    'type' => 'local',
                                    'path' => $fullPath,
                                    'mime' => $fRow->mimetype ?: 'application/pdf',
                                    'name' => 'in_press_' . $submissionId . '.pdf',
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[InPressPlugin] resolveGalleyFile error: ' . $e->getMessage());
        }

        return null;
    }
}
