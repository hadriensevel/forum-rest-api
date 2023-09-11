<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: tequila.php
 * Based on Tequila PHP client v. 3.0.5
 */

/*========================================================================

	PHP client for Tequila, v. 3.0.5 (2022-03-09)
	(C) 2004, EPFL
	This code is released under the GNU GPL v2 terms (see LICENCE file).
	Original Author : Lionel Clavien

		3.0.0 : Big rewrite.
			Fix session time out
			use PHP sessions
			hide key attribute in urlaccess.

		3.0.1 : Fix INFO_PATH & QUERY_STRING test.

		3.0.2 : 2011-08-05 : Include comments from Lucien Chaboudez
			Define MIN_SESSION_TIMEOUT
			Delete cookie with explicit root path

		3.0.3 : 2012-04-12 : Patch from Lucien Chaboudez, EPFL
			LoadSession :Check if all the wanted attributes are present
			in the $_SESSION.

		3.0.4 : 2022-03-09 : Patch from Pierre Mellier, EPFL
			Add security mode auth_check for protocol 2.1

        3.0.5 : 2022-03-28 : Patch from Pierre Mellier, EPFL
            Forces the CURL command to check the authenticity
            of the accessed server

========================================================================*/

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception\ExpiredToken;
use Exception\InvalidToken;
use Exception\ExpiredSession;

// Start output buffering, for the authentication redirection to work...
ob_start();

// Constants declarations
const LNG_ENGLISH = 1;
const LNG_FRENCH = 0;

const COOKIE_NAME = 'teqkey';

class TequilaClientJWT
{
    private $jwtSecret = JWT_SECRET_KEY;

    private array $aLanguages = [
        LNG_ENGLISH => 'english',
        LNG_FRENCH => 'francais',
    ];

    private array $aWantedRights = [];
    private array $aWantedRoles = [];
    private array $aWantedAttributes = [];
    private array $aWishedAttributes = [];
    private array $aWantedGroups = [];
    private string $sCustomFilter = '';
    private string $sAllowsFilter = '';
    private int $iLanguage = LNG_FRENCH;
    private string $sApplicationURL = '';
    private string $sApplicationName = '';
    private string $sResource = '';
    private string $sKey = '';
    private array $aAttributes = [];
    private mixed $iTimeout;
    private mixed $sServer = '';
    private mixed $sServerUrl = '';
    private string $sCAFile = '';
    private string $sCertFile = '';
    private string $sKeyFile = '';

    private string $sCookieName = COOKIE_NAME;

    private array $requestInfos = [];

    /**
     * TequilaClient constructor.
     * @param string $sServer The Tequila server domain.
     * @param int|null $iTimeout The session timeout in seconds.
     * @throws Exception
     */
    public function __construct(string $sServer = '', ?int $iTimeout = null)
    {
        // If curl is not found, throws an exception.
        if (!extension_loaded('curl')) {
            throw new Exception('CURL Extension is not loaded.');
        }

        // Initializations. If no parameter given, get info from config file.
        if (empty($sServer)) {
            $sServer = $this->getConfigOption('sServer');
            $sServerUrl = $this->getConfigOption('sServerUrl');
        }

        if (empty($sServerUrl) && !empty($sServer)) {
            $sServerUrl = $sServer . '/cgi-bin/tequila';
        }
        if (empty($iTimeout)) {
            $iTimeout = $this->getConfigOption('iTimeout', 86400);
        }
        if (empty($logoutUrl)) {
            $logoutUrl = $this->getConfigOption('logoutUrl');
        }

        $this->sServer = $sServer;
        $this->sServerUrl = $sServerUrl;
        $this->iTimeout = $iTimeout;
    }

    /**
     * Get the value of a configuration option.
     * @param string $sOption
     * @param string $sDefault
     * @return string
     */
    private function getConfigOption(string $sOption, string $sDefault = ''): string
    {
        if (!array_key_exists($sOption, TEQUILA_CONFIG))
            return ($sDefault);
        else
            return (TEQUILA_CONFIG[$sOption]);
    }

    /**
     * Set the custom parameters.
     * @param array $customParameters An array containing the parameters. The
     *                                array key is the name of the parameter and the value is the value.
     */
    public function setCustomParameters(array $customParameters): void
    {
        foreach ($customParameters as $key => $val) {
            $this->requestInfos[$key] = $val;
        }
    }

