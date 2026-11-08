# تفعيل لوحة صور رحّال على Hostinger

1. تأكد من نشر آخر ملفات GitHub إلى مجلد public_html في Hostinger، وبالأخص admin/images.php و index.html.
2. من File Manager أنشئ ملفًا **على Hostinger فقط** بالمسار public_html/api/rahhal-media-secret.php. **لا ترفعه إلى GitHub**.
3. ضع داخله هذا المثال، مع تغيير كلمة المرور الطويلة بكلمة مرور خاصة بك (20 حرفًا أو أكثر):

```php
<?php
return password_hash('REPLACE-WITH-YOUR-OWN-VERY-LONG-PASSWORD', PASSWORD_DEFAULT);
```

4. افتح https://rahhaal.com/admin/images.php وسجل الدخول بكلمة المرور التي اخترتها.
5. اختر الدولة، واكتب اسم المكان **كما يظهر حرفيًا في بطاقة رحّال**، ثم اختر الصورة واضغط حفظ.
6. يضغط النظام الصورة إلى WebP ويحفظها في public_html/assets/uploads/<country>/ ويحدث public_html/assets/rahhal-image-overrides.json تلقائيًا. تظهر الصورة على الموقع بعد تحديث الصفحة.
7. يجب أن يكون PHP GD مع WebP مفعّلًا، وأن يكون مجلد assets قابلًا للكتابة بواسطة PHP. الحد الأقصى للصورة الأصلية 8 MB أو حد upload_max_filesize الأصغر في إعدادات PHP.
8. احتفظ بنسخة احتياطية من مجلد assets/uploads وملف assets/rahhal-image-overrides.json عند تحديث الاستضافة. هذه الصور تُحفظ على Hostinger فقط ولا تنتقل تلقائيًا إلى GitHub.
9. لا تشارك كلمة المرور أو ملف الإعدادات في المحادثات أو المستودعات. لا تضع لوحة الإدارة في قائمة الموقع العامة.

**تنبيه:** يجب نشر التعديلات من GitHub إلى Hostinger قبل أن تعمل اللوحة. رابط لوحة الإدارة سيعطي 404 إن لم تُنشر الملفات.
