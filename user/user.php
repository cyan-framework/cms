<?php
namespace CMS\Library;
use Cyan\Library\ApplicationWeb;
use Cyan\Library\TraitContainer;
use Cyan\Library\TraitError;
use Cyan\Library\TraitEvent;
use Cyan\Library\TraitPrototype;
use Cyan\Library\FactoryPlugin;
use Cyan\Library\TraitSingleton;

/**
 * Class User
 * @package CMS\Library
 */
class User
{
    use TraitPrototype, TraitSingleton, TraitContainer, TraitEvent, TraitError;

    /**
     * Default to 1
     *
     * @var int
     */
    protected $lang_id = null;

    /**
     * Base identifier app
     *
     * @var string
     */
    private $baseIdentifier = 'app';

    /**
     * @param $baseIdentifier
     */
    public function __construct($baseIdentifier)
    {
        $this->Cyan = \Cyan::initialize();
        $this->baseIdentifier = $baseIdentifier;
    }

    /**
     * User Language ID
     *
     * @return mixed
     */
    public function getLanguageID()
    {
        if (is_null($this->lang_id)) {
            /** @var ApplicationWeb $App */
            $App = $this->getContainer('application');
            $Dbo = $App->Database->connect();
            $table = $this->Cyan->Finder->getIdentifier('components.com_users.config.table.profile');

            $userProfile = $Dbo->table($table['table_name'])->where('user_id', $this->getID())->fetch();
            if (empty($userProfile)) {
                $this->lang_id = 1;
            } else {
                $this->lang_id = $userProfile['lang_id'];
            }
        }

        return $this->lang_id;
    }

    /**
     * Authenticate User
     *
     * @param $credentials
     * @return bool
     */
    public function authenticate($credentials)
    {
        $App = $this->getContainer('application');

        //import model plugins
        $App->getContainer('factory_plugin')->assign('authentication', $this);

        if (empty($this->getEventPlugins())) {
            $this->setError($App->Text->translate('GLOBAL_ERROR_EMPTY_AUTHENTICATION_PLUGINS'));
            return false;
        }

        $method = 'onAuthenticate';
        foreach ($this->getEventPlugins() as $name => $plugin) {
            $hasMethod = (method_exists($plugin,$method) || (isset($plugin->$method) && is_callable($plugin->$method)));
            if (!$hasMethod) continue;
            $plugin->$method($this->getContainer('application'), $credentials);
            if ($this->isAuthenticated()) return true;
        }

        return false;
    }

    /**
     * Destroy user session
     */
    public function destroySession()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['login_string']);
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        if (isset($_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['login_string'])) {

            $table = $this->getContainer('table');

            $user_id = $_SESSION['user_id'];
            $login_string = $_SESSION['login_string'];
            $username = $_SESSION['username'];
            $user_browser = $_SERVER['HTTP_USER_AGENT'];

            $Dbo = $this->getContainer('application')->Database->connect();

            $userInfo = $Dbo->table($table['table_name'])->where($table['table_key'].' = '.$user_id)->fetch();

            if (empty($userInfo)) {
                return false;
            }

            $login_check = hash('sha512', $userInfo['password'] . $user_browser);

            if ($login_check == $login_string) {
                return true;
            }
        }

        return false;
    }

    /**
     * User ID
     *
     * @return int
     */
    public function getID()
    {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ;
    }

    /**
     * Return user data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($_SESSION['user_id'])) {
            $table = $this->getContainer('table');
            $Dbo = $this->getContainer('application')->Database->connect();

            $userInfo = $Dbo->table($table['table_name'],$_SESSION['user_id'])->fetch();

            return $userInfo->getData();
        } else {
            return [];
        }
    }

    /**
     * Return Profile Data
     *
     * @return array
     */
    public function getProfile()
    {
        if (isset($_SESSION['user_id'])) {
            $App = $this->getContainer('application');

            $table_config = $this->Cyan->Finder->getIdentifier('components:com_users.config.table.profile');

            $Dbo = $App->Database->connect();

            $profileInfo = $Dbo->table($table_config['table_name'])->where('user_id', $_SESSION['user_id'])->fetch();

            return $profileInfo;
        } else {
            return [];
        }
    }

    /**
     * Confirm current password is still valid
     *
     * @param $password
     * @return bool
     */
    public function sudo($password)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $App = $this->getContainer('application');

        //import model plugins
        $App->getContainer('factory_plugin')->assign('authentication', $this);

        if (empty($this->getEventPlugins())) {
            $this->setError($App->Text->translate('GLOBAL_ERROR_EMPTY_AUTHENTICATION_PLUGINS'));
            return false;
        }

        $method = 'onConfirmPassword';
        foreach ($this->getEventPlugins() as $name => $plugin) {
            $hasMethod = (method_exists($plugin,$method) || (isset($plugin->$method) && is_callable($plugin->$method)));
            if (!$hasMethod) continue;
            $plugin->$method($this->getContainer('application'), $password);
            if ($this->isAuthenticated()) return true;
        }

        return false;
    }

    /**
     * User username
     *
     * @return string
     */
    public function getUsername()
    {
        return isset($_SESSION['username']) ? $_SESSION['username'] : $this->getContainer('application')->Text->translate('USERNAME_GUEST') ;
    }

    /**
     * User Current Usergroup
     *
     * @return string
     */
    public function getUsergroup()
    {
        /** @var ApplicationWeb $App */
        $App = $this->getContainer('application');
        $Dbo = $App->Database->connect();

        $table = $this->getContainer('table');

        $userInfo = $Dbo->table($table['table_name'],$this->getID());

        return empty($userInfo) ? 1 : $userInfo->user_groups_id;
    }

    /**
     * Check if user can do a action base on plan
     *
     * @param $identifier
     */
    public function can($identifier, $default = false)
    {
        $parts = explode('.', $identifier);

        $acl = $this->getPlan();

        if (empty($acl)) {
            return $default;
        }

        return $this->_arraySearch([$acl], $parts, $default, 0);
    }

    /**
     * Plan Details
     *
     * @return array|\ArrayObject|mixed
     */
    public function getPlan()
    {
        /** @var ApplicationWeb $App */
        $App = $this->getContainer('application');
        $Dbo = $App->Database->connect();


    }


    /**
     * Check Brute Force verification
     *
     * @param $user_id
     * @return bool
     */
    public function checkBruteForce($user_id)
    {
        $App = $this->getContainer('application');

        // All login attempts are counted from the past 2 hours.
        $now = date("Y-m-d h:i:s");
        $two_hours_ago = date("Y-m-d h:i:s",strtotime('-2 hours'));
        $Dbo = $App->Database->connect();

        $sql = $Dbo->getDatabaseQuery()->from('login_attempt')->where('user_id = ?')->andWhere('time >= ?')->andWhere('time <= ?')->parameters([$user_id,$two_hours_ago,$now]);

        $sth = $Dbo->prepare($sql->getQuery());
        $sth->execute($sql->getParameters());
        $attempts = $sth->fetchAll(\PDO::FETCH_ASSOC);

        // If there have been more than 5 failed login
        if (!empty($attempts) && count($attempts) == 5) {
            return true;
        }

        return false;
    }
}