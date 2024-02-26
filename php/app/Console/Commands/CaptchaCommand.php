<?php

namespace App\Console\Commands;

use App\CaptchaSolver\CaptchaSolverQMidPass;

class CaptchaCommand extends AbstractCommand
{
    public function __invoke(): ?int
    {
        $tests = [
            'captcha-001.jpg' => 'e62202',
            'captcha-002.jpg' => 'y91106',
            'captcha-003.jpg' => 'i33326',
            'captcha-004.jpg' => 'm85565',
            'captcha-005.jpg' => 'j76028',
            'captcha-006.jpg' => 'k36094',
            'captcha-007.jpg' => 's62210',
            'captcha-008.jpg' => 'c62014',
            'captcha-009.jpg' => 'f92422',
            'captcha-010.jpg' => 'l79147',
            'captcha-011.jpg' => 'k54912',
            'captcha-012.jpg' => 'o84727',
            'captcha-013.jpg' => 'h20098',
            'captcha-014.jpg' => 'n28464',
            'captcha-015.jpg' => 'w26983',
            'captcha-016.jpg' => 'v72779',
            'captcha-017.jpg' => 'j22074',
            'captcha-018.jpg' => 'd13886',
            'captcha-019.jpg' => 'y48166',
            'captcha-020.jpg' => 'e87385',
            'captcha-021.jpg' => 'h42554',
            'captcha-022.jpg' => 'b85150',
            'captcha-023.jpg' => 'p64432',
            'captcha-024.jpg' => 'x52448',
            'captcha-025.jpg' => 'w12786',
            'captcha-026.jpg' => 'n30631',
            'captcha-027.jpg' => 'p34307',
            'captcha-028.jpg' => 'm56349',
            'captcha-029.jpg' => 'o84645',
            'captcha-030.jpg' => 'k29518',
            'captcha-031.jpg' => 'y88990',
            'captcha-032.jpg' => 'n98025',
            'captcha-033.jpg' => 'm82389',
            'captcha-034.jpg' => 'v80080',
            'captcha-035.jpg' => 'e30962',
            'captcha-036.jpg' => 'i64768',
            'captcha-037.jpg' => 'd81299',
            'captcha-038.jpg' => 'w97830',
            'captcha-039.jpg' => 'g95521',
            'captcha-040.jpg' => 'p15550',
            'captcha-041.jpg' => 'r11083',
            'captcha-042.jpg' => 'g68171',
            'captcha-043.jpg' => 'a83138',
            'captcha-044.jpg' => 'l47099',
            'captcha-045.jpg' => 'm41415',
            'captcha-046.jpg' => 'k65021',
            'captcha-047.jpg' => 'p98827',
            'captcha-048.jpg' => 'x55714',
            'captcha-049.jpg' => 'n67554',
            'captcha-050.jpg' => 'd43031',
            'captcha-051.jpg' => 'y69515',
            'captcha-052.jpg' => 'r35288',
            'captcha-053.jpg' => 'b28538',
            'captcha-054.jpg' => 'j65471',
            'captcha-055.jpg' => 'y36506',
            'captcha-056.jpg' => 't53037',
            'captcha-057.jpg' => 'g49414',
            'captcha-058.jpg' => 'u38371',
            'captcha-059.jpg' => 'j13848',
            'captcha-060.jpg' => 'k57359',
            'captcha-061.jpg' => 'j81657',
            'captcha-062.jpg' => 'j94376',
            'captcha-063.jpg' => 'q41310',
            'captcha-064.jpg' => 'p66479',
            'captcha-065.jpg' => 'y64170',
            'captcha-066.jpg' => 'l28131',
            'captcha-067.jpg' => 'e83904',
            'captcha-068.jpg' => 'e19072',
            'captcha-069.jpg' => 'g59705',
            'captcha-070.jpg' => 't82814',
            'captcha-071.jpg' => 't75567',
            'captcha-072.jpg' => 'v13862',
            'captcha-073.jpg' => 'v34589',
            'captcha-074.jpg' => 'y62884',
            'captcha-075.jpg' => 'w37295',
            'captcha-076.jpg' => 't97758',
            'captcha-077.jpg' => 'l21162',
            'captcha-078.jpg' => 'u80385',
            'captcha-079.jpg' => 'q98480',
            'captcha-080.jpg' => 'j40226',
            'captcha-081.jpg' => 'b24067',
            'captcha-082.jpg' => 'm68075',
            'captcha-083.jpg' => 'g84605',
            'captcha-084.jpg' => 'k18459',
            'captcha-085.jpg' => 'y26352',
            'captcha-086.jpg' => 'f61722',
            'captcha-087.jpg' => 'l97092',
            'captcha-088.jpg' => 'e62864',
            'captcha-089.jpg' => 'c37275',
            'captcha-090.jpg' => 'd31591',
            'captcha-091.jpg' => 'u85801',
            'captcha-092.jpg' => 'm50010',
            'captcha-093.jpg' => 'i68104',
            'captcha-094.jpg' => 'r61354',
            'captcha-095.jpg' => 'd62172',
            'captcha-096.jpg' => 'w81087',
            'captcha-097.jpg' => 's58376',
            'captcha-098.jpg' => 't85108',
            'captcha-099.jpg' => 'k91265',
            'captcha-100.jpg' => 'y39417',
        ];
//        for ($i = 101; $i < 1000; $i++) {
//            $filename = 'captcha-' . $i . '.jpg';
//            if (!array_key_exists($filename, $tests)) {
//                $tests[$filename] = '';
//            }
//        }
//        file_put_contents(PROJECT_ROOT_DIR . '/tests/captcha.json', json_encode($tests, JSON_PRETTY_PRINT));die;

        $report = fopen(PROJECT_ROOT_DIR . '/captcha_report.csv', 'w');

        // "captcha-538.jpg": "i19410" - тут не 100% точно i или l. Но вроде больше похоже на l, т.к. l чуть выше
        $tests = json_decode(file_get_contents(PROJECT_ROOT_DIR . '/tests/captcha.json'), true, JSON_THROW_ON_ERROR);

//        $tests = array_intersect_key($tests, array_fill_keys([
//            'captcha-791.jpg',
//            'captcha-588.jpg',
//            'captcha-936.jpg',
//            'captcha-959.jpg',
//            'captcha-208.jpg',
//            'captcha-336.jpg',
//            'captcha-084.jpg',
//            'captcha-271.jpg',
//            'captcha-722.jpg',
//            'captcha-580.jpg',
//            'captcha-766.jpg',
//            'captcha-551.jpg',
//            'captcha-481.jpg',
//            'captcha-928.jpg',
//            'captcha-705.jpg',
//            'captcha-538.jpg',
//            'captcha-690.jpg',
//            'captcha-786.jpg',
//            'captcha-956.jpg',
//            'captcha-601.jpg',
//            'captcha-430.jpg',
//            'captcha-694.jpg',
//            'captcha-785.jpg',
//            'captcha-610.jpg',
//            'captcha-254.jpg',
//            'captcha-622.jpg',
//            'captcha-556.jpg',
//            'captcha-026.jpg',
//            'captcha-830.jpg',
//            'captcha-809.jpg',
//            'captcha-299.jpg',
//            'captcha-258.jpg',
//            'captcha-905.jpg',
//            'captcha-049.jpg',
//            'captcha-641.jpg',
//            'captcha-335.jpg',
//            'captcha-560.jpg',
//            'captcha-103.jpg',
//            'captcha-714.jpg',
//            'captcha-066.jpg',
//            'captcha-422.jpg',
//            'captcha-735.jpg',
//            'captcha-488.jpg',
//            'captcha-217.jpg',
//            'captcha-880.jpg',
//            'captcha-190.jpg',
//            'captcha-412.jpg',
//            'captcha-952.jpg',
//            'captcha-247.jpg',
//            'captcha-508.jpg',
//            'captcha-202.jpg',
//            'captcha-092.jpg',
//            'captcha-958.jpg',
//            'captcha-138.jpg',
//            'captcha-555.jpg',
//            'captcha-144.jpg',
//            'captcha-185.jpg',
//            'captcha-157.jpg',
//            'captcha-355.jpg',
//            'captcha-231.jpg',
//        ], ''));

//        $alphabet = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
//        foreach ($tests as $code) {
//            if (($key = array_search($code[0], $alphabet)) !== false) {
//                unset($alphabet[$key]);
//            }
//        }
//        echo "Not covered letters in tests: " . json_encode(array_values($alphabet)) . "\n";die;

        $testCounter = 0;
        $successCounter = 0;
        $totalAverageCharDiff = 0;
        $captchaSolver = new CaptchaSolverQMidPass();
        foreach ($tests as $captchaImgName => $expectedCaptchaCode) {
//            if ($expectedCaptchaCode[0] !== 'n') { // h,
//                continue;
//            }
            $testCounter++;
            $image = imagecreatefromjpeg(PROJECT_ROOT_DIR . '/tests/' . $captchaImgName);
            $captchaSolver->expectedCaptchaCode = $expectedCaptchaCode;

            $verbose = true;
            if ($verbose) echo "{$captchaImgName}: ";
            $resultCaptchaCode = $captchaSolver->solveCaptcha($image);
            if ($verbose) echo "expected: {$expectedCaptchaCode}, result: {$resultCaptchaCode}, diff: " . number_format(round($captchaSolver->averageCharDiff, 2), 2) . ", accuracy = " . number_format(round($captchaSolver->accuracy, 2), 2) . " - ";
            $totalAverageCharDiff += $captchaSolver->averageCharDiff;
            $reportRow = [
                'captha' => $captchaImgName,
                'expected' => $expectedCaptchaCode,
                'result' => $resultCaptchaCode,
                'diff' => str_replace('.', ',', (string) round($captchaSolver->averageCharDiff, 2)),
                'accuracy' => str_replace('.', ',', (string) round($captchaSolver->accuracy, 2)),
            ];
            if ($resultCaptchaCode === $expectedCaptchaCode) {
                if ($verbose) echo "OK\n";
                $successCounter++;
                $reportRow['status'] = 'OK';
            } else {
                if ($verbose) echo "ERROR\n";
                $reportRow['status'] = 'ERROR';
            }
            fputcsv($report, $reportRow);


//            // Update captcha.json
//            if (preg_match('/^captcha-(\d+)\.jpg$/i', $captchaImgName, $m)) {
//                if ($m[1] >= 200 /*&& $expectedCaptchaCode == ''*/) {
//                    $tests[$captchaImgName] = $resultCaptchaCode . implode('', array_fill(0, round(abs($captchaSolver->averageCharDiff - 30) / 10), '!'));
//                    file_put_contents(PROJECT_ROOT_DIR . '/tests/captcha.json', json_encode($tests, JSON_PRETTY_PRINT));
//                }
//            }

            // imagejpeg($image, PROJECT_ROOT_DIR . '/tests/result/' . preg_replace('/(captcha-\d+)/', '$1_result', $captchaImgName), 100);
        }

        // $this->saveChars($captchaSolver);

        echo "\nTotal {$successCounter} / {$testCounter} (" . round($successCounter / $testCounter * 100, 3) . "%) tests successful\n";
        $totalAverageCharDiff /= $testCounter;
        echo "Total average char diff: " . round($totalAverageCharDiff, 3) . "\n";

        return 0;
    }

    private function saveChars($captchaSolver): void
    {
        $charsData = [];
        foreach ($captchaSolver->charImages as $char => $charData) {
            echo "Save char \"{$char}\", diff: " . round($charData['diff'], 2) . "\n";
            imagepng($charData['image'], PROJECT_ROOT_DIR . '/tests/chars/char_' . $char . '.png');
            $charsData[$char] = ['yOffset' => $charData['yOffset']];
        }
        file_put_contents(PROJECT_ROOT_DIR . '/tests/chars/chars.json', json_encode($charsData));
    }

}
