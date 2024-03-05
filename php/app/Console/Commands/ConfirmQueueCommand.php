<?php

namespace App\Console\Commands;

use App\CaptchaSolver\CaptchaSolverQMidPass;
use App\Exceptions\PermanentException;
use App\ResourceManager;
use Carbon\Carbon;
use gugglegum\RetryHelper\RetryHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\PsrHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConfirmQueueCommand extends AbstractCommand
{
    private \Luracast\Config\Config $config;
    private \GuzzleHttp\Cookie\CookieJar $cookieJar;
    private \GuzzleHttp\Client $guzzle;
    private ConsoleOutput $output;
    private LoggerInterface $logger;
    private RetryHelper $guzzleRetryHelper;
    private bool $authorized = false;
    private array $state;

    private array $httpHeaders = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding' => 'gzip, deflate',
        'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
        'Cache-Control' => 'no-cache',
        'Dnt' => '1',
        'Pragma' => 'no-cache',
        'Sec-Ch-Ua' => '"Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'same-origin',
        'Sec-Fetch-User' => '?1',
        'Upgrade-Insecure-Requests' => '1',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    ];

    public function __construct(ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);

        // Config
        $this->config = $this->resourceManager->getConfig();

        // Logger
        $this->logger = new Logger('app');

        // Logger: Console
        $this->output = new ConsoleOutput();
        $styles = [
            'warning' => 'red',
            'notice' => 'yellow',
            'info' => 'default',
            'debug' => 'gray',
        ];
        foreach ($styles as $level => $style) {
            $this->output->getFormatter()->setStyle($level, new OutputFormatterStyle($style));
        }
        $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $consoleLogger = new ConsoleLogger(output: $this->output, formatLevelMap: [
            LogLevel::WARNING => 'warning',
            LogLevel::NOTICE => 'notice',
            LogLevel::DEBUG => 'debug',
        ]);
        $this->logger->pushHandler(new PsrHandler($consoleLogger));

        // Logger: File
        $logFile = PROJECT_ROOT_DIR . '/../logs/confirm-queue.log';
        $streamHandler = new StreamHandler($logFile, Logger::DEBUG);
        $outputFormat = "%datetime% %message%\n";
        $streamHandler->setFormatter(new LineFormatter($outputFormat, "Y-m-d H:i:s", true, true));
        $this->logger->pushHandler($streamHandler);

        // GuzzleHttp client
        $this->cookieJar = new \GuzzleHttp\Cookie\CookieJar;
        $this->guzzle = new Client([
            'allow_redirects' => false,
            'cookies' => $this->cookieJar,
        ]);
        $this->guzzleRetryHelper = (new RetryHelper())
            ->setIsTemporaryException(function(\Throwable $e) {
                return ($e instanceof \GuzzleHttp\Exception\ConnectException || $e instanceof \GuzzleHttp\Exception\ServerException);
            })
            ->setLogger($this->logger);

        // State file
        $this->loadState();
    }

    public function __destruct()
    {
        $this->saveState();
    }

    public function __invoke(): ?int
    {
        try {
            $retryHelper = (new RetryHelper())
                ->setLogger($this->logger)
                ->setIsTemporaryException(function(\Throwable $e) {
                    return !$e instanceof \App\Exceptions\PermanentException;
                })
                ->setOnFailure(function () {
                    if ($this->authorized) {
                        $this->logout();
                    }
                });

            // Step 0: Check if we need to do anything
            if (!$this->doNeedToConfirmAnything()) {
                $this->logger->notice("There's no need to do anything - everything already confirmed in the current 24-hour interval");
                return 0;
            }

            // Step 1: Solve CAPTCHA, Login
            $captchaCode = $retryHelper->execute(function () {
                $this->logger->notice("Start login");
                $this->logger->info("Load CAPTCHA for login");
                $captchaFilePath = $this->loadCaptcha();
                $this->logger->debug("Solve CAPTCHA");
                $captchaSolver = new CaptchaSolverQMidPass();
                $captchaImage = imagecreatefromjpeg($captchaFilePath);
                $captchaCode = $captchaSolver->solveCaptcha($captchaImage);
                unlink($captchaFilePath); // remove temporary file with CAPTCHA image
                if ($captchaCode === null) {
                    throw new \Exception("Unable to solve CAPTCHA");
                }
                $this->logger->debug("CAPTCHA: " . $captchaCode . " (accuracy: " . number_format(round($captchaSolver->accuracy, 1), 1) . '%)');
                if ($captchaSolver->accuracy < 70) {
                    throw new \Exception("CAPTCHA solution accuracy is too low - reload");
                }

                $this->logger->info("Login as {$this->config->get('queue.email')}");
                $this->login($this->config->get('queue.email'), $this->config->get('queue.password'), $captchaCode);
                $this->logger->notice("Successful login");
                return $captchaCode;
            }, 5);

            // Step 2: Get a list of waiting appointments
            $appointments = $retryHelper->execute(function () {
                // Loading list of appointments
                $this->logger->info("Load list of waiting appointments");
                return $this->fetchWaitingAppointments();
            }, 5);

            // Step 3: Confirm all waiting appointments that can be confirmed
            if (count($appointments)) {
                // Draw table with appointments
                $buffer = new BufferedOutput();
                $table = new Table($buffer);
                $table->setHeaders(['Full name', 'In queue since', 'Service name', 'Can confirm', 'Place in queue']);
                foreach ($appointments as $appointment) {
                    $table->addRow([
                        $appointment['FullName'],
                        strtok($appointment['ScheduledDateTimeString'], ' '),
                        $appointment['ServiceName'],
                        $appointment['CanConfirm'] ? 'Yes' : 'No',
                        $appointment['PlaceInQueue'],
                    ]);
                }
                $table->render();
                $this->logger->info("Waiting appointments: " . count($appointments) . "\n" . rtrim($buffer->fetch()));

                $state['WaitingAppointments']['LastConfirmation'] = [];
                foreach ($appointments as $appointment) {
                    $retryHelper->execute(function () use ($appointment, $captchaCode, &$state) {
                        if ($appointment['CanConfirm']) {
                            $this->logger->notice("Confirm appointment \"{$appointment['ServiceName']}\" for \"" . trim($appointment['FullName']) . "\"");
                            $this->confirmAppointment($appointment['WaitingAppointmentId'], $captchaCode);
                            $state['WaitingAppointments']['LastConfirmation'][$appointment['WaitingAppointmentId']] = (new \DateTime('now'))->format('c');
                        } else {
                            $this->logger->notice("Skip appointment \"{$appointment['ServiceName']}\" for \"" . trim($appointment['FullName']) . "\" - not available yet");
                            $state['WaitingAppointments']['LastConfirmation'][$appointment['WaitingAppointmentId']] = $this->state['WaitingAppointments']['LastConfirmation'][$appointment['WaitingAppointmentId']] ?? (new \DateTime('now'))->format('c');
                        }
                    }, 5);
                }
                $this->state = $state;
            } else {
                $this->logger->info("No waiting appointments");
            }

            // Step 4: Logout
            $retryHelper->execute(function () {
                $this->logger->info("Logout");
                $this->logout();
            }, 5);

            return 0;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->logger->debug("Exception of class " . get_class($e) . " was thrown in file {$e->getFile()} at line {$e->getLine()}\nDebug backtrace:\n{$e->getTraceAsString()}");
            return 255;
        }
    }

    /**
     * @return string
     * @throws \Throwable
     */
    private function loadCaptcha(): string
    {
        return $this->guzzleRetryHelper->execute(function() {
            $tick = floor(microtime(true) * 1000);
            $response = $this->httpGet('https://q.midpass.ru/ru/Account/CaptchaImage?' . $tick, [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Origin' => 'https://q.midpass.ru',
                    'Referer' => 'https://q.midpass.ru/',
                ],
            ]);
            //echo json_encode($response->getHeaders(), JSON_PRETTY_PRINT) . "\n";
            $captchaFilePath = PROJECT_ROOT_DIR . '/../temp/captcha-' . $tick . '.jpg';
            file_put_contents($captchaFilePath, $response->getBody()->getContents());
            return $captchaFilePath;
        }, 5);
    }

    /**
     * @param string $email
     * @param string $password
     * @param string $captchaCode
     * @return void
     * @throws GuzzleException
     */
    private function login(string $email, string $password, string $captchaCode): void
    {
        $payload = http_build_query([
            'NeedShowBlockWithServiceProviderAndCountry' => 'True',
            'CountryId' => $this->config->get('queue.countryId'),
            'ServiceProviderId' => $this->config->get('queue.serviceProviderId'),
            'Email' => $email,
            'g-recaptcha-response' => '',
            'Captcha' => $captchaCode,
            'Password' => $password,
        ]);
        $response = $this->httpPost('https://q.midpass.ru/ru/Account/DoPrivatePersonLogOn', $payload,[
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => 'https://q.midpass.ru/',
                'Content-Length' => strlen($payload),
            ],
        ]);
        $content = $response->getBody()->getContents();
