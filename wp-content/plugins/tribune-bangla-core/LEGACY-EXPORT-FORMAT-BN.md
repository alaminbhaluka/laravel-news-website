# Laravel Export JSON — বাংলা নির্দেশিকা

এই importer কেবল JSON export গ্রহণ করে। এটি Laravel database-এ সংযোগ করে না এবং কোনো Laravel record update/delete করে না। JSON ফাইল WordPress-এর web root-এর বাইরে রাখুন।

## ১. Dry-run ও import কমান্ড

সার্ভারে WP-CLI ও Tribune Bangla Core plugin সক্রিয় করার পর:

```bash
wp tribune import /secure/path/laravel-export.json
```

এটি শুধু JSON যাচাই করে record count দেখায়; কোনো রেকর্ড তৈরি করে না।

Staging WordPress backup নেওয়া ও export যাচাইয়ের পর:

```bash
wp tribune import /secure/path/laravel-export.json --apply --author=1
```

`--author=1` হলো fallback WordPress user ID; বাস্তব সাইটে সঠিক ID দিন। আগে legacy author mapping তৈরি থাকলে importer সেই mapping ব্যবহার করবে।

## ২. JSON কাঠামো

```json
{
  "categories": [
    {"id": 10, "name": "জাতীয়", "slug": "national"}
  ],
  "tags": [
    {"id": 20, "name": "রাজনীতি", "slug": "politics"}
  ],
  "posts": [
    {
      "id": 1001,
      "title": "সংবাদের শিরোনাম",
      "slug": "sample-news",
      "content": "<p>সংবাদের বিস্তারিত...</p>",
      "excerpt": "সংক্ষিপ্ত বিবরণ",
      "status": "draft",
      "date": "2026-11-20 10:30:00",
      "author_id": 55,
      "category_ids": [10],
      "tag_ids": [20],
      "language": "bn",
      "translation_group": "1001",
      "legacy_url": "/news/sample-news"
    }
  ],
  "pages": [
    {"id": 30, "title": "আমাদের সম্পর্কে", "slug": "about-us", "content": "পরিচিতি", "status": "draft"}
  ],
  "comments": [
    {"id": 900, "post_id": 1001, "author": "পাঠক", "email": "reader@example.com", "content": "মন্তব্য", "approved": false}
  ]
}
```

## ৩. গুরুত্বপূর্ণ mapping

- `id` হলো Laravel-এর পুরোনো primary key; WordPress-এ সেটি legacy meta হিসেবে সংরক্ষিত হয়।
- `category_ids` এবং `tag_ids`-এ export করা category/tag ID দিতে হবে।
- `author_id`-এর সঙ্গে WordPress user-এর `_tb_legacy_laravel_user_id` user meta mapping থাকতে হবে; না থাকলে --author fallback ব্যবহার হবে।
- অজানা বা অনিরাপদ post status `draft` করা হবে।
- Comments default-এ অনুমোদিত হবে না; `approved: true` হলে অনুমোদিত হবে।
- একই legacy post/comment ID পুনরায় import হলে সেটি skip করার চেষ্টা করা হয়।
- Images, videos, user accounts/passwords, galleries, ads, subscribers, mail/social settings ও geography এখনো এই JSON importer-এ স্থানান্তর হয় না। সেগুলোর জন্য পৃথক mapping ও যাচাই প্রয়োজন।

## ৪. নিরাপত্তা

1. প্রথমে database ও uploads backup নিন।
2. প্রথমে dry-run চালান।
3. staging WordPress-এ import করুন; production-এ নয়।
4. import শেষে count, বাংলা লেখা, permalink, author, category, tag ও comments মিলিয়ে নিন।
5. Duplicate skip-এর ওপর একমাত্র নির্ভর করবেন না; import-এর আগে এবং পরে আলাদা audit রাখুন।
6. JSON-এ password hash, API secret, database credentials বা ব্যক্তিগত অপ্রয়োজনীয় তথ্য রাখবেন না।
