{**
 * plugins/generic/inPressPlugin/templates/frontend/inpress.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Main public listing template for In Press articles.
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page page_inpress pkp_inpress_page">

	{* Breadcrumbs *}
	<nav class="cmp_breadcrumbs" aria-label="{translate key="navigation.breadcrumbLabel"}">
		<ol class="breadcrumb">
			<li>
				<a href="{url page="index"}">{translate key="common.homepageNavigationLabel"}</a>
				<span class="separator">/</span>
			</li>
			<li class="current" aria-current="page">
				<span aria-current="page">{$pageTitle|escape}</span>
			</li>
		</ol>
	</nav>

	{* Header Banner *}
	<header class="page_header pkp_inpress_header">
		<div class="pkp_inpress_title_row">
			<h1 class="page_title">{$pageTitle|escape}</h1>
			{if $feedUrl}
				<a href="{$feedUrl|escape}" class="pkp_inpress_feed_link" title="{translate key="plugins.generic.inPressPlugin.feedTitle"}" target="_blank" rel="noopener">
					<svg class="pkp_inpress_feed_icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
						<path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27V4.44m0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93v-2.83Z"/>
					</svg>
					<span>RSS</span>
				</a>
			{/if}
		</div>

		{if $introNotice}
			<div class="pkp_inpress_notice_banner">
				<div class="pkp_inpress_notice_icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
						<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
					</svg>
				</div>
				<div class="pkp_inpress_notice_text">
					{$introNotice|nl2br}
				</div>
			</div>
		{/if}
	</header>

	{* Articles Section *}
	{if !empty($inPressArticles)}
		{* Filter & Search Toolbar *}
		<div class="pkp_inpress_toolbar">
			<div class="pkp_inpress_search_box">
				<input type="text" id="pkpInPressSearch" class="pkp_inpress_search_input" placeholder="{translate key="plugins.generic.inPressPlugin.searchPlaceholder"}" aria-label="{translate key="plugins.generic.inPressPlugin.searchPlaceholder"}">
			</div>

			{if !empty($sections) && $sections|@count > 1}
				<div class="pkp_inpress_filter_box">
					<select id="pkpInPressSectionFilter" class="pkp_inpress_filter_select" aria-label="{translate key="plugins.generic.inPressPlugin.filterBySection"}">
						<option value="">{translate key="plugins.generic.inPressPlugin.allSections"}</option>
						{foreach from=$sections item=sec}
							<option value="{$sec.title|escape}">{$sec.title|escape}</option>
						{/foreach}
					</select>
				</div>
			{/if}

			<div class="pkp_inpress_count_badge">
				<span id="pkpInPressVisibleCount">{$inPressArticles|@count}</span> / {$inPressArticles|@count}
			</div>
		</div>

		{* Articles Cards *}
		<div class="pkp_inpress_articles_list" id="pkpInPressArticlesContainer">
			{foreach from=$inPressArticles item=art}
				<article class="pkp_inpress_card" data-section="{$art.sectionTitle|escape}" data-title="{$art.fullTitle|escape|lower}" data-authors="{$art.authorString|escape|lower}">
					<div class="pkp_inpress_card_header">
						<div class="pkp_inpress_badges_group">
							<span class="pkp_inpress_badge pkp_inpress_badge_status">{$badgeText|escape}</span>
							{if $art.sectionTitle}
								<span class="pkp_inpress_badge pkp_inpress_badge_section">{$art.sectionTitle|escape}</span>
							{/if}
						</div>

						{if $showAcceptanceDate && $art.dateAccepted}
							<div class="pkp_inpress_date_badge">
								<span class="pkp_inpress_meta_label">{translate key="plugins.generic.inPressPlugin.dateAccepted"}:</span>
								<time datetime="{$art.dateAccepted|escape}">{$art.dateAccepted|escape}</time>
							</div>
						{/if}
					</div>

					{* Article Title *}
					<h2 class="pkp_inpress_card_title">
						<a href="{$art.viewUrl|escape}">{$art.fullTitle|escape}</a>
					</h2>

					{* Authors *}
					{if !empty($art.authors)}
						<div class="pkp_inpress_authors">
							{foreach from=$art.authors item=author name=authorsLoop}
								<span class="pkp_inpress_author">
									{$author.fullName|escape}{if $author.affiliation}<span class="pkp_inpress_affiliation">({$author.affiliation|escape})</span>{/if}
									{if $author.orcid}
										<a href="{$author.orcid|escape}" class="pkp_inpress_orcid_link" target="_blank" rel="noopener" title="ORCID: {$author.orcid|escape}">
											<img src="{$baseUrl}/plugins/generic/inPressPlugin/images/orcid.svg" alt="ORCID" width="14" height="14" onerror="this.remove()">
										</a>
									{/if}
								</span>{if !$smarty.foreach.authorsLoop.last}, {/if}
							{/foreach}
						</div>
					{/if}

					{* Scheduled Issue Information *}
					{if $showExpectedIssue && !empty($art.futureIssue)}
						<div class="pkp_inpress_future_issue">
							<span class="pkp_inpress_issue_icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
									<path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
								</svg>
							</span>
							<span class="pkp_inpress_issue_text">
								<strong>{translate key="plugins.generic.inPressPlugin.toAppearIn"}:</strong> {$art.futureIssue.identification|escape}
							</span>
						</div>
					{/if}

					{* Abstract *}
					{if $showAbstract && !empty($art.abstract)}
						<div class="pkp_inpress_abstract_wrapper">
							<div class="pkp_inpress_abstract pkp_inpress_collapsed">
								{$art.abstract|strip_unsafe_html}
							</div>
							<button type="button" class="pkp_inpress_abstract_toggle" data-more="{translate key="plugins.generic.inPressPlugin.readMore"}" data-less="{translate key="plugins.generic.inPressPlugin.readLess"}">
								{translate key="plugins.generic.inPressPlugin.readMore"} &darr;
							</button>
						</div>
					{/if}

					{* DOI & Action Links *}
					<div class="pkp_inpress_card_footer">
						<div class="pkp_inpress_doi_col">
							{if $showDoi && !empty($art.doiUrl)}
								<span class="pkp_inpress_doi">
									<strong>{translate key="plugins.generic.inPressPlugin.doi"}:</strong>
									<a href="{$art.doiUrl|escape}" target="_blank" rel="noopener">{$art.doiUrl|escape}</a>
								</span>
							{/if}
						</div>

						<div class="pkp_inpress_actions_col">
							<a href="{$art.viewUrl|escape}" class="pkp_inpress_btn pkp_inpress_btn_secondary">
								{translate key="plugins.generic.inPressPlugin.viewArticle"}
							</a>

							{if $showGalleys && !empty($art.galleys)}
								{foreach from=$art.galleys item=galley}
									<a href="{$galley.downloadUrl|escape}" class="pkp_inpress_btn pkp_inpress_btn_primary" target="_blank" rel="noopener">
										<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
											<path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
										</svg>
										<span>{$galley.label|escape} {if $proofNotice}<small class="pkp_inpress_proof_note">({$proofNotice|escape})</small>{/if}</span>
									</a>
								{/foreach}
							{/if}
						</div>
					</div>
				</article>
			{/foreach}
		</div>

		{* Empty results during search *}
		<div id="pkpInPressNoFilterResults" class="pkp_inpress_empty_message" style="display: none;">
			<p>{translate key="common.noMatches"}</p>
		</div>

	{else}
		<div class="pkp_inpress_empty_state">
			<div class="pkp_inpress_empty_icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
					<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
				</svg>
			</div>
			<p class="pkp_inpress_empty_text">{translate key="plugins.generic.inPressPlugin.noArticles"}</p>
			<a href="{url page="issue" op="archive"}" class="pkp_inpress_btn pkp_inpress_btn_secondary">
				{translate key="navigation.archives"}
			</a>
		</div>
	{/if}

</div>

{include file="frontend/components/footer.tpl"}