//        file_put_contents(PROJECT_ROOT_DIR . '/login_form.html', $content);
//        $content = file_get_contents(PROJECT_ROOT_DIR . '/login_form.html');

        if (preg_match('|<div id="captchaError" class="registerForm">(.*)</div>|iuU', $content, $m)) {
            $errorMessage = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES);
            if ($errorMessage != '') {
                throw new \Exception("CAPTCHA error: " . $errorMessage);
            }
        }
        if (preg_match('|<span class="field-validation-error">(.*)</span>|iuU', $content, $m)) {
            $errorMessage = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES);
            throw new PermanentException("Login form error: " . $errorMessage);
        }

        $aspAuthCookie = $this->cookieJar->getCookieByName('.ASPXAUTH');
        if (!$aspAuthCookie) {
            throw new \Exception("Failed authorization: no \".ASPXAUTH\" cookie came from the server");
        }
        if ($aspAuthCookie->getValue() == '') {
            throw new \Exception("Failed authorization: \".ASPXAUTH\" cookie is empty");
        }

        $this->authorized = true;
    }

    private function logout(): void
    {
        $this->httpGet('https://q.midpass.ru/ru/Account/LogOff', [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Referer' => 'https://q.midpass.ru/ru/Appointments/WaitingList',
            ],
        ]);
        $this->authorized = false;
    }

    private function fetchWaitingAppointments(): array
    {
        // TODO: Handle "Count"
        $payload = http_build_query([
            'begin' => 0,
            'end' => 10,
        ]);
        return $this->guzzleRetryHelper->execute(function() use ($payload) {
            $response = $this->httpPost('https://q.midpass.ru/ru/Appointments/FindWaitingAppointments', $payload, [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Referer' => 'https://q.midpass.ru/ru/Appointments/WaitingList',
                    'Content-Length' => strlen($payload),
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
            ]);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!array_key_exists('Items', $data)) {
                throw new \Exception("Missing \"Items\" section in the FindWaitingAppointments JSON response");
            }
            if (!is_array($data['Items'])) {
                throw new \Exception("\"Items\" property is not array in the FindWaitingAppointments JSON response");
            }
            $appointments = [];
            foreach ($data['Items'] as $item) {
                $appointments[] = [
                    "WaitingAppointmentId" => $item["WaitingAppointmentId"] ?? null,
                    "PlaceInQueue" => $item["PlaceInQueue"] ?? null,
                    "CanConfirm" => $item["CanConfirm"] ?? null,
                    "CanCancel" => $item["CanCancel"] ?? null,
                    "Email" => $item["Email"] ?? null,
                    "FullName" => $item["FullName"] ?? null,
                    "PhoneNumber" => $item["PhoneNumber"] ?? null,
                    "ScheduledDateTimeString" => $item["ScheduledDateTimeString"] ?? null,
                    "ServiceProviderCode" => $item["ServiceProviderCode"] ?? null,
                    "ServiceId" => $item["ServiceId"] ?? null,
                    "ServiceName" => $item["ServiceName"] ?? null,
                ];
            }
            return $appointments;
        }, 5);
    }

    private function confirmAppointment(string $appointmentId, string $captchaCode): void
    {
        $payload = http_build_query([
            'ids' => $appointmentId,
            'captcha' => $captchaCode,
        ]);
        $this->guzzleRetryHelper->execute(function() use ($payload) {
            $response = $this->httpPost('https://q.midpass.ru/ru/Appointments/ConfirmWaitingAppointments', $payload, [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Referer' => 'https://q.midpass.ru/ru/Appointments/WaitingList',
                    'Content-Length' => strlen($payload),
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
            ]);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
//            echo "Content:\n";
//            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS) . "\n";

            if (array_key_exists('IsSuccessful', $data)) {
                if ($data['IsSuccessful']) {
                    $this->logger->notice("Successful confirmation");
                } else {
                    throw new \Exception((array_key_exists('ErrorMessage', $data) && $data['ErrorMessage'] != '') ? $data['ErrorMessage'] : 'Unknown error (missing error message from server)');
                }
            }
        }, 5);
    }

    /**
     * @throws \JsonException
     */
    private function loadState(): void
    {
        $file = PROJECT_ROOT_DIR . '/../temp/confirm-queue.json';
        if (file_exists($file)) {
            $this->state = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        } else {
            $this->state = [
                "WaitingAppointments" => [
                    "LastConfirmation" => [],
                ],
            ];
        }
    }

    /**
     * @return void
     * @throws \JsonException
     */
    private function saveState(): void
    {
        $file = PROJECT_ROOT_DIR . '/../temp/confirm-queue.json';
        file_put_contents($file, json_encode($this->state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    private function doNeedToConfirmAnything(): bool
    {
        $dtEarliestLastConfirmed = null;
        foreach ($this->state['WaitingAppointments']['LastConfirmation'] as $lastConfirmedAt) {
            $dtLastConfirmed = \Carbon\Carbon::createFromFormat('c', $lastConfirmedAt);
            if ($dtEarliestLastConfirmed === null || $dtEarliestLastConfirmed > $dtLastConfirmed) {
                $dtEarliestLastConfirmed = $dtLastConfirmed;
            }
        }
        if ($dtEarliestLastConfirmed !== null) {
            $dtRenewal = clone $dtEarliestLastConfirmed;
            $dtRenewal->setTimezone('Europe/Moscow');
            if ($dtRenewal->hour >= 3) {
                $dtRenewal->addDay();
            }
            $dtRenewal->setTime(3, 0); // 03:00 MSK - it's the moment of the confirmation renewal
            return $dtRenewal <= Carbon::now();
        }
        return true;
    }

    /**
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function httpGet(string $url, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->httpRequest('GET', $url, $options);
    }

    /**
     * @param string $url
     * @param callable|float|\Iterator|int|string|StreamInterface|null $payload
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function httpPost(string $url, callable|float|StreamInterface|\Iterator|int|string|null $payload, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $options[\GuzzleHttp\RequestOptions::BODY] = $payload;
        return $this->httpRequest('POST', $url, $options);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function httpRequest(string $method, string $url, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        if (!array_key_exists(\GuzzleHttp\RequestOptions::HEADERS, $options)) {
            $options[\GuzzleHttp\RequestOptions::HEADERS] = [];
        }
        $options[\GuzzleHttp\RequestOptions::HEADERS] = array_merge($this->httpHeaders, $options[\GuzzleHttp\RequestOptions::HEADERS]);
        ksort($options[\GuzzleHttp\RequestOptions::HEADERS]);

//        echo "{$method} {$url}\n";
//        echo "Request headers:\n" . json_encode($options[\GuzzleHttp\RequestOptions::HEADERS], JSON_PRETTY_PRINT) . "\n";
//        if (!empty($options[\GuzzleHttp\RequestOptions::BODY])) {
//            echo "Request body:\n" . json_encode($options[\GuzzleHttp\RequestOptions::BODY], JSON_PRETTY_PRINT) . "\n";
//        }

        $response = $this->guzzle->request($method, $url, $options);

//        echo "Status: {$response->getStatusCode()}\n";
//        echo "Response headers:\n" . json_encode($response->getHeaders(), JSON_PRETTY_PRINT) . "\n";

        //sleep(1); // Little delay between HTTP requests to prevent hammering

        return $response;
    }

}
