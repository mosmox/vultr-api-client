<?php
/**
 * Vultr.com API Client
 * @package vultr
 * @version 1.0
 * @author  https://github.com/mosmox
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Vultr;

use Exception;


/**
 * @property string request_type
 */
class Api {
    /**
     * API Token
     * @access private
     * @type string $api_token Vultr.com API token
     * @see https://my.vultr.com/settings/
     */
    private $api_token = '';
    /**
     * API Endpoint
     * @access public
     * @type string URL for Vultr.com API
     */
    public $endpoint = 'https://api.vultr.com/v1/';
    /**
     * Current Version
     * @access public
     * @type string Current version number
     */
    public $version = '1.0';

    /**
     * @var
     */
    public $apiSession;

    /**
     * User Agent
     * @access public
     * @type string API User-Agent string
     */
    public $agent = 'Vultr.com API Client';
    /**
     * Debug Variable
     * @access public
     * @type bool Debug API requests
     */
    public $debug = FALSE;
    /**
     * Snapshots Variable
     * @access public
     * @type mixed Array to store snapshot IDs
     */
    public $snapshots = array();
    /**
     * Plans Variable
     * @access public
     * @type mixed Array to store VPS Plan IDs
     */
    public $plans     = array();
    /**
     * Regions Variable
     * @access public
     * @type mixed Array to store available regions
     */
    public $regions   = array();
    /**
     * Scripts Variable
     * @access public
     * @type mixed Array to store startup scripts
     */
    public $scripts   = array();
    /**
     * Servers Variable
     * @access public
     * @type mixed Array to store server data
     */
    public $servers   = array();
    /**
     * Account Variable
     * @access public
     * @type mixed Array to store account data
     */
    public $account   = array();

    /**
     * OS List Variable
     * @access public
     * @type mixed Array to store OS list
     */
    public $oses   = array();
    /**
     * SSH Keys variable
     * @access public
     * @type mixed Array to store SSH keys
     **/
    public $ssh_keys = array();
    /**
     * Response code variable
     * @access public
     * @type int Holds HTTP response code from API
     **/
    public $response_code = 0;
    /**
     * Response code variable
     * @access public
     * @type bool Determines whether to include the response code, default: false
     **/
    public $get_code = false;
    /**
     * Cache folder
     * @access public
     * $type string Cache dir
     */
    public $cache_dir = '/tmp/vultr-api-client-cache';

    /**
     * Constructor function
     *
     * @param string $token
     *
     * @throws Exception
     */
    public function __construct($token = '') {
        if($token && $token != '') {
            $this->api_token = $token;
            return true;
        } else {
            throw new Exception('Invalid token.');
            return false;
        }
    }

