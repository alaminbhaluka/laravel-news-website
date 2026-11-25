TRIBUNE BANGLA WORDPRESS THEME
Version: 1.2.0
Requires: WordPress 6.2+, PHP 8.0+

INSTALLATION
1. Upload tribune-bangla-theme.zip from Appearance > Themes > Add New > Upload Theme.
2. Activate Tribune Bangla.
3. Set the logo in Appearance > Customize > Site Identity.
4. Assign primary and footer menus in Appearance > Menus.
5. Add widgets under Appearance > Widgets.
6. Create pages and add the relevant shortcode listed below.

THEME FEATURES
- Bengali-first responsive homepage, lead stories, category sections, latest news and breaking-news bar.
- WordPress posts, categories, tags, pages, comments, media library, search, archives and pagination.
- Breaking-news checkbox in the post editor.
- Custom logo, navigation menus, footer widgets, social links and Customizer settings.
- Ad inventory in the dashboard (বিজ্ঞাপন); add image URL, destination URL, placement and active flag.
- Ad placement shortcode: [tribune_ads position="sidebar"] (also header or footer).
- Ad click tracking, render/impression counts, click-through rate (CTR), campaign report and manually entered revenue under বিজ্ঞাপন → বিজ্ঞাপন রিপোর্ট.
- Newsletter subscriber storage in dashboard (নিউজলেটার গ্রাহক); shortcode: [tribune_newsletter].
- Automatic plain-text email when a new article is published, with article link and one-click unsubscribe.
- Reader contact inbox in dashboard (পাঠকের বার্তা); shortcode: [tribune_contact_form].
- Reporter role with pending-only editorial submission; create a page and add [tribune_reporter_panel].
- Mobile number registration/login shortcode: [tribune_mobile_account]. Enable registration in Settings > General first.
- Per-post/page SEO title and description fields; Open Graph metadata and NewsArticle structured data (suppressed when Yoast, Rank Math or AIOSEO is active).
- Native WordPress XML sitemap at /wp-sitemap.xml and Google News sitemap at /news-sitemap.xml (articles from the last 48 hours). Sitemap URLs are added to robots.txt.
- Photo gallery content type (ফটো গ্যালারি); shortcode: [tribune_gallery].

EDITORIAL WORKFLOW
- Administrator creates a user and assigns the "প্রতিবেদক" role.
- Reporter signs in and submits a story from the page containing [tribune_reporter_panel].
- Each story is saved as Pending; an editor reviews and publishes it from WordPress admin.
- Reporter cannot publish directly or edit another reporter's story by default.

SECURITY AND OPERATION NOTES
- Forms use WordPress nonces and sanitize submitted values. Use HTTPS and keep WordPress/core/plugins updated.
- Mobile registration requires Settings > General > Anyone can register. Use strong passwords.
- Mobile number sign-in uses the mobile number as the WordPress username. This is password login, not SMS/OTP verification.
- Ad click and render counts are measured by theme redirects and shortcode rendering; these are not viewability-certified ad impressions. Revenue is entered manually; no Google Ad Manager/AdSense API, payment reconciliation or automatic earnings import is included.
- Newsletter sends with WordPress wp_mail when a new post is published. Configure authenticated SMTP on the hosting account (or a trusted WordPress SMTP plugin) and test delivery before launch; PHP mail availability and inbox placement depend on hosting/provider. Large mailing lists should use a dedicated mailing service/queue.
- The theme provides native XML and Google News sitemaps. Submit the sitemap in Google Search Console; a sitemap does not guarantee Google News inclusion or indexing. Google News eligibility and publisher policies still apply. If Yoast, Rank Math or AIOSEO is active, its sitemap/SEO output takes precedence.
- Test all features on a staging WordPress install before production. This theme has not yet been tested against the live hosting environment.
- This theme does not import old Laravel content or alter/delete the existing Laravel site or database.
