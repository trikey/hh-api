<?

Class HeadHunterApi
{
    const EMPLOYER_ID = ""; // employer ID
    const CLIENT_ID = ""; // client ID
    const CLIENT_SECRET = ""; // client secret
    const APP_EMAIL = ""; //  email of developer
    const APP_NAME = ""; // name of app at headhunter

    const API_BASE_URI = "https://api.hh.ru/";


    private $authAddress;
    private $authAccessToken;
    private $authRefreshToken;
    private $authTokensFilePath;

    private function setAuthAddress($value) {
        $this->authAddress = $value;
    }
    private function getAuthAddress() {
        return $this->authAddress;
    }

    private function setAuthAccessToken($value) {
        $this->authAccessToken = $value;
    }
    private function getAuthAccessToken() {
        return $this->authAccessToken;
    }

    private function setAuthRefreshToken($value) {
        $this->authRefreshToken = $value;
    }
    private function getAuthRefreshToken() {
        return $this->authRefreshToken;
    }

    private function setAuthTokensFilePath($value) {
        $this->authTokensFilePath = $value;
    }
    private function getAuthTokensFilePath() {
        return $this->authTokensFilePath;
    }

    private function getTokensFromFile($appTokensFilePath) {
        $tokens = file_get_contents($appTokensFilePath);
        $tokens = explode(";", $tokens);// 0 element - refresh token, 1 - access token
        return $tokens;
    }

    function __construct($appTokensFilePath) {
        $this->setAuthTokensFilePath($appTokensFilePath);
        $tokens = $this->getTokensFromFile($appTokensFilePath);
        $this->setAuthAccessToken($tokens[1]);
        $this->setAuthRefreshToken($tokens[0]);
        $this->updateTokens();
    }

    private function updateTokens() {
        $this->setAuthAddress("https://hh.ru/oauth/token?grant_type=refresh_token&refresh_token=".$this->getAuthRefreshToken());
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->getAuthAddress(),
            CURLOPT_USERAGENT => self::APP_NAME . " (" . self::APP_EMAIL . ")",
            CURLOPT_HTTPHEADER => array(
                "Content-Length:0",
                "Content-Type:application/x-www-form-urlencoded",
            ),
            CURLOPT_POST => 1,
        ));
        $response = curl_exec($curl);
        $res = json_decode($response);
        if ($res->access_token && $res->refresh_token) {
            $this->setAuthAccessToken($res->access_token);
            $this->setAuthRefreshToken($res->refresh_token);
            file_put_contents($this->getAuthTokensFilePath(), $res->refresh_token.";".$res->access_token);
        }
        elseif($res->error)
        {
            /* TODO Handle errors */
        }
    }

    public function getManagersListByEmployerId($employerId) {
        $url = self::API_BASE_URI."employers/{$employerId}/managers";
        return $this->sendRequest($url);
    }

    public function getVacanciesPlacedByManagerId($employerId, $managerId)
    {
        $url = self::API_BASE_URI."employers/{$employerId}/vacancies/active?manager_id={$managerId}";
        return $this->sendRequest($url);
    }

    public function getVacancyById($vacancyId)
    {
        $url = self::API_BASE_URI."vacancies/{$vacancyId}";
        return $this->sendRequest($url);
    }

    private function sendRequest($url, $method = "GET", $fields = false, $headers = false) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => self::APP_NAME . " (" . self::APP_EMAIL . ")",
            CURLOPT_HTTPHEADER => array("Authorization: Bearer ".$this->getAuthAccessToken())
        ));
        $result = curl_exec($curl);
        $res = json_decode($result);
        return $res;
    }

}


$redmondEmployerId = "";
$appTokensFilePath = "hh_tokens.csv";
$hh = new HeadHunterApi($appTokensFilePath);
$managers = $hh->getManagersListByEmployerId($redmondEmployerId); // parameter - employer id
foreach($managers->items as $managerKey => $managerItem)
{
    $vacancies = $hh->getVacanciesPlacedByManagerId($redmondEmployerId, $managerItem->id);
    if (intval($vacancies->found) > 0) {
        foreach($vacancies->items as $vacancyKey => $vacancyItem)
        {
            $vacancy = $hh->getVacancyById($vacancyItem->id);
        }
    }
}



