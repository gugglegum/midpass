<?php

namespace App\CaptchaSolver;

class CaptchaSolverQMidPass implements CaptchaSolverInterface
{
    public string $expectedCaptchaCode;
    private array $bgColor = [0xff, 0xff, 0xff];
    private array $fontColor = [0x7f, 0x7f, 0x7f];

//    private string $fontFile = PROJECT_ROOT_DIR . '/arial.ttf';
//    private float $fontSize = 15;
//    private int $captchaPosX = 4;
//    private int $captchaPosY = 23;
    private string $fontFile = 'C:\Windows\Fonts\tahoma.ttf';
    private float $fontSize = 15;
    private int $captchaPosX = 5;
    private int $captchaPosY = 23;

//    private string $fontFile = PROJECT_ROOT_DIR . '/verdana.ttf';
//    private string $fontFile = PROJECT_ROOT_DIR . '/calibri.ttf';
//    private float $fontSize = 15;
//    private string $fontFile = PROJECT_ROOT_DIR . '/arial.ttf';
//    private float $fontSize = 15;

    private int $currentImagePosX;
    private int $currentImagePosY;

    public float $averageCharDiff;

    public array $charImages = [];

    public float $accuracy;

    public function solveCaptcha(\GdImage $image): ?string
    {
        $this->currentImagePosX = $this->captchaPosX;
        $this->currentImagePosY = $this->captchaPosY;
        $captchaCode = '';

//        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
//        $this->cleanFromNoise($image);

//        $textColor = imagecolorallocate($image, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
//        $text = 'e62202';
//        $angle = 0;
//        $x = 5;
//        $y = 23;
//        imagettftext($image, $this->fontSize, $angle, $x, $y, $textColor, $this->fontFile, $text);

        $this->averageCharDiff = 0.0;
        $alphabet = 'abcdefghijklmnopqrstuvwxy'; // without "z"
        $char = $this->findBestChar($image, $alphabet, $charDiff, $charWidth);
        $this->currentImagePosX += $charWidth;
        $this->averageCharDiff += $charDiff;
//        var_dump($char);
        $captchaCode .= $char;

        $digits = '0123456789';
        for ($i = 0; $i < 5; $i++) {
            $char = $this->findBestChar($image, $digits, $charDiff, $charWidth);
            $this->currentImagePosX += $charWidth;
            $this->averageCharDiff += $charDiff;
//            var_dump($char);
            $captchaCode .= $char;
        }
        $this->averageCharDiff /= 6;
        $this->accuracy = 100 - min(100, max(0, ($this->averageCharDiff - 20)));

//        $bestDiff = null;
//        $bestChar = null;
//        for ($i = 0; $i < strlen($alphabet); $i++) {
//            $char = $alphabet[$i];
//            $charImage = $this->createCharImage($char);
//            $charDiff = $this->charDiff($image, $charImage);
//            echo json_encode(['char' => $char, 'diff' => $charDiff]), "\n";
//            if ($bestDiff === null || $bestDiff > $charDiff) {
//                $bestDiff = $charDiff;
//                $bestChar = $char;
//            }
//        }
//        var_dump($bestChar);die;


//        $charImage = $this->createCharImage('y', $charYOffset);
//        $charImageWidth = imagesx($charImage);
//        $charImageHeight = imagesy($charImage);
//        for ($y = 0; $y < $charImageHeight; $y++) {
//            for ($x = 0; $x < $charImageWidth; $x++) {
//                $color1 = imagecolorsforindex($charImage, imagecolorat($charImage, $x, $y));
//                $color2 = imagecolorallocate($image, $color1['red'], $color1['green'], $color1['blue']);
//                imagesetpixel($image, $currentImagePosX + $x, $currentImagePosY + $y - $charImageHeight + $charYOffset, $color2);
//                echo '.';
//            }
//        }


//        $currentImagePosX += $charImageWidth;

        // imagefilledrectangle($image, 0, 0, imagesx($image) - 1, imagesy($image) - 1, imagecolorallocate($image, 255, 255, 255));
//        $this->currentImagePosX = $this->captchaPosX;
//        $this->currentImagePosY = $this->captchaPosY;
//        //$this->drawText($image, $this->currentImagePosX, $this->currentImagePosY, $this->expectedCaptchaCode, [196, 32, 32], $charWidth);
//        for ($i = 0; $i < strlen($this->expectedCaptchaCode); $i++) {
//            //$color = [96, 96, 196]; // blue
//            //$color = [255, 255, 255]; // white
//            $color = [96, 96, 96]; // dark grey
//            //$color = $this->fontColor;
//            $this->drawChar($image, $this->currentImagePosX + 2, $this->currentImagePosY + 0, $this->expectedCaptchaCode[$i], $color, $charWidth);
//            $this->currentImagePosX += $charWidth;
//        }

//        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
//        imagejpeg($image, PROJECT_ROOT_DIR . '/captcha_result.jpg', 100);

        if ($this->averageCharDiff > 60) {
            $captchaCode = null;
        }

        return $captchaCode;
    }

