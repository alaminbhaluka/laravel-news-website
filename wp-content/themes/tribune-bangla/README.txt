TRIBUNE BANGLA WORDPRESS THEME
Version: 1.1.0
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
- Newsletter subscriber storage in dashboard (নিউজলেটার গ্রাহক); shortcode: [tribune_newsletter].
- Reader contact inbox in dashboard (পাঠকের বার্তা); shortcode: [tribune_contact_form].
- Reporter role with pending-only editorial submission; create a page and add [tribune_reporter_panel].
- Mobile number registration/login shortcode: [tribune_mobile_account]. Enable registration in Settings > General first.
- Per-post/page SEO title and description fields; basic Open Graph metadata (suppressed when Yoast, Rank Math or AIOSEO is active).
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
- Ads are manually managed; no ad-network integration or automatic revenue reporting is included.
- Newsletter addresses are stored locally; no bulk email sending or third-party mailing integration is included.
- SEO metadata is basic; use a dedicated SEO plugin for XML sitemaps, redirects and advanced schema.
- Test all features on a staging WordPress install before production. This theme has not yet been tested against the live hosting environment.
- This theme does not import old Laravel content or alter/delete the existing Laravel site or database.
