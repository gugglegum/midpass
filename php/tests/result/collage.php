<?php

require_once __DIR__ . '/../../init.php';

$startNum = 1;
$endNum = 999;

$colsNum = 4;
$rowsNum = (int) ceil(($endNum - $startNum + 1) / $colsNum);

$captchaWidth = 130;
$captchaHeight = 30;

$image = imagecreatetruecolor($colsNum * $captchaWidth, $rowsNum * $captchaHeight);
$captchaData = json_decode(file_get_contents(__DIR__ . '/../captcha.json'), true);

$i = 1;
for ($n = $startNum; $n <= $endNum; $n++) {
    $captchaFileName = 'captcha-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . '.jpg';
    $captchaResultFileName = 'captcha-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . '_result.jpg';

//    var_dump($captchaData[$captchaFileName][0]);
//    if ($captchaData[$captchaFileName][0] == 'm') { // m, y, v, b, p, q, d, r, i, l, f, h, j, n
        $captchaImage = imagecreatefromjpeg(PROJECT_ROOT_DIR . '/tests/result/' . $captchaResultFileName);
        $row = floor(($i - 1) / $colsNum);
        $col = ($i - 1) % $colsNum;
        imagecopy($image, $captchaImage, $col * $captchaWidth, $row * $captchaHeight, 0, 0, $captchaWidth, $captchaHeight);
        $i++;
//    }

}
imagepng($image, PROJECT_ROOT_DIR . '/tests/result/captcha_result_all.png', 9);