    /**
     * Returns the custom parameters.
     * @return array
     */
    public function getCustomParameters(): array
    {
        return $this->requestInfos;
    }

    /**
     * Set the wanted rights.
     * @param array $aWantedRights An array with the rights.
     */
    public function setWantedRights(array $aWantedRights): void
    {
        $this->aWantedRights = $aWantedRights;
    }

    /**
     * Add a wanted right. The wanted right must be an array. It
     * will be merged with the array containing the wanted rights.
     * @param array $aWantedRights An array containing the wanted rights to add.
     */
    public function addWantedRights(array $aWantedRights): void
    {
        $this->aWantedRights = array_merge($this->aWantedRights, $aWantedRights);
    }

    /**
     * Remove some wanted rights.
     * @param array $aWantedRights An array with the wanted rights to remove.
     */
    public function removeWantedRights(array $aWantedRights): void
    {
        foreach ($this->aWantedRights as $sWantedRight) {
            if (in_array($sWantedRight, $aWantedRights)) {
                unset($this->aWantedRights[array_search($sWantedRight, $this->aWantedRights)]);
            }
        }
    }

    /**
     * Returns the wanted rights array.
     * @return array
     */
    public function getWantedRights(): array
    {
        return $this->aWantedRights;
    }

    /**
     * Set the wanted Roles.
     * @param array $aWantedRoles An array with the wanted roles.
     */
    public function setWantedRoles(array $aWantedRoles): void
    {
        $this->aWantedRoles = $aWantedRoles;
    }

    /**
     * Add some wanted roles to the current roles.
     * @param array $aWantedRoles An array with the roles to add.
     */
    public function addWantedRoles(array $aWantedRoles): void
    {
        $this->aWantedRoles = array_merge($this->aWantedRoles, $aWantedRoles);
    }

    /**
     * Remove some wanted roles from the list.
     * @param array $aWantedRoles An array with the roles to remove.
     */
    public function removeWantedRoles(array $aWantedRoles): void
    {
        foreach ($this->aWantedRoles as $sWantedRole) {
            if (in_array($sWantedRole, $aWantedRoles)) {
                unset($this->aWantedRoles[array_search($sWantedRole, $this->aWantedRoles)]);
            }
        }
    }

    /**
     * Returns the array containing the wanted roles.
     * @return array
     */
    public function getWantedRoles(): array
    {
        return $this->aWantedRoles;
    }

    /**
     * Set the wanted attributes.
     * @param array $aWantedAttributes An array containing the wanted attributes.
     */
    public function setWantedAttributes(array $aWantedAttributes): void
    {
        $this->aWantedAttributes = $aWantedAttributes;
    }

    /**
     * Add some wanted attributes to the list.
     * @param array $aWantedAttributes An array with the attributes to add.
     */
    public function addWantedAttributes(array $aWantedAttributes): void
    {
        $this->aWantedAttributes = array_merge($this->aWantedAttributes, $aWantedAttributes);
    }

    /**
     * Remove some wanted attributes from the list.
     * @param array $aWantedAttributes An array containing the attributes to remove.
     */
    public function removeWantedAttributes(array $aWantedAttributes): void
    {
        foreach ($this->aWantedAttributes as $sWantedAttribute) {
            if (in_array($sWantedAttribute, $aWantedAttributes)) {
                unset($this->aWantedAttributes[array_search($sWantedAttribute, $this->aWantedAttributes)]);
            }
        }
    }

    /**
     * Returns the array containing the wanted attributes.
     * @return array
     */
    public function getWantedAttributes(): array
    {
        return $this->aWantedAttributes;
    }

    /**
     * Set the wished attributes.
     * @param array $aWishedAttributes An array containing the wished attributes.
     */
    public function setWishedAttributes(array $aWishedAttributes): void
    {
        $this->aWishedAttributes = $aWishedAttributes;
    }

    /**
     * Add some wished attributes to the list.
     * @param array $aWishedAttributes An array containing the attributes to add.
     */
    public function addWishedAttributes(array $aWishedAttributes): void
    {
        $this->aWishedAttributes = array_merge($this->aWishedAttributes, $aWishedAttributes);
    }