    /**
     * Проверка подключения к сервису
     * @return bool
     */
    public function isConnect(){
        $auth = $this->AuthInfo();
        if(is_array($auth) == FALSE && is_string($auth) == true && $auth == 'Invalid API key'){
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get Account info
     * @see https://www.vultr.com/api/#account_info
     * @return mixed
     */
    public function AccountInfo(){
        return $this->request('account/info');
    }

    /**
     * Get Auth info
     * @see https://www.vultr.com/api/#auth_info
     * @return mixed
     */
    public function AuthInfo(){
        return $this->request('auth/info');
    }

    /**
     * Get OS list
     * @see https://www.vultr.com/api/#os_os_list
     *
     * @param string $family
     * @param int $arch
     *
     * @return mixed
     */
    public function OsList($family = '', $arch = 0){
        $r = $this->request('os/list');
        if($arch && ($arch == 32 || $arch = 64)){
            foreach($r as $k => $i){
                if(isset($i['arch']) == true && $i['arch'] != str_replace(['32', '64'], ['i386', 'x64'], $arch)){
                    unset($r[$k]);
                }
            }
        }
        if($family && $family != ''){
            foreach($r as $k => $i){
                if(isset($i['family']) == true && $i['family'] != $family){
                    unset($r[$k]);
                }
            }
        }
        return $r;
    }

    /**
     * List available snapshots
     * @see https://www.vultr.com/api/#snapshot_snapshot_list
     * @return mixed
     */
    public function SnapshotList(){
        return $this->request('snapshot/list');
    }

    /**
     * Create snapshot
     * @see https://www.vultr.com/api/#snapshot_create
     *
     * @param int $server_id
     *
     * @return int|mixed|string
     */
    public function SnapshotCreate($server_id){
        $args = array('SUBID' => $server_id);
        return $this->post('snapshot/create', $args);
    }

    /**
     * Destroy snapshot
     * @see https://www.vultr.com/api/#snapshot_destroy
     * @param int $snapshot_id
     * @return int HTTP response code
     */
    public function SnapshotDestroy($snapshot_id){
        $args = array('SNAPSHOTID' => $snapshot_id);
        return $this->code('snapshot/destroy', $args);
    }

    /**
     * List available ISO iamges
     * @see https://www.vultr.com/api/#iso_list
     * @return mixed Available ISO images
     **/
    public function IsoList(){
        return $this->request('iso/list');
    }

    /**
     * List available plans
     * @see https://www.vultr.com/api/#plans_plan_list
     * @return mixed
     */
    public function PlansList(){
        return $this->request('plans/list');
    }

    /**
     * List available plans
     * @see https://www.vultr.com/api/#plans_plan_list_vc2
     * @return mixed
     */
    public function PlansListVC2(){
        return $this->request('plans/list_vc2');
    }

    /**
     * List available plans
     * @see https://www.vultr.com/api/#plans_plan_list_vdc2
     * @return mixed
     */
    public function PlansListVDC2(){
        return $this->request('plans/list_vdc2');
    }

    /**
     * List available regions
     * @see https://www.vultr.com/api/#regions_region_list
     * @return mixed
     */
    public function RegionsList(){
        return $this->request('regions/list');
    }

    /**
     * Determine region availability
     * @see https://www.vultr.com/api/#regions_region_available
     * @param int $datacenter_id
     * @return mixed VPS plans available at given region
     */
    public function RegionsAvailability($datacenter_id = 0){
        return $this->request('regions/availability?DCID=' . (int)$datacenter_id);
    }

    /**
     * Determine region availability
     * @see https://www.vultr.com/api/#regions_region_available_vc2
     * @param int $datacenter_id
     * @return mixed VPS plans available at given region
     */
    public function RegionsAvailabilityVC2($datacenter_id = 0){
        return $this->request('regions/availability_vc2?DCID=' . (int)$datacenter_id);
    }

    /**
     * Determine region availability
     * @see https://www.vultr.com/api/#regions_region_available_vdc2
     * @param int $datacenter_id
     * @return mixed VPS plans available at given region
     */
    public function RegionsAvailabilityVDC2($datacenter_id = 0){
        return $this->request('regions/availability_vdc2?DCID=' . (int)$datacenter_id);
    }

    /**
     * List startup scripts
     * @see https://www.vultr.com/api/#startupscript_startupscript_list
     * @return mixed List of startup scripts
     */
    public function StartUpScriptList(){
        return $this->request('startupscript/list');
    }

    /**
     * Update startup script
     * @param int $script_id
     * @param string $name
     * @param string $script script contents
     * @return int HTTP response code
     **/
    public function StartUpScriptUpdate($script_id, $name, $script){
        return $this->code('startupscript/update', [
            'SCRIPTID' => $script_id,
            'name' => $name,
            'script' => $script
        ]);
    }

    /**
     * Destroy startup script
     * @see https://www.vultr.com/api/#startupscript_destroy
     * @param int $script_id
     * @return int HTTP respnose code
     */
    public function StartupScriptDestroy($script_id){
        return $this->code('startupscript/destroy', [
            'SCRIPTID' => $script_id
        ]);
    }

    /**
     * Create startup script
     * @see https://www.vultr.com/api/#startupscript_create
     * @param string $script_name
     * @param string $script_contents
     * @return int Script ID
     */
    public function StartUpScriptCreate($script_name, $script_contents){
        $script = $this->post('startupscript/create', [
            'name' => $script_name,
            'script' => $script_contents
        ]);
        return isset($script['SCRIPTID']) == true ? (int)$script['SCRIPTID'] : -1;
    }

    /**
     * Determine server availability
     * @param int $region_id Datacenter ID
     * @param int $plan_id VPS Plan ID
     * @return bool Server availability
     * @throws Exception if VPS Plan ID is not available in specified region
     */
    public function ServerAvailable($region_id, $plan_id){
        $availability = $this->regions_availability((int)$region_id);
        if (!in_array((int) $plan_id, $availability)) {
            throw new Exception('Plan ID ' . $plan_id . ' is not available in region ' . $region_id);
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * List servers
     * @see https://www.vultr.com/api/#server_server_list
     * @return mixed List of servers
     */
    public function ServerList(){
        return $this->request('server/list');
    }

    /**
     * Display server bandwidth
     * @see https://www.vultr.com/api/#server_bandwidth
     * @param int $server_id
     * @return mixed Bandwidth history
     */
    public function ServerBandwidth($server_id){
        $args = array('SUBID' => (int) $server_id);
        return $this->request('server/bandwidth', $args);
    }

    /**
     * List IPv4 Addresses allocated to specified server
     * @see https://www.vultr.com/api/#server_list_ipv4
     * @param int $server_id
     * @return mixed IPv4 address list
     */
    public function ServerIPv4List($server_id){
        $ipv4 = $this->request('server/list_ipv4', [
            'SUBID' => (int) $server_id
        ]);
        return $ipv4[(int) $server_id];
    }

    /**
     * Create IPv4 address
     * @see https://www.vultr.com/api/#server_create_ipv4
     * @param int $server_id
     * @param string reboot server after adding IP: <yes|no>, default: yes
     * @return int HTTP response code
     **/
    public function ServerIPv4Create($server_id, $reboot = 'yes'){
        return $this->code('server/create_ipv4', [
            'SUBID' => $server_id,
            'reboot' => ($reboot == 'yes' ? 'yes' : 'no')
        ]);
    }

    /**
     * Destroy IPv4 Address
     * @see https://www.vultr.com/api/#server_destroy_ipv4
     * @param int $server_ID
     * @param string $ip IPv4 address
     * @return int HTTP response code
     **/
    public function ServerIPv4Destroy($server_id, $ip4){
        return $this->code('server/destroy_ipv4', [
            'SUBID' => $server_id,
            'ip' => $ip4
        ]);
    }

    /**
     * Set Reverse DNS for IPv4 address
     * @see https://www.vultr.com/api/#server_reverse_set_ipv4
     * @param string $ip
     * @param string $rdns
     * @return int HTTP response code
     */
    public function ServerReverseIPv4Set($ip, $rdns){
        return $this->code('server/reverse_set_ipv4', [
            'ip' => $ip,
            'entry' => $rdns
        ]);
    }

    /**
     * Set Default Reverse DNS for IPv4 address
     * @see https://www.vultr.com/api/#server_reverse_default_ipv4
     * @param string $server_id
     * @param string $ip
     * @return int HTTP response code
     */
    public function ServerReverseIPv4Default($server_id, $ip){
        return $this->code('server/reverse_default_ipv4', [
            'SUBID' => (int) $server_id,
            'ip' => $ip
        ]);
    }

    /**
     * List IPv6 addresses for specified server
     * @see https://www.vultr.com/api/#server_list_ipv6
     * @param int $server_id
     * @return mixed IPv6 allocation info
     */
    public function ServerIPv6List($server_id){
        $ipv6 = $this->request('server/list_ipv6', [
            'SUBID' => (int) $server_id
        ]);
        return isset($ipv6[(int) $server_id]) == true ? $ipv6[(int) $server_id] : -1;
    }

    /**
     * Set Reverse DNS for IPv6 address
     * @see https://www.vultr.com/api/#server_reverse_set_ipv6
     * @param int $server_id
     * @param string $ip
     * @param string $rdns
     * @return int HTTP response code
     */
    public function ServerReverseIPv6Set($server_id, $ip, $rdns){
        return $this->code('server/reverse_set_ipv6', [
            'SUBID' => (int) $server_id,
            'ip' => $ip,
            'entry' => $rdns
        ]);
    }

    /**
     * Delete IPv6 Reverse DNS
     * @see https://www.vultr.com/api/#server_reverse_delete_ipv6
     * @param int $server_id
     * @param string $ip6 IPv6 address
     * @return int HTTP response code
     **/
    public function ServerReverseIPv6Delete($server_id, $ip6){
        return $this->code('server/reverse_delete_ipv6', [
            'SUBID' => $server_id,
            'ip' => $ip6
        ]);
    }

    /**
     * Reboot server
     * @see https://www.vultr.com/api/#server_reboot
     * @param int $server_id
     * @return int HTTP response code
     */
    public function ServerReboot($server_id){
        return $this->code('server/reboot', [
            'SUBID' => $server_id
        ]);
    }

    /**
     * Halt server
     * @see https://www.vultr.com/api/#server_halt
     * @param int $server_id
     * @return int HTTP response code
     */
    public function ServerHalt($server_id){
        return $this->code('server/halt', [
            'SUBID' => (int) $server_id
        ]);
    }

    /**
     * Start server
     * @see https://www.vultr.com/api/#server_start
     * @param int $server_id
     * @return int HTTP response code
     */
    public function ServerStart($server_id){
        return $this->code('server/start', [
            'SUBID' => (int) $server_id
        ]);
    }

    /**
     * Destroy server
     * @see https://www.vultr.com/api/#server_destroy
     * @param int $server_id
     * @return int HTTP response code
     */
    public function ServerDestroy($server_id){
        return $this->code('server/destroy', [
            'SUBID' => (int) $server_id
        ]);
    }

    /**
     * Reinstall OS on an instance
     * @see https://www.vultr.com/api/#server_reinstall
     * @param int $server_id
     * @return int HTTP response code
     */
    public function ServerReInstall($server_id){
        return $this->code('server/reinstall', [
            'SUBID' => (int) $server_id
        ]);
    }

    /**
     * Set server label
     * @see https://www.vultr.com/api/#server_label_set
     * @param int $server_id
     * @param string $label
     * @return int HTTP response code
     */
    public function ServerLabelSet($server_id, $label){
        return $this->code('server/label_set', [
            'SUBID' => (int) $server_id,
            'label' => $label
        ]);
    }

    /**
     * Restore Server Snapshot
     * @see https://www.vultr.com/api/#server_restore_snapshot
     * @param int $server_id
     * @param string $snapshot_id Hexadecimal string with Restore ID
     * @return int HTTP response code
     */
    public function ServerRestoreSnapshot($server_id, $snapshot_id){
        return $this->code('server/restore_snapshot', [
            'SUBID' => (int) $server_id,
            'SNAPSHOTID' => preg_replace('/[^a-f0-9]/', '', $snapshot_id)
        ]);
    }

    /**
     * Restore Backup
     * @param int $server_id
     * @param string $backup_id
     * @return int HTTP response code
     **/
    public function ServerRestoreBackup($server_id, $backup_id){
        return $this->code('server/restore_backup', [
            'SUBID' => $server_id,
            'BACKUPID' => $backup_id
        ]);
    }

    /**
     * Create Server
     * @see https://www.vultr.com/api/#server_create
     *
     * @param $config
     *
     * @return int
     * @internal param int $region_id
     * @internal param int $plan_id
     * @internal param int $os_id
     *
     */
    public function ServerCreate($config){
        if(array_key_exists('DCID', $config) == true && array_key_exists('VPSPLANID', $config) == true && array_key_exists('OSID', $config) == true){
            try {
                $this->server_available((int)$config['DCID'], (int)$config['VPSPLANID']);
            } catch (Exception $e) {
                return FALSE;
            }
            $create = $this->post('server/create', $config);
            return isset($create['SUBID']) == true ? (int)$create['SUBID'] : -1;
        } else {
            return -1;
        }

    }

    /**
     * SSH Keys List method
     * @see https://www.vultr.com/api/#sshkey_sshkey_list
     * @return FALSE if no SSH keys are available
     * @return mixed with whatever ssh keys get returned
     */
    public function SshKeyList(){
        $try = $this->request('sshkey/list');
        if (sizeof($try) < 1) {
            return FALSE;
        } else {
            return $try;
        }
    }

    /**
     * SSH Keys Create method
     * @see https://www.vultr.com/api/#sshkey_sshkey_create
     * @param string $name
     * @param string $key [openssh formatted public key]
     * @return FALSE if no SSH keys are available
     * @return mixed with whatever ssh keys get returned
     */
    public function SshKeyCreate($name, $key){
        return $this->post('sshkey/create', [
            'name' => $name,
            'ssh_key'  => $key
        ]);
    }

    /**
     * SSH Keys Update method
     * @see https://www.vultr.com/api/#sshkey_sshkey_update
     * @param string $key_id
     * @param string $name
     * @param string $key [openssh formatted public key]
     * @return int HTTP response code
     */
    public function SshKeyUpdate($key_id, $name, $key){
        return $this->code('sshkey/update', [
            'SSHKEYID' => $key_id,
            'name'     => $name,
            'ssh_key'  => $key
        ]);
    }

    /**
     * SSH Keys Destroy method
     * @see https://www.vultr.com/api/#sshkey_sshkey_destroy
     * @param string $key_id
     * @return int HTTP response code
     */
    public function SshKeyDestroy($key_id){
        return $this->code('sshkey/update', [
            'SSHKEYID' => $key_id
        ]);
    }

    /**
     * GET Method
     *
     * @param string $method
     * @param mixed $args
     *
     * @return int|mixed|string
     */
    public function request($method, $args = FALSE){
        $this->request_type = 'GET';
        $this->get_code = false;
        return $this->query($method, $args);
    }

    /**
     * CODE Method
     * @param string $method
     * @param mixed $args
     * @return mixed if no exceptions thrown
     **/
    public function code($method, $args = FALSE){
        $this->request_type = 'POST';
        $this->get_code = true;
        return $this->query($method, $args);
    }

    /**
     * POST Method
     *
     * @param string $method
     * @param mixed $args
     *
     * @return int|mixed|string
     */
    public function post($method, $args){
        $this->request_type = 'POST';
        return $this->query($method, $args);
    }

    /**
     * API Query Function
     *
     * @param string $method
     * @param mixed $args
     *
     * @return int|mixed|string
     */
    private function query($method, $args){
        $url = $this->endpoint . $method . '?api_key=' . $this->api_token;
        if ($this->debug) echo $this->request_type . ' ' . $url . PHP_EOL;

        $this->apiSession = curl_init();
        curl_setopt($this->apiSession, CURLOPT_USERAGENT, sprintf('%s v%s', $this->agent, $this->version));
        curl_setopt($this->apiSession, CURLOPT_HEADER, 0);
        curl_setopt($this->apiSession, CURLOPT_VERBOSE, 0);
        curl_setopt($this->apiSession, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->apiSession, CURLOPT_SSL_VERIFYHOST, '1.0');
        curl_setopt($this->apiSession, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->apiSession, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($this->apiSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->apiSession, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($this->apiSession, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->apiSession, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        switch($this->request_type) {
            case 'POST':
                curl_setopt($this->apiSession, CURLOPT_URL, $url);
                curl_setopt($this->apiSession, CURLOPT_POST, 1);
                curl_setopt($this->apiSession, CURLOPT_POSTFIELDS, http_build_query($args));
                break;
            case 'GET':
                if ($args !== FALSE) {
                    curl_setopt($this->apiSession, CURLOPT_URL, $url . '&' . http_build_query($args));
                } else {
                    curl_setopt($this->apiSession, CURLOPT_URL, $url);
                }
                break;
            default:break;
        }

        curl_setopt($this->apiSession, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($this->apiSession);

        if($response == 'Invalid API key') {
            return 'Invalid API key';
        }

        /**
         * Check to see if there were any API exceptions thrown
         * If so, then error out, otherwise, keep going.
         */
        try {
            $this->isAPIError($this->apiSession, $response);
        } catch(Exception $e) {
            curl_close($this->apiSession);
            return $e->getMessage() . PHP_EOL;
        }

        /**
         * Close our session
         * Return the decoded JSON response
         */
        curl_close($this->apiSession);
        $obj = json_decode($response, true);
        if ($this->get_code) {
            return (int) $this->response_code;
        }
        return $obj;
    }

    /**
     * API Error Handling
     * @param cURL_Handle $response_obj
     * @param string $response
     * @throws Exception if invalid API location is provided
     * @throws Exception if API token is missing from request
     * @throws Exception if API method does not exist
     * @throws Exception if Internal Server Error occurs
     * @throws Exception if the request fails otherwise
     */
    public function isAPIError($response_obj, $response) {
        $code = curl_getinfo($response_obj, CURLINFO_HTTP_CODE);
        if ($this->get_code) {
            $this->response_code = $code;
            return;
        }
        if($this->debug){
            echo $code . PHP_EOL;
            switch($code) {
                case 200: break;
                case 400: throw new Exception('Invalid API location. Check the URL that you are using'); break;
                case 403: throw new Exception('Invalid or missing API key. Check that your API key is present and matches your assigned key'); break;
                case 405: throw new Exception('Invalid HTTP method. Check that the method (POST|GET) matches what the documentation indicates'); break;
                case 500: throw new Exception('Internal server error. Try again at a later time'); break;
                case 412: throw new Exception('Request failed: ' . $response); break;
                case 503: throw new Exception('Rate limit hit. API requests are limited to an average of 2/s. Try your request again later.'); break;
                default:  break;
            }
        }
    }
}