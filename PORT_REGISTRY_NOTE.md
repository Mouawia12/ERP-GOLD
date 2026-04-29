# PORT REGISTRY NOTE

> هذا المشروع موثق في السجل المركزي للمنافذ.

## السجل المركزي

**الملف:** `/Users/mw/Downloads/DEV_PORT_REGISTRY.md`

## منافذ هذا المشروع

| الخدمة | المنفذ | ملاحظة |
|--------|:------:|--------|
| Laravel Backend (ERP-GOLD-main) | **8006** | كان 8080 — تعارض مع allal-article Spring Boot! |
| Redis | **6379** | افتراضي |
| MySQL/MariaDB | **3306** | XAMPP |

## إجراءات مطلوبة

في `ERP-GOLD-main/.env`:
```
APP_URL=http://127.0.0.1:8006
```
وشغّل: `php artisan serve --port=8006`
