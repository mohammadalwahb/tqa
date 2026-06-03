# PDF Arabic / Kurdish fonts

`NotoSansArabic-Regular.ttf` is required for correct Arabic and Kurdish script in PDF exports (DomPDF).

If the file is missing, download it:

```bash
curl -fsSL -o resources/fonts/NotoSansArabic-Regular.ttf \
  "https://cdn.jsdelivr.net/gh/notofonts/noto-fonts@main/hinted/ttf/NotoSansArabic/NotoSansArabic-Regular.ttf"
```

Licensed under the [SIL Open Font License](https://scripts.sil.org/OFL).

## Arabic shaping (PDF)

PDFs also require the Composer package `khaled.alshamaa/ar-php` (and PHP `ext-calendar`). On the server after deploy:

```bash
composer install --no-dev --optimize-autoloader
```

If `vendor/` is not uploaded with your code, this step is required or PDF export will fail / Arabic text will not shape correctly.
