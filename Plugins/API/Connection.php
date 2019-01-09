<?php
class APIConnect extends Controller
{
    // Documentation : https://docs.trixcms.eu/

    protected $guzzle;

    /**
     * APIConnect constructor.
     */
    public function __construct()
    {
        return $this->guzzle = (new Client(['base_uri' => 'urlOfAPI', 'timeout' => 5, 'verify' => base_path() . '/cacert.pem']));
    }

    /**
     * @return string
     * @throws APIConnectFail
     */
    public function Login()
    {
        try {
            $request = $this->guzzle->request('GET', "?key=" . env('APP_KCFT'));
            $content = $request->getBody();
            return $content->getContents();
        } catch (GuzzleException $e) {
            Log::info('APIConnect has failed.');
            throw new APIConnectFail('APIConnect has failed.');
        }
    }

    /**
     * @param $item
     * @return mixed
     * @throws APIConnectFail
     */
    public function getItem($item)
    {
        if (!empty(json_decode($this->Login(), true)[$item])) return json_decode($this->Login(), true)[$item];
    }
}