    /**
     * Remove some wished attributes from the list.
     * @param array $aWishedAttributes An array with the attributes to remove.
     */
    public function removeWishedAttributes(array $aWishedAttributes): void
    {
        foreach ($this->aWishedAttributes as $aWishedAttribute) {
            if (in_array($aWishedAttribute, $aWishedAttributes)) {
                unset($this->aWishedAttributes[array_search($aWishedAttribute, $this->aWishedAttributes)]);
            }
        }
    }

    /**
     * Returns the array containing the wished attributes.
     * @return array
     */
    public function getWishedAttributes(): array
    {
        return $this->aWishedAttributes;
    }

    /**
     * Set the wanted groups.
     * @param array $aWantedGroups An array containing the groups.
     */
    public function setWantedGroups(array $aWantedGroups): void
    {
        $this->aWantedGroups = $aWantedGroups;
    }

    /**
     * Add some wanted groups to the list.
     * @param array $aWantedGroups An array containing the groups to add.
     */
    public function addWantedGroups(array $aWantedGroups): void
    {
        $this->aWantedGroups = array_merge($this->aWantedGroups, $aWantedGroups);
    }

    /**
     * Remove some wanted groups from the list.
     * @param array $aWantedGroups An array containing the groups to remove.
     */
    public function removeWantedGroups(array $aWantedGroups): void
    {
        foreach ($this->aWantedGroups as $aWantedGroup) {
            if (in_array($aWantedGroup, $aWantedGroups)) {
                unset($this->aWantedGroups[array_search($aWantedGroup, $this->aWantedGroups)]);
            }
        }
    }

    /**
     * Returns the array containing the wanted groups.
     * @return array
     */
    public function getWantedGroups(): array
    {
        return $this->aWantedGroups;
    }

    /**
     * Set the custom filter.
     * @param string $sCustomFilter A string containing the custom filter.
     */
    public function setCustomFilter(string $sCustomFilter): void
    {
        $this->sCustomFilter = $sCustomFilter;
    }

    /**
     * Returns the string containing the custom filter.
     * @return string
     */
    public function getCustomFilter(): string
    {
        return $this->sCustomFilter;
    }

    /**
     * Sets the allow filter.
     * @param string $sAllowsFilter A string containing the allow filter.
     */
    public function setAllowsFilter(string $sAllowsFilter): void
    {
        $this->sAllowsFilter = $sAllowsFilter;
    }

    /**
     * Returns the string containing the allows filter.
     * @return string
     */
    public function getAllowsFilter(): string
    {
        return $this->sAllowsFilter;
    }

    /**
     * Sets the current language.
     * @param string $sLanguage The language: 'english' | 'francais'.
     */
    public function setLanguage(string $sLanguage): void
    {
        $this->iLanguage = $sLanguage;
    }

    /**
     * Returns the current language.
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->iLanguage;
    }

    /**
     * Sets the application URL. This is the URL where to redirect
     * when the authentication has been done.
     * @param string $sApplicationURL The URL.
     */
    public function setApplicationURL(string $sApplicationURL): void
    {
        $this->sApplicationURL = $sApplicationURL;
    }

    /**
     * Returns the application URL.
     * @return string
     */
    public function getApplicationURL(): string
    {
        return $this->sApplicationURL;
    }

    /**
     * Set the application name. This will be displayed on the
     * Tequila login window.
     * @param string $sApplicationName The application name.
     */
    public function setApplicationName(string $sApplicationName): void
    {
        $this->sApplicationName = $sApplicationName;
    }

    /**
     * Returns the application name.
     * @return string
     */
    public function getApplicationName(): string
    {
        return $this->sApplicationName;
    }

    /**
     * Set the resource name.
     * @param string $sResource The resource name.
     */
    public function setResource(string $sResource): void
    {
        $this->sResource = $sResource;
    }

    /**
     * Returns the resource name.
     * @return string
     */
    public function getResource(): string
    {
        return $this->sResource;
    }

    /**
     * Set the session key.
     * @param string $sKey The session key.
     */
    public function setKey(string $sKey): void
    {
        $this->sKey = $sKey;
    }

    /**
     * Returns the session key.
     * @return string
     */
    public function getKey(): string
    {
        return $this->sKey;
    }

    /**
     * Set Tequila server name (i.e https://tequila.epfl.ch).
     * @param string $sServer The server name.
     */
    public function setServer(string $sServer): void
    {
        $this->sServer = $sServer;
    }

    /**
     * Returns Tequila server's name.
     * @return string
     */
    public function getServer(): string
    {
        return $this->sServer;
    }

    /**
     * Set Tequila server URL (i.e https://tequila.epfl.ch/cgi-bin/tequila).
     * @param string $sURL The server URL.
     */
    public function setServerURL(string $sURL): void
    {
        $this->sServerUrl = $sURL;
    }

    /**
     * Returns Tequila server's URL.
     * @return string
     */
    public function getServerURL(): string
    {
        return $this->sServerUrl;
    }

    /**
     * Set session manager timeout parameter.
     * @param int $iTimeout The timeout value.
     */
    public function setTimeout(int $iTimeout): void
    {
        $this->iTimeout = $iTimeout;
    }

    /**
     * Returns the session manager timeout parameter.
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->iTimeout;
    }

    /**
     * Set the cookie name parameters.
     * @param string $sCookieName The name of the cookie.
     */
    public function setCookieName(string $sCookieName): void
    {
        $this->sCookieName = $sCookieName;
    }

    /**
     * Create a PHP session with the Tequila attributes.
     * @param array $attributes An array containing the attributes returned by the tequila server.
     * @return void
     */
    private function createSession(array $attributes): void
    {
        if ($attributes) {
            foreach ($attributes as $key => $val) {
                $this->aAttributes[$key] = $val;
                $_SESSION[$key] = $val;
            }
            $_SESSION['creation'] = time();
        }
    }

    /**
     * Check the PHP session.
     * @return bool
     */
    public function isSessionValid(): bool
    {
        if (!isset($_SESSION['key'])) return false;

        /**
         * Check if all the wanted attributes are present in the $_SESSION.
         * If at least one of the attribute is missing, we can consider that information
         * is missing in $_SESSION. In this case, we return false to "force" to create a new
         * session with the wanted attributes. This can happen when several website are
         * running on the same web server and all are using the PHP Tequila Client.
         */
        foreach ($this->aWantedAttributes as $wantedAttribute) {
            if (!array_key_exists($wantedAttribute, $_SESSION)) return false;
        }
        foreach ($this->aWishedAttributes as $wishedAttribute) {
            if (!array_key_exists($wishedAttribute, $_SESSION)) return false;
        }

        return true;
    }

    /**
     * Verifies the given JWT token, checks it against session data, and returns the wanted attributes.
     * @param string $token The JWT token.
     * @return array|null The decoded token attributes if the token is valid and matches the session, otherwise null.
     * @throws InvalidToken|ExpiredToken|ExpiredSession
     */
    public function verifyTokenAndGetAttributes(string $token): ?array
    {
        try {
            // Decode the token
            $attributes = (array) JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            if (!isset($attributes['sessionId'], $attributes['key'])) {
                throw new InvalidToken('Token is not valid'); // Token doesn't have the required attributes
            }

            // Set the session ID to the one from the token and start the session
            session_id($attributes['sessionId']);
            if (!isset($_SESSION)) session_start();

            // Check if the session is valid
            if (!$this->checkSession()) {
                throw new ExpiredSession('The session is not valid');
            }

            // Check if the 'key' attribute from the token matches the session key
            if ($attributes['key'] !== $_SESSION['key']) {
                throw new ExpiredSession('The key of the session doesn\'t correspond to the one of the token');
            }

            // Return the wanted attributes from the session
            return $this->extractTokenAttributes();
        } catch (ExpiredException) {
            throw new ExpiredToken('Token is expired');
        }
    }

    private function checkSession(): bool
    {
        return $this->isSessionValid() && isset($_SESSION['creation']) && ($_SESSION['creation'] + $this->iTimeout >= time());
    }

    private function extractTokenAttributes(): array
    {
        $tokenAttributes = [];
        foreach ($this->aWantedAttributes as $wantedAttribute) {
            $tokenAttributes[$wantedAttribute] = $_SESSION[$wantedAttribute] ?? null; // Ensure all wanted attributes are set, even if not found in session
        }
        return $tokenAttributes;
    }

    /**
     * Returns an array containing user's attributes names as indexes
     * and attributes values as values.
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->aAttributes;
    }

    /**
     * Checks if the given attributes are present in the user's attributes.
     * @param array $attributes An associative array where keys are attribute names and values are set to true if the attribute is present.
     * @return void
     */
    public function hasAttributes(array &$attributes): void
    {
        foreach ($attributes as $attribute => $hasIt) {
            $attributes[$attribute] = array_key_exists($attribute, $this->aAttributes);
        }
    }

    /**
     * Launches the user authentication process.
     * @return string Returns the JWT token.
     */
    public function authenticate(): string
    {
        if (!isset($_SESSION)) session_start();

        $authCheck = $_GET['auth_check'] ?? '';

        if (!empty($_SESSION['key']) && !empty($authCheck)) {
            // Process auth_check parameter
            $attributes = $this->askTequila('fetchattributes', [
                'key' => $_SESSION['key'],
                'auth_check' => $authCheck
            ]);

            if ($attributes) {
                $this->createSession($attributes);

                // Create a new JWT token with the key and an expiration date
                $jwtData = [
                    'key' => $_SESSION['key'],
                    'sessionId' => session_id(),
                    'exp' => time() + $this->iTimeout
                ];
                return JWT::encode($jwtData, $this->jwtSecret, 'HS256');
            }
        }

        $this->createRequest();
        $url = $this->getAuthenticationUrl();
        header('Location: ' . $url);
        exit;
    }

    /**
     * Sends an authentication request to Tequila.
     * @return void
     */
    private function createRequest(): void
    {
        $urlAccess = $this->sApplicationURL;

        // If the application URL is not initialized, generate it automatically.
        if (empty($urlAccess)) {
            $urlAccess = $this->getCurrentUrl();
        }

        // Request creation
        $this->requestInfos['urlaccess'] = $urlAccess;
        $this->populateRequestInfos();

        ob_end_clean();

        // Asking Tequila
        $response = $this->askTequila('createrequest', $this->requestInfos);
        if (str_starts_with($response, 'key=')) $this->sKey = substr(trim($response), 4); // 4 = strlen ('key=')
        else $this->sKey = "";
        $_SESSION['key'] = $this->sKey;
    }

    /**
     * Populates the request information for the authentication request.
     * @return void
     */
    private function populateRequestInfos(): void
    {
        if (!empty($this->sApplicationName)) {
            $this->requestInfos['service'] = $this->sApplicationName;
        }
        $this->setRequestInfo('wantright', $this->aWantedRights);
        $this->setRequestInfo('wantrole', $this->aWantedRoles);
        $this->setRequestInfo('request', $this->aWantedAttributes);
        $this->setRequestInfo('wish', $this->aWishedAttributes);
        $this->setRequestInfo('belongs', $this->aWantedGroups);
        $this->setRequestInfo('require', array($this->sCustomFilter));
        $this->setRequestInfo('allows', array($this->sAllowsFilter));

        if (!empty($this->iLanguage)) {
            $this->requestInfos['language'] = $this->aLanguages[$this->iLanguage];
        }

        $this->requestInfos['dontappendkey'] = "1";
        $this->requestInfos['mode_auth_check'] = "1";
    }

    /**
     * Sets the request information with the given key and values.
     * @param string $key The key to set in the request information.
     * @param array|null $values The array of values to set for the given key.
     * @return void
     */
    private function setRequestInfo(string $key, ?array $values): void
    {
        if (!empty($values)) {
            $this->requestInfos[$key] = implode('+', $values);
        }
    }

    /**
     * Returns the current URL.
     * @return string
     */
    private function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['REQUEST_URI'];

        // Remove the token from the URL
        $path = preg_replace('/&token=[^&]*/', '', $path);

