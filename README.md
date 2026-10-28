# In Press Articles Plugin for OJS 3.5

An advanced, publication-grade **In Press** (Ahead of Print / Online First / Forthcoming) plugin for **Open Journal Systems (OJS) 3.5+** (and 3.4+).

This plugin allows journals to showcase peer-reviewed and accepted articles before their designated issue is published, accelerating scientific dissemination while maintaining full citation integrity.

---

## Key Features

- **Automated In-Press Discovery**:
  - Automatically identifies articles scheduled into future / unpublished issues (`issues.published = 0`).
  - Supports alternative detection modes:
    1. **Scheduled in Unpublished Issues** (Default & Recommended).
    2. **All Accepted Submissions in Production Stage** (`stage_id = 5`).
    3. **Designated Forthcoming Issue** (Specific unpublished placeholder issue).
- **Public In-Press Listing Page (`/inpress`)**:
  - Seamlessly integrates with the journal's active theme.
  - Live client-side search by title, author, or keywords.
  - Section filtering dropdown.
  - Clean article cards displaying section badge, "In Press" badge, authors, affiliations, ORCID, expected issue, acceptance date, and DOI.
  - Expandable abstract previews with smooth "Read more / Show less" toggle.
  - Galley proof download buttons with configurable "Uncorrected Proof" notice.
- **Dedicated Article Landing Page (`/inpress/view/{id}`)**:
  - Detailed view of the forthcoming paper before the issue is released.
  - Injects **HighWire Press**, **Google Scholar**, and **Dublin Core** metadata tags (`citation_title`, `citation_author`, `citation_publication_date`, etc.) to enable early indexing.
  - Formats standard APA forthcoming citation:  
    `Author(s) (Year). Title. Journal Name, In Press. https://doi.org/...`
  - One-click "Copy Citation" button with instant feedback.
- **Galley Proof Delivery (`/inpress/download/{id}/{galleyId}`)**:
  - Safely serves PDF proofs or redirects to remote galleys for in-press articles without exposing private editorial files.
- **Homepage Showcase Block**:
  - Optional sleek section on the journal homepage highlighting the latest 3–5 in-press articles with a direct "View All" link.
- **RSS 2.0 Feed (`/inpress/feed`)**:
  - Automatically generates an RSS feed of forthcoming articles for subscribers and aggregators.
- **Navigation Menu Integration**:
  - Registers a native OJS Navigation Menu Item type (`NMI_TYPE_IN_PRESS`) so editors can place "In Press" anywhere in primary or user navigation menus.
- **Full Internationalization (i18n)**:
  - English (`en`) and Arabic (`ar`) out of the box.
  - Responsive RTL (Right-to-Left) CSS styling for Arabic.
- **Dual Architecture (Repository + Eloquent DB)**:
  - Supports modern OJS 3.5 `Repo::submission()`, `Repo::publication()`, `Repo::issue()`, and `Repo::galley()` domain models with direct Eloquent Query Builder fallbacks.

---

## Directory Structure

```
inPressPlugin/
├── InPressPlugin.php              # Main plugin entry class
├── index.php                      # OJS loader wrapper
├── version.xml                    # Plugin metadata for OJS 3.5
├── README.md                      # Documentation
├── LICENSE                        # GNU GPL v3
├── classes/
│   ├── InPressService.php         # Business logic, query engine & citation generator
│   └── handlers/
│       ├── InPressFrontendHandler.php # Public controller (/inpress, /view, /download, /feed)
│       └── InPressSettingsHandler.php # Backend settings modal controller
├── templates/
│   ├── frontend/
│   │   ├── inpress.tpl            # Listing page template
│   │   ├── article.tpl            # Article detail template
│   │   └── homepageBlock.tpl      # Homepage showcase section
│   └── settings/
│       └── settings.tpl           # Settings modal template
├── css/
│   └── inPress.css                # Responsive styles & RTL support
├── js/
│   └── inPress.js                 # Search, filtering, abstract toggle, clipboard copy
└── locale/
    ├── en/
    │   └── locale.po              # English translations
    └── ar/
        └── locale.po              # Arabic translations
```

---

## Installation

1. Copy or clone the `inPressPlugin` folder into your OJS installation under:
   ```
   plugins/generic/inPressPlugin
   ```
2. Log in as Journal Manager or Site Administrator.
3. Navigate to:
   **Settings > Website > Plugins > Installed Plugins**
4. Under **Generic Plugins**, locate **Articles In Press** and check the box to **Enable** it.
5. Click the expansion triangle next to the plugin name and select **Settings** to customize the plugin.

---

## Configuration Options

In the Plugin Settings modal, you can configure:

| Setting | Description | Default |
|---|---|---|
| **Enable In Press** | Toggle the public In-Press page and features. | Enabled |
| **Page Title** | Custom title for the listing page (e.g. *Articles in Press* or *Online First*). | Localized default |
| **Article Badge Label** | Badge shown on article cards (e.g. *In Press*, *Ahead of Print*). | `In Press` |
| **Introductory Notice** | Disclaimer banner explaining that papers are peer-reviewed accepted manuscripts. | Localized default |
| **Detection Mode** | 1. Scheduled in unpublished issues (Recommended)<br>2. All accepted in production stage<br>3. Designated forthcoming issue | `scheduled_unpublished` |
| **Designated Issue** | Dropdown of unpublished issues (used when mode #3 is selected). | None |
| **Show Galley Proofs** | Enable/disable PDF download buttons for in-press articles. | Enabled |
| **Proof Notice** | Label displayed alongside galley buttons (e.g. *Uncorrected Proof*). | `Uncorrected Proof` |
| **Show Abstracts** | Show expandable abstracts in the article list. | Enabled |
| **Show Expected Issue** | Show "To appear in: Vol X No Y (Year)". | Enabled |
| **Show Acceptance Date**| Display acceptance date. | Enabled |
| **Show DOI** | Display assigned DOI link. | Enabled |
| **Homepage Showcase** | Display a block of recent in-press articles on the journal homepage. | Enabled |
| **Homepage Item Count**| Number of articles to showcase on the homepage (1–20). | `3` |
| **Enable RSS Feed** | Provide `/inpress/feed` RSS 2.0 endpoint. | Enabled |

---

## Navigation Menu Setup

To add an "In Press" link to your journal's navigation bar:
1. Go to **Settings > Website > Setup > Navigation**.
2. Edit your **Primary Navigation Menu**.
3. Under **Assigned Items**, click **Add Item**.
4. Choose the **In Press** navigation item type.
5. Save the menu.

---

## License

This plugin is distributed under the GNU General Public License v3.0 (GPL-3.0).
