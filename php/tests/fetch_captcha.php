<?php

for ($i = 1; $i < 1000; $i++) {
    $filename = 'captcha-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.jpg';
    if (!file_exists($filename)) {
        echo "Download {$filename}\n";
        exec("curl https://q.midpass.ru/ru/Account/CaptchaImage?" . (floor(microtime(true) * 1000)) . " -o " . $filename);
        sleep(rand(3, 7));
    }
}