        return "{$protocol}://{$host}{$path}";
    }

    /**
     * Checks that the user has correctly authenticated and retrieves their data.
     * @param string $sessionKey The session key for the request.
     * @param string $authCheck The auth check for the request.
     * @return array|false Returns an array of fetched attributes or false if the response is empty.
     */
    private function fetchAttributes(string $sessionKey, string $authCheck): false|array
    {
        $fields = [
            'key' => $sessionKey,
            'auth_check' => $authCheck
        ];

        $response = $this->askTequila('fetchattributes', $fields);
        if (!$response) return false;
        $result = [];
        $attributes = explode("\n", $response);

        // Saving returned attributes
        foreach ($attributes as $attribute) {
            $attribute = trim($attribute);
            if (!$attribute) continue;
            $splitAttribute = explode('=', $attribute, 2);
            // Handle the case when the attribute is not properly split
            if (count($splitAttribute) !== 2) continue;
            [$key, $value] = $splitAttribute;
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Returns the value of $key.
     * @param string $key The key of the Tequila attribute to retrieve.
     * @return string|null
     */
    public function getValue(string $key = ''): ?string
    {
        if (isset ($_SESSION [$key])) return $_SESSION [$key];
        return NULL;
    }

    /**
     * Gets tequila server config infos
     * @return bool|string|null
     */
    public function getConfig(): bool|string|null
    {
        return $this->askTequila('config');
    }

    /**
     * Returns the Tequila authentication form URL.
     * @return string
     */
    private function getAuthenticationUrl(): string
    {
        return "{$this->sServerUrl}/requestauth?requestkey={$this->sKey}";
    }

    /**
     * Returns the logout URL
     * @param string $redirectUrl Optional url to redirect to when logout is done
     * @return string
     */
    private function getLogoutUrl(string $redirectUrl = ''): string
    {
        $url = "{$this->sServerUrl}/logout";
        if (!empty($redirectUrl)) {
            $url .= "?urlaccess=" . $redirectUrl;
        }
        return $url;
    }

    /**
     * Destroy the session file
     * @return void
     */
    private function killSessionFile(): void
    {
        if (!empty($_SESSION)) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Destroy session cookie
     * @return void
     */
    private function killSessionCookie(): void
    {
        // Delete cookie by setting expiration time in the past with root path
        setcookie($this->sCookieName, '', time() - 3600, '/');
    }

    /**
     * Terminate a session
     * @return void
     */
    private function killSession(): void
    {
        $this->killSessionFile();
        //$this->killSessionCookie();
    }

    /**
     * Logout from tequila and redirect to the logout url.
     * @param string $redirectUrl
     * @return void
     */
    public function logout(string $redirectUrl = ''): void
    {
        // Kill session cookie and session file
        $this->killSession();
        // Redirect the user to the tequila server logout url
        header("Location: " . $this->getLogoutUrl($redirectUrl));
        exit;
    }

    /**
     * Send a request to the Tequila service.
     * @param string $requestType The type of request to send to the Tequila service.
     * @param array $fields Optional fields to include in the request.
     * @return string|false Returns the response from the Tequila service or false if the request failed.
     */
    private function askTequila(string $requestType, array $fields = [])
    {
        // Initialize the cURL object to communicate with the Tequila service
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($this->sCAFile) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->sCAFile);
        }
        if ($this->sCertFile) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->sCertFile);
        }
        if ($this->sKeyFile) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->sKeyFile);
        }

        // Map request types to URLs
        $requestTypeToUrl = [
            'createrequest' => '/createrequest',
            'fetchattributes' => '/fetchattributes',
            'config' => '/getconfig',
            'logout' => '/logout'
        ];

        // Check if the request type is valid
        if (!isset($requestTypeToUrl[$requestType])) {
            return false;
        }

        // Construct the URL
        $url = $this->sServerUrl . $requestTypeToUrl[$requestType];
        curl_setopt($ch, CURLOPT_URL, $url);

        // If fields were passed as parameters
        if (!empty($fields)) {

            // Construct the query string
            $query = [];
            foreach ($fields as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $query[] = "$key=$value";
            }

            $query = implode("\n", $query) . "\n";

            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $response = curl_exec($ch);

        if ($requestType === 'fetchattributes' && $response) {
            return $this->parseAttributesFromResponse($response);
        }

        // If the connection failed (HTTP code 200 <=> OK)
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200') {
            $response = false;
        }

        curl_close($ch);
        return $response;
    }

    /**
     * Parse the attributes from the Tequila service response.
     * @param string $response The response from the Tequila service.
     * @return array The parsed attributes.
     */
    private function parseAttributesFromResponse(string $response): array
    {
        $result = [];
        $attributes = explode("\n", $response);

        // Saving returned attributes
        foreach ($attributes as $attribute) {
            $attribute = trim($attribute);
            if (!$attribute) continue;
            $splitAttribute = explode('=', $attribute, 2);
            // Handle the case when the attribute is not properly split
            if (count($splitAttribute) !== 2) continue;
            [$key, $value] = $splitAttribute;
            $result[$key] = $value;
        }

        return $result;
    }
}
