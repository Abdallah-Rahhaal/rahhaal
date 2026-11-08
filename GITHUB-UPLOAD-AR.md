# رفع مشروع رحّال إلى GitHub

المستودع: https://github.com/Abdallah-Rahhaal/rahhaal

## الطريقة الأسهل: GitHub Desktop

1. فك ضغط ملف `rahhal-github-ready.zip` على جهازك. لا ترفع ZIP نفسه إلى GitHub.
2. افتح GitHub Desktop وسجّل الدخول إلى حسابك.
3. اختر File > Clone repository وحدد `Abdallah-Rahhaal/rahhaal` ومجلدًا محليًا.
4. انسخ **محتويات** مجلد `rahhal-github-ready` المفكوك إلى مجلد المستودع المحلي، بحيث يصبح `index.html` في الجذر.
5. في GitHub Desktop اكتب ملخصًا مثل `Update Rahhal website and dynamic stats` ثم Commit to main.
6. اضغط Push origin.

## بديل: Git (في الطرفية)

```bash
git clone https://github.com/Abdallah-Rahhaal/rahhaal.git
# انسخ محتويات المجلد المفكوك إلى مجلد rahhaal
cd rahhaal
git add .
git commit -m "Update Rahhal website and dynamic stats"
git push origin main
```

## تنبيه أمني مهم

- لا ترفع `api/db-config.php` أو `api/openai-config.php` إلى GitHub؛ الملفان يحويان بيانات سرية على Hostinger.
- تم وضع ملفي `.example.php` بديلين بدون أي مفاتيح حقيقية.
- ملف `.gitignore` يستبعد الملفات السرية من أي إضافات لاحقة.
- عند النشر على Hostinger احتفظ بملفي الإعدادات الحقيقيين الموجودين على الخادم، ولا تستبدلهما بملفات الأمثلة.
- GitHub لا يشغّل PHP أو قاعدة MySQL عبر GitHub Pages؛ التشغيل الكامل يبقى على Hostinger.
