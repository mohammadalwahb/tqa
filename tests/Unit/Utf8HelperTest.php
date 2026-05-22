<?php

use App\Support\Utf8Helper;

it('keeps valid utf8 kurdish text unchanged', function () {
    $name = 'ئەحمەد عەلی محەمەد';
    expect(Utf8Helper::toUtf8($name))->toBe($name);
});

it('converts cp1256 encoded kurdish text to utf8 when the encoding is available', function () {
    if (! in_array('CP1256', mb_list_encodings(), true)) {
        $this->markTestSkipped('CP1256 is not available in this PHP build.');
    }

    $name  = 'ئەحمەد';
    $bytes = mb_convert_encoding($name, 'CP1256', 'UTF-8');

    expect(Utf8Helper::toUtf8($bytes))->toBe($name);
});
