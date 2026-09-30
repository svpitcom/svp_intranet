<?php $pdfFontRoot = str_replace('\\', '/', BASE_PATH) . '/public/assets/fonts'; ?>
@font-face {
    font-family: 'Sarabun';
    font-style: normal;
    font-weight: normal;
    src: url('<?= $pdfFontRoot ?>/Sarabun-Regular.ttf') format('truetype');
}
@font-face {
    font-family: 'Sarabun';
    font-style: normal;
    font-weight: bold;
    src: url('<?= $pdfFontRoot ?>/Sarabun-Bold.ttf') format('truetype');
}
@font-face {
    font-family: 'Sarabun';
    font-style: italic;
    font-weight: normal;
    src: url('<?= $pdfFontRoot ?>/Sarabun-Italic.ttf') format('truetype');
}
@font-face {
    font-family: 'Sarabun';
    font-style: italic;
    font-weight: bold;
    src: url('<?= $pdfFontRoot ?>/Sarabun-BoldItalic.ttf') format('truetype');
}
