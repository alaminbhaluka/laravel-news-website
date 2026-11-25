# Tribune Bangla — WordPress রূপান্তর পরিকল্পনা

## লক্ষ্য
বর্তমান Laravel ওয়েবসাইট, ডোমেইন ও ডেটাবেস অক্ষত রেখে আলাদা WordPress staging পরিবেশে নতুন সাইট প্রস্তুত করা। সব পরীক্ষা ও মালিকের অনুমোদনের আগে DNS/production পরিবর্তন করা যাবে না।

## ইতিমধ্যে প্রস্তুত
- বাংলা-প্রথম responsive newspaper theme: header, footer, homepage, category sections, single news, pages, search/archive, 404, comments
- Custom logo, menu, widget, breaking-news toggle, footer tagline ও social profile links
- Core plugin: gallery, advertisements, subscribers, contact messages, mobile/password account, reporter mobile profile, reporter/sub-editor roles, per-post SEO controls
- Shortcodes: [tribune_mobile_account], [tribune_contact_form], [tribune_newsletter], [tribune_ads position="homepage"], [tribune_gallery]
- GitHub Actions-এ PHP syntax validation workflow যোগ করা হয়েছে

## Laravel থেকে WordPress-এ ফিচার ম্যাপিং

| Laravel-এর তথ্য/ফিচার | WordPress গন্তব্য | অবস্থা |
|---|---|---|
| News headline/details/quote/slug | Posts + custom fields | importer প্রয়োজন |
| Categories, tags | Native Categories/Tags | importer প্রয়োজন |
| Reporter/author/admin | Users + editorial roles | role কাঠামো প্রস্তুত; account mapping প্রয়োজন |
| News images/videos | Media Library + gallery | importer প্রয়োজন |
| Pages/about/contact | Pages + contact form | কাঠামো প্রস্তুত |
| Comments/replies/moderation | Native Comments | কাঠামো প্রস্তুত |
| Advertisements | tb_advertisement | কাঠামো প্রস্তুত |
| Photo/video galleries | tb_gallery | কাঠামো প্রস্তুত |
| Subscribers | tb_subscriber | কাঠামো প্রস্তুত |
| Contact messages | tb_contact_message | কাঠামো প্রস্তুত |
| Mobile login | WP user meta + password | SMS OTP/verification বাকি |
| Google/Facebook login | OAuth integration | credentials ও callback setup বাকি |
| SEO/mail/social settings | WP settings/customizer + plugin | আংশিক; SMTP ও provider setup বাকি |
| Branch/manpower/geography | taxonomy/custom tables/plugin | data model review প্রয়োজন |

## আবশ্যিক নিরাপত্তা ও স্থানান্তর ধাপ
1. Laravel database এবং uploads-এর আলাদা পূর্ণ ব্যাকআপ।
2. Source schema, relationship, translation columns, IDs, timestamps, deleted/trash status এবং media paths read-only export করে যাচাই।
3. WordPress staging-এ importer dry-run; কোনো record insert/update না করে সম্ভাব্য duplicate ও mapping report তৈরি।
4. দ্বিতীয় ধাপে staging-এ import; original Laravel IDs সংরক্ষণে _tb_legacy_id এবং source type metadata রাখা।
5. Posts, categories, tags, users, comments, pages, media, galleries, ads, subscribers, settings এবং geography record count মিলিয়ে দেখা।
6. বাংলা Unicode, translated headlines, slug/permalink, image dimensions, video embeds, author attribution এবং comments পরীক্ষা।
7. পুরোনো URL থেকে নতুন URL-এ 301 redirect mapping তৈরি; broken links ও 404 audit।
8. Mobile, desktop, accessibility, SEO, forms, login, role permissions, backup/restore এবং performance পরীক্ষা।
9. মালিকের লিখিত অনুমোদনের পরেই domain/DNS cutover; Laravel hosting ও database rollback-এর জন্য সংরক্ষণ।

## এখনো চালু নয়
এই branch-এর theme/plugin WordPress server-এ ইনস্টল বা production-এ deploy করা হয়নি। SMS OTP gateway, Google/Facebook OAuth, SMTP, Laravel data importer, redirects এবং real-site acceptance tests এখনো সম্পন্ন হয়নি। PHP lint workflow যুক্ত হয়েছে; CI pass না দেখা পর্যন্ত syntax validation-কে সফল বলা যাবে না।