    private function cleanFromNoise(\GdImage $image, int $maxDelta, array $targetColor, array $bgColor): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $colorIndex = imagecolorsforindex($image, $color);
                if ($this->colorDiff1([$colorIndex['red'], $colorIndex['green'], $colorIndex['blue']], $targetColor) > $maxDelta) {
                    $bgColorIndex = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
                    imagesetpixel($image, $x, $y, $bgColorIndex);
                }
            }
        }
    }

    private function colorDiff1(array $color1, array $color2): float
    {
        return sqrt(pow($color1[0] - $color2[0], 2) + pow($color1[1] - $color2[1], 2) + pow($color1[2] - $color2[2], 2));
    }

    /**
     * @param array $color1 Char image
     * @param array $color2 CAPTCHA image
     * @return float
     */
    private function colorDiff2(array $color1, array $color2): float
    {
//        if ($color2[0] > 220 && $color2[1] > 220 && $color2[2] > 220) {
//            $color2[0] = 255;
//            $color2[1] = 255;
//            $color2[2] = 255;
//        }

        $diff = sqrt(pow($color1[0] - $color2[0], 2) + pow($color1[1] - $color2[1], 2) + pow($color1[2] - $color2[2], 2));

        // Добавляем diff, если в букве серый пиксель, а в картинке белый
//        $delta = 50; // 970
//        if (($color1[0] > 127 - $delta && $color1[0] < 127 + $delta && $color1[1] > 127 - $delta && $color1[1] < 127 + $delta && $color1[2] > 127 - $delta && $color1[2] < 127 + $delta) && ($color2[0] > 200 && $color2[1] > 200 && $color2[2] > 200)) {
//        $delta = 40; // 971
//        if (($color1[0] > 127 - $delta && $color1[0] < 127 + $delta && $color1[1] > 127 - $delta && $color1[1] < 127 + $delta && $color1[2] > 127 - $delta && $color1[2] < 127 + $delta) && ($color2[0] > 180 && $color2[1] > 180 && $color2[2] > 180)) {
//        $delta = 40; // 972
//        if (($color1[0] > 127 - $delta && $color1[0] < 127 + $delta && $color1[1] > 127 - $delta && $color1[1] < 127 + $delta && $color1[2] > 127 - $delta && $color1[2] < 127 + $delta) && ($color2[0] > 175 && $color2[1] > 175 && $color2[2] > 175)) {
        $delta = 40; // 973
        if (($color1[0] > 127 - $delta && $color1[0] < 127 + $delta && $color1[1] > 127 - $delta && $color1[1] < 127 + $delta && $color1[2] > 127 - $delta && $color1[2] < 127 + $delta) && ($color2[0] > 175 && $color2[1] > 175 && $color2[2] > 175)) {
            $diff += 2000;
        }

        // Добавляем diff, если на картинке серый пиксель, а в букве белый
        $delta = 3;
        if (($color2[0] > 127 - $delta && $color2[0] < 127 + $delta && $color2[1] > 127 - $delta && $color2[1] < 127 + $delta && $color2[2] > 127 - $delta && $color2[2] < 127 + $delta) && ($color1[0] > 200 && $color1[1] > 200 && $color1[2] > 200)) {
            $diff += 2000;
        }

        // Если на картинке цветной пиксель - уменьшаем diff в 10 раз
        $d = 30;
        if (abs($color2[0] - $color2[1]) > $d || abs($color2[1] - $color2[2]) > $d || abs($color2[0] - $color2[2]) > $d) {
            $diff /= 10;
        }

        return $diff;
    }

    private function createCharImage(string $char, &$yOffset): \GdImage
    {
        $angle = 0;
        $text = $char;
        $bbox = imagettfbbox($this->fontSize, $angle, $this->fontFile, $text);
//        $textWidth = $bbox[2] - $bbox[0] + 1;
        $textWidth = $this->getCharWidth($char);
        $textHeight = $bbox[1] - $bbox[7];
//        var_dump($bbox, $textWidth, $textHeight);die;
        $image = imagecreatetruecolor($textWidth, $textHeight);
        $bgColor = imagecolorallocate($image, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]);
        imagefill($image, 0, 0, $bgColor);
