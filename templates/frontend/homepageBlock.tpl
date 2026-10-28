{**
 * plugins/generic/inPressPlugin/templates/frontend/homepageBlock.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Homepage section showcasing the latest In Press articles.
 *}
<section class="pkp_inpress_homepage_section" aria-labelledby="inPressHomeHeading">
	<div class="pkp_inpress_home_header">
		<div class="pkp_inpress_home_title_wrap">
			<span class="pkp_inpress_badge pkp_inpress_badge_status">{$inPressBadgeText|escape}</span>
			<h2 id="inPressHomeHeading" class="pkp_inpress_home_heading">{$inPressTitle|escape}</h2>
		</div>
		<a href="{$inPressPageUrl|escape}" class="pkp_inpress_home_view_all">
			{translate key="plugins.generic.inPressPlugin.viewAll"} &rarr;
		</a>
	</div>

	<div class="pkp_inpress_home_grid">
		{foreach from=$inPressHomeArticles item=art}
			<div class="pkp_inpress_home_card">
				<div class="pkp_inpress_home_card_meta">
					{if $art.sectionTitle}
						<span class="pkp_inpress_badge pkp_inpress_badge_section">{$art.sectionTitle|escape}</span>
					{/if}
					{if $inPressShowAcceptanceDate && $art.dateAccepted}
						<span class="pkp_inpress_home_date">{$art.dateAccepted|escape}</span>
					{/if}
				</div>

				<h3 class="pkp_inpress_home_card_title">
					<a href="{$art.viewUrl|escape}">{$art.fullTitle|escape}</a>
				</h3>

				{if !empty($art.authorString)}
					<p class="pkp_inpress_home_authors">
						{$art.authorString|escape}
					</p>
				{/if}

				{if $inPressShowExpectedIssue && !empty($art.futureIssue)}
					<div class="pkp_inpress_home_future_issue">
						<small><strong>{translate key="plugins.generic.inPressPlugin.toAppearIn"}:</strong> {$art.futureIssue.identification|escape}</small>
					</div>
				{/if}

				<div class="pkp_inpress_home_card_actions">
					<a href="{$art.viewUrl|escape}" class="pkp_inpress_home_read_link">
						{translate key="plugins.generic.inPressPlugin.viewArticle"} &rarr;
					</a>

					{if $inPressShowGalleys && !empty($art.galleys)}
						{foreach from=$art.galleys item=galley}
							<a href="{$galley.downloadUrl|escape}" class="pkp_inpress_home_btn_pdf" target="_blank" rel="noopener" title="{$galley.label|escape}">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
									<path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
								</svg>
								<span>{$galley.label|escape}</span>
							</a>
							{break}
						{/foreach}
					{/if}
				</div>
			</div>
		{/foreach}
	</div>
</section>
