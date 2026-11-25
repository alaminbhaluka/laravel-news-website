=== Tribune Bangla Core ===
Contributors: tribune-bangla
Tags: news, editorial, galleries, advertisements
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Tribune Bangla-এর WordPress সাইটের অতিরিক্ত সম্পাদকীয় ফিচার।

== অন্তর্ভুক্ত ফিচার ==
* ফটো ও ভিডিও গ্যালারির জন্য আলাদা কনটেন্ট টাইপ
* বিজ্ঞাপন ব্যবস্থাপনা: অবস্থান, লিংক, শুরু ও শেষের তারিখ
* নিউজলেটার গ্রাহক সংরক্ষণ
* যোগাযোগ ফর্ম ও অ্যাডমিন বার্তা সংরক্ষণ
* WordPress ব্যবহারকারীর প্রোফাইলে মোবাইল নম্বর
* বাংলাদেশি মোবাইল নম্বর দিয়ে পাসওয়ার্ড-ভিত্তিক নিবন্ধন ও লগইন
* প্রতিবেদক ও সহ-সম্পাদক ভূমিকা
* সংবাদ ও পাতার SEO বিবরণ, ক্যানোনিক্যাল URL, noindex এবং Open Graph
* অ্যাডমিন সেটিংসে যোগাযোগের তথ্য

== শর্টকোড ==
* [tribune_mobile_account] — মোবাইল অ্যাকাউন্ট লগইন ও নিবন্ধন
* [tribune_contact_form] — যোগাযোগ ফর্ম
* [tribune_newsletter] — নিউজলেটার ফর্ম
* [tribune_ads position="homepage" limit="3"] — বিজ্ঞাপন
  অবস্থান: header, homepage, sidebar, article, footer
* [tribune_gallery limit="12"] — গ্যালারি তালিকা

== ইনস্টলেশন ==
1. WordPress Dashboard > Plugins > Add New > Upload Plugin দিয়ে ZIP আপলোড করুন অথবা সার্ভারে প্লাগইন ফোল্ডার কপি করুন।
2. Tribune Bangla Core সক্রিয় করুন।
3. Settings > Tribune Bangla থেকে যোগাযোগের তথ্য দিন।
4. প্রয়োজনীয় পেজে শর্টকোড বসান।
5. Users > Add New থেকে প্রতিবেদক/সহ-সম্পাদক ভূমিকা বরাদ্দ করুন।
6. Settings > General-এ New User Default Role যাচাই করুন। উন্মুক্ত নিবন্ধন চালু করতে Anyone can register সক্রিয় করুন।

== নিরাপত্তা ও সীমাবদ্ধতা ==
* মোবাইল লগইন পাসওয়ার্ড-ভিত্তিক; SMS OTP এখনো সংযুক্ত নয়।
* Google/Facebook OAuth-এর জন্য সংশ্লিষ্ট অ্যাপের Client ID/Secret ও OAuth callback কনফিগারেশন প্রয়োজন; এই সংস্করণে OAuth চালু নয়।
* বিজ্ঞাপনের বাহ্যিক HTML/স্ক্রিপ্ট কেবল বিশ্বস্ত অ্যাডমিনের মাধ্যমে যোগ করুন।
* SEO প্লাগইন (যেমন Yoast/Rank Math) ব্যবহার করলে ডুপ্লিকেট meta description/Open Graph এড়াতে এই প্লাগইনের SEO output বন্ধ/সমন্বয় করতে হবে।
* এই প্লাগইন Laravel ডেটাবেস নিজে থেকে পরিবর্তন বা মুছে ফেলে না।

== Laravel থেকে ডেটা স্থানান্তর ==
লাইভ ডেটা স্থানান্তরের আগে পূর্ণ ডেটাবেস ও মিডিয়া ব্যাকআপ বাধ্যতামূলক। প্রথম ধাপে read-only export নিয়ে posts, categories, tags, users, comments, pages, media, ads এবং galleries-এর mapping যাচাই করতে হবে। তারপর staging WordPress-এ dry-run import, record count ও permalink যাচাই, duplicate check, বাংলা ইউনিকোড এবং ছবি/ভিডিও URL পরীক্ষা করতে হবে। কোনো production import বা DNS পরিবর্তন এই থিম/প্লাগইন একা করে না।