//        $textColor = imagecolorallocate($image, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
        $textColor = imagecolorallocate($image, 75, 75, 75);
        $yOffset = $bbox[1];
        imagettftext($image, $this->fontSize, $angle, 0, $textHeight - $yOffset, $textColor, $this->fontFile, $text);
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        return $image;
    }

    private function createCharImage2(string $char, &$yOffset): \GdImage
    {
        $charsDir = PROJECT_ROOT_DIR . '/../assets/captcha/chars';
        $yOffset = json_decode(file_get_contents($charsDir . '/chars.json'), true)[$char]['yOffset'];
        $image = imagecreatefrompng($charsDir . '/char_' . $char . '.png');
//        $this->cleanFromNoise($image, 160, $this->fontColor, $this->bgColor);
        return $image;
    }

    private function getCharWidth(string $char): int
    {
        $charWidths = [
            'a' => 11, 'b' => 11, 'c' => 9, 'd' => 11, 'e' => 11, 'f' => 6, 'g' => 11, 'h' => 11, 'i' => 5,
            'j' => 6, 'k' => 10, 'l' => 5, 'm' => 17, 'n' => 11, 'o' => 11, 'p' => 11, 'q' => 11, 'r' => 7,
            's' => 9, 't' => 7, 'u' => 11, 'v' => 10, 'w' => 15, 'x' => 10, 'y' => 10, 'z' => 11, '0' => 11,
            '1' => 11, '2' => 11, '3' => 11, '4' => 11, '5' => 11, '6' => 11, '7' => 11, '8' => 11, '9' => 11,
        ];
        return array_key_exists($char, $charWidths) ? $charWidths[$char] : 11;
    }

    private function drawText(\GdImage $image, int $x, int $y, $text, $color, &$textWidth): void
    {
        $textColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
        imagettftext($image, $this->fontSize, 0, $x, $y, $textColor, $this->fontFile, $text);
        $bbox = imagettfbbox($this->fontSize, 0, $this->fontFile, $text);
        $textWidth = $bbox[2] - $bbox[0];
    }

    private function drawChar(\GdImage $image, int $x, int $y, $char, $color, &$textWidth): void
    {
        $this->drawText($image, $x, $y, $char, $color, $textWidth);
        $textWidth = $this->getCharWidth($char);
    }

    private function charDiff(\GdImage $image, \GdImage $charImage, int $charYOffset): float
    {
        $charDiff = 0.0;
        $charImageWidth = imagesx($charImage);
        $charImageHeight = imagesy($charImage);
        for ($y = 0; $y < $charImageHeight; $y++) {
            for ($x = 0; $x < $charImageWidth; $x++) {
                $color1 = imagecolorsforindex($charImage, imagecolorat($charImage, $x, $y));
                $color2 = imagecolorsforindex($image, imagecolorat($image, $this->currentImagePosX + $x, $this->currentImagePosY + $y - $charImageHeight + $charYOffset));
                $charDiff += $this->colorDiff2([$color1['red'], $color1['green'], $color1['blue']], [$color2['red'], $color2['green'], $color2['blue']]);
            }
        }
//        return $charDiff / $charImageWidth / $charImageHeight;
//        return $charDiff / $charImageWidth / $charImageHeight - ($charImageWidth * $charImageHeight) / 5;
//        return $charDiff / $charImageWidth / $charImageHeight + 2750 / ($charImageWidth * $charImageHeight);
        return $charDiff / $charImageWidth / $charImageHeight + 2050 / ($charImageWidth * $charImageHeight);
    }

    private function findBestChar(\GdImage $image, string $chars, &$charDiff, &$charWidth): string
    {
        $bestDiff = null;
        $bestChar = null;
        $bestCharWidth = null;
        $len = strlen($chars);
        for ($i = 0; $i < $len; $i++) {
            $char = $chars[$i];
            $charImage = $this->createCharImage2($char, $charYOffset);
            $charDiff = $this->charDiff($image, $charImage, $charYOffset);

            if (!array_key_exists($char, $this->charImages) || $this->charImages[$char]['diff'] > $charDiff) {
                $captchaCharImage = imagecreatetruecolor(imagesx($charImage), imagesy($charImage));
                imagecopy($captchaCharImage, $image, 0, 0, $this->currentImagePosX, $this->currentImagePosY - imagesy($charImage) + $charYOffset, imagesx($charImage), imagesy($charImage));
                $this->charImages[$char] = [
                    'image' => $captchaCharImage,
                    'diff' => $charDiff,
                    'yOffset' => $charYOffset,
                ];
            }

//            echo json_encode(['char' => $char, 'diff' => round($charDiff, 2)]), "\n";
            if ($bestDiff === null || $bestDiff > $charDiff) {
                $bestDiff = $charDiff;
                $bestChar = $char;
                $bestCharWidth = $this->getCharWidth($char);
            }
        }
        $charDiff = $bestDiff;
        $charWidth = $bestCharWidth;
        return $bestChar;
    }
}
