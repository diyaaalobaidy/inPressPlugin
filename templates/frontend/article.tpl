{**
 * plugins/generic/inPressPlugin/templates/frontend/article.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Dedicated landing page template for an In Press article.
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page page_inpress_article pkp_inpress_article_page">

	{* Breadcrumbs *}
	<nav class="cmp_breadcrumbs" aria-label="{translate key="navigation.breadcrumbLabel"}">
		<ol class="breadcrumb">
			<li>
				<a href="{url page="index"}">{translate key="common.homepageNavigationLabel"}</a>
				<span class="separator">/</span>
			</li>
			<li>
				<a href="{$inPressListUrl|escape}">{translate key="plugins.generic.inPressPlugin.pageTitle"}</a>
				<span class="separator">/</span>
			</li>
			<li class="current" aria-current="page">
				<span aria-current="page">{$article.title|escape|truncate:60:"..."}</span>
			</li>
		</ol>
	</nav>

	<article class="pkp_inpress_article_detail">

		{* Forthcoming Notice Banner *}
		<div class="pkp_inpress_article_banner">
			<div class="pkp_inpress_badge_strip">
				<span class="pkp_inpress_badge pkp_inpress_badge_status">{$badgeText|escape}</span>
				{if $article.sectionTitle}
					<span class="pkp_inpress_badge pkp_inpress_badge_section">{$article.sectionTitle|escape}</span>
				{/if}
				{if $proofNotice}
					<span class="pkp_inpress_badge pkp_inpress_badge_proof">{$proofNotice|escape}</span>
				{/if}
			</div>
			<p class="pkp_inpress_banner_note">
				{translate key="plugins.generic.inPressPlugin.introNotice"}
			</p>
		</div>

		{* Article Header *}
		<header class="pkp_inpress_article_header">
			<h1 class="pkp_inpress_article_title">
				{$article.title|escape}
			</h1>
			{if $article.subtitle}
				<h2 class="pkp_inpress_article_subtitle">
					{$article.subtitle|escape}
				</h2>
			{/if}

			{* Authors & Affiliations *}
			{if !empty($article.authors)}
				<div class="pkp_inpress_article_authors">
					{foreach from=$article.authors item=author}
						<div class="pkp_inpress_author_card">
							<strong class="pkp_inpress_author_name">{$author.fullName|escape}</strong>
							{if $author.affiliation}
								<span class="pkp_inpress_author_affil">{$author.affiliation|escape}</span>
							{/if}
							{if $author.orcid}
								<a href="{$author.orcid|escape}" class="pkp_inpress_orcid_badge" target="_blank" rel="noopener">
									<span>ORCID:</span> {$author.orcid|escape}
								</a>
							{/if}
						</div>
					{/foreach}
				</div>
			{/if}
		</header>

		{* Main Content Grid (Abstract & Metadata Sidebar) *}
		<div class="pkp_inpress_article_grid">
			
			{* Left Column: Abstract & Galleys *}
			<div class="pkp_inpress_main_col">
				
				{if !empty($article.abstract)}
					<section class="pkp_inpress_section pkp_inpress_abstract_section">
						<h2 class="pkp_inpress_section_title">{translate key="article.abstract"}</h2>
						<div class="pkp_inpress_full_abstract">
							{$article.abstract|strip_unsafe_html}
						</div>
					</section>
				{/if}

				{* Galleys / PDF proofs *}
				{if $showGalleys && !empty($article.galleys)}
					<section class="pkp_inpress_section pkp_inpress_galleys_section">
						<h2 class="pkp_inpress_section_title">{translate key="submission.layout.galleys"}</h2>
						<div class="pkp_inpress_galley_cards">
							{foreach from=$article.galleys item=galley}
								<div class="pkp_inpress_galley_card">
									<div class="pkp_inpress_galley_meta">
										<span class="pkp_inpress_galley_label">{$galley.label|escape}</span>
										{if $proofNotice}
											<span class="pkp_inpress_galley_subnote">({$proofNotice|escape})</span>
										{/if}
									</div>
									<a href="{$galley.downloadUrl|escape}" class="pkp_inpress_btn pkp_inpress_btn_primary" target="_blank" rel="noopener">
										<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
											<path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
										</svg>
										<span>{translate key="plugins.generic.inPressPlugin.downloadProof"}</span>
									</a>
								</div>
							{/foreach}
						</div>
					</section>
				{/if}

				{* How to Cite Box *}
				{if $citation}
					<section class="pkp_inpress_section pkp_inpress_citation_section">
						<div class="pkp_inpress_citation_header">
							<h2 class="pkp_inpress_section_title">{translate key="plugins.generic.inPressPlugin.howToCite"}</h2>
							<button type="button" class="pkp_inpress_btn_copy" id="pkpInPressCopyCitation" data-text="{$citation|escape}">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
									<path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
								</svg>
								<span>{translate key="plugins.generic.inPressPlugin.copyCitation"}</span>
							</button>
						</div>
						<div class="pkp_inpress_citation_box" id="pkpInPressCitationText">
							{$citation|escape}
						</div>
						<div id="pkpInPressCitationNotice" class="pkp_inpress_copy_success" style="display: none;">
							{translate key="plugins.generic.inPressPlugin.copied"}
						</div>
					</section>
				{/if}

			</div>

			{* Right Column: Metadata Details *}
			<aside class="pkp_inpress_sidebar_col">
				<div class="pkp_inpress_meta_panel">
					<h3 class="pkp_inpress_meta_heading">{translate key="article.metadata"}</h3>

					<dl class="pkp_inpress_meta_list">
						{if $article.sectionTitle}
							<div class="pkp_inpress_meta_row">
								<dt>{translate key="section.section"}:</dt>
								<dd>{$article.sectionTitle|escape}</dd>
							</div>
						{/if}

						{if $showExpectedIssue && !empty($article.futureIssue)}
							<div class="pkp_inpress_meta_row">
								<dt>{translate key="plugins.generic.inPressPlugin.futureIssue"}:</dt>
								<dd><strong>{$article.futureIssue.identification|escape}</strong></dd>
							</div>
						{/if}

						{if $article.dateAccepted}
							<div class="pkp_inpress_meta_row">
								<dt>{translate key="plugins.generic.inPressPlugin.dateAccepted"}:</dt>
								<dd><time datetime="{$article.dateAccepted|escape}">{$article.dateAccepted|escape}</time></dd>
							</div>
						{/if}

						{if $article.dateSubmitted}
							<div class="pkp_inpress_meta_row">
								<dt>{translate key="plugins.generic.inPressPlugin.dateSubmitted"}:</dt>
								<dd><time datetime="{$article.dateSubmitted|escape}">{$article.dateSubmitted|escape}</time></dd>
							</div>
						{/if}

						{if !empty($article.doiUrl)}
							<div class="pkp_inpress_meta_row">
								<dt>{translate key="plugins.generic.inPressPlugin.doi"}:</dt>
								<dd><a href="{$article.doiUrl|escape}" target="_blank" rel="noopener">{$article.doiUrl|escape}</a></dd>
							</div>
						{/if}
					</dl>

					<div class="pkp_inpress_back_link">
						<a href="{$inPressListUrl|escape}">&larr; {translate key="plugins.generic.inPressPlugin.viewAll"}</a>
					</div>
				</div>
			</aside>

		</div>

	</article>

</div>

{include file="frontend/components/footer.tpl"}
