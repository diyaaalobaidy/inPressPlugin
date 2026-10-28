{**
 * plugins/generic/inPressPlugin/templates/settings/settings.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * In Press Plugin settings modal form.
 *}
<div class="pkp_inpress_settings_modal">
	<form class="pkp_form" id="inPressSettingsForm" method="post" action="{url op="manage" category="generic" plugin=$pluginName verb="save"}">
		{csrf}

		<div class="pkp_inpress_settings_header" style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
			<h3 style="margin-top: 0; font-size: 1.25rem; color: #1e293b;">{translate key="plugins.generic.inPressPlugin.settings.title"}</h3>
			<p class="description" style="color: #64748b; font-size: 0.9rem; margin: 0;">{translate key="plugins.generic.inPressPlugin.settings.description"}</p>
		</div>

		{* Section 1: Activation & Identity *}
		<div class="pkp_form_section" style="margin-bottom: 22px;">
			<div class="pkp_form_row" style="margin-bottom: 16px;">
				<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
					<input type="checkbox" name="inPressEnabled" value="1" {if $inPressEnabled}checked="checked"{/if} style="width: 18px; height: 18px;" />
					<span>{translate key="plugins.generic.inPressPlugin.settings.enabled"}</span>
				</label>
			</div>

			<div style="display: flex; gap: 16px; margin-bottom: 16px;">
				<div style="flex: 1;">
					<label for="inPressPageTitle" style="display: block; font-weight: 600; margin-bottom: 6px;">
						{translate key="plugins.generic.inPressPlugin.settings.pageTitle"}
					</label>
					<input type="text" id="inPressPageTitle" name="pageTitle" value="{$pageTitle|escape}" class="pkp_input" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px;" />
				</div>

				<div style="flex: 1;">
					<label for="inPressBadgeText" style="display: block; font-weight: 600; margin-bottom: 6px;">
						{translate key="plugins.generic.inPressPlugin.settings.badgeText"}
					</label>
					<input type="text" id="inPressBadgeText" name="badgeText" value="{$badgeText|escape}" class="pkp_input" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px;" />
				</div>
			</div>

			<div class="pkp_form_row">
				<label for="inPressIntroNotice" style="display: block; font-weight: 600; margin-bottom: 6px;">
					{translate key="plugins.generic.inPressPlugin.settings.introNotice"}
				</label>
				<textarea id="inPressIntroNotice" name="introNotice" rows="3" class="pkp_input" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; resize: vertical;">{$introNotice|escape}</textarea>
			</div>
		</div>

		{* Section 2: Detection Mode *}
		<div class="pkp_form_section" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin-bottom: 22px;">
			<h4 style="margin-top: 0; margin-bottom: 12px; color: #334155;">{translate key="plugins.generic.inPressPlugin.settings.detectionMode"}</h4>

			<div style="display: flex; flex-direction: column; gap: 10px;">
				<label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
					<input type="radio" name="detectionMode" value="scheduled_unpublished" {if $detectionMode == 'scheduled_unpublished' || !$detectionMode}checked="checked"{/if} style="margin-top: 3px;" onchange="toggleDesignatedIssue(this.value)" />
					<div>
						<strong>{translate key="plugins.generic.inPressPlugin.settings.modeScheduled"}</strong>
						<div style="font-size: 0.82rem; color: #64748b;">Submissions with STATUS_SCHEDULED or assigned to an issue where published = 0.</div>
					</div>
				</label>

				<label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
					<input type="radio" name="detectionMode" value="all_accepted_production" {if $detectionMode == 'all_accepted_production'}checked="checked"{/if} style="margin-top: 3px;" onchange="toggleDesignatedIssue(this.value)" />
					<div>
						<strong>{translate key="plugins.generic.inPressPlugin.settings.modeProduction"}</strong>
						<div style="font-size: 0.82rem; color: #64748b;">Includes accepted submissions in Production stage (stage_id = 5) before or after issue scheduling.</div>
					</div>
				</label>

				<label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
					<input type="radio" name="detectionMode" value="specific_issue" {if $detectionMode == 'specific_issue'}checked="checked"{/if} style="margin-top: 3px;" onchange="toggleDesignatedIssue(this.value)" />
					<div>
						<strong>{translate key="plugins.generic.inPressPlugin.settings.modeSpecific"}</strong>
						<div style="font-size: 0.82rem; color: #64748b;">Show submissions assigned strictly to a specific forthcoming issue selected below.</div>
					</div>
				</label>
			</div>

			<div id="designatedIssueWrap" style="margin-top: 14px; padding-left: 26px; {if $detectionMode != 'specific_issue'}display: none;{/if}">
				<label for="designatedIssueId" style="display: block; font-weight: 600; margin-bottom: 6px;">
					{translate key="plugins.generic.inPressPlugin.settings.designatedIssue"}
				</label>
				<select id="designatedIssueId" name="designatedIssueId" class="pkp_input" style="width: 100%; max-width: 400px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px;">
					<option value="0">{translate key="plugins.generic.inPressPlugin.settings.designatedIssueSelect"}</option>
					{foreach from=$unpublishedIssues item=issue}
						<option value="{$issue.id}" {if $designatedIssueId == $issue.id}selected="selected"{/if}>
							{$issue.identification|escape}
						</option>
					{/foreach}
				</select>
			</div>
		</div>

		{* Section 3: Display & Proofs *}
		<div class="pkp_form_section" style="margin-bottom: 22px;">
			<h4 style="margin-top: 0; margin-bottom: 12px; color: #334155;">Display Options & Galleys</h4>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="showGalleys" value="1" {if $showGalleys}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.showGalleys"}</span>
				</label>

				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="showAbstract" value="1" {if $showAbstract}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.showAbstract"}</span>
				</label>

				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="showExpectedIssue" value="1" {if $showExpectedIssue}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.showExpectedIssue"}</span>
				</label>

				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="showAcceptanceDate" value="1" {if $showAcceptanceDate}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.showAcceptanceDate"}</span>
				</label>

				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="showDoi" value="1" {if $showDoi}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.showDoi"}</span>
				</label>

				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="enableFeed" value="1" {if $enableFeed}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.enableFeed"}</span>
				</label>
			</div>

			<div style="margin-bottom: 16px;">
				<label for="proofNotice" style="display: block; font-weight: 600; margin-bottom: 6px;">
					{translate key="plugins.generic.inPressPlugin.settings.proofNotice"}
				</label>
				<input type="text" id="proofNotice" name="proofNotice" value="{$proofNotice|escape}" class="pkp_input" style="width: 100%; max-width: 350px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px;" />
			</div>
		</div>

		{* Section 4: Homepage Block *}
		<div class="pkp_form_section" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin-bottom: 24px;">
			<h4 style="margin-top: 0; margin-bottom: 12px; color: #334155;">Homepage Showcase</h4>

			<div style="margin-bottom: 14px;">
				<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
					<input type="checkbox" name="showOnHomepage" value="1" {if $showOnHomepage}checked="checked"{/if} />
					<span>{translate key="plugins.generic.inPressPlugin.settings.showOnHomepage"}</span>
				</label>
			</div>

			<div style="display: flex; align-items: center; gap: 12px;">
				<label for="homepageItemCount" style="font-weight: 600;">
					{translate key="plugins.generic.inPressPlugin.settings.homepageItemCount"}:
				</label>
				<input type="number" id="homepageItemCount" name="homepageItemCount" min="1" max="20" value="{$homepageItemCount|default:3}" class="pkp_input" style="width: 80px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px;" />
			</div>
		</div>

		{* Actions *}
		<div class="pkp_inpress_settings_footer" style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
			<button type="button" class="pkp_button" onclick="pkp.classes.Handler.getHandler($('#inPressSettingsForm').closest('.pkp_modal_panel')).cancel()" style="padding: 8px 18px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; cursor: pointer;">
				{translate key="common.cancel"}
			</button>
			<button type="submit" class="pkp_button pkp_button_primary" style="padding: 8px 22px; background: #006699; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
				{translate key="common.save"}
			</button>
		</div>
	</form>

	<script type="text/javascript">
		function toggleDesignatedIssue(mode) {
			var wrap = document.getElementById('designatedIssueWrap');
			if (wrap) {
				wrap.style.display = (mode === 'specific_issue') ? 'block' : 'none';
			}
		}

		$(function() {
			$('#inPressSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', {
				baseUrl: {$baseUrl|json_encode}
			});
		});
	</script>
</div>
