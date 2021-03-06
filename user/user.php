<?php
namespace Cyan\CMS;
use Cyan\Framework\ApplicationWeb;
use Cyan\Framework\Config;
use Cyan\Framework\Database;
use Cyan\Framework\DatabaseTable;
use Cyan\Framework\TraitContainer;
use Cyan\Framework\TraitError;
use Cyan\Framework\TraitEvent;
use Cyan\Framework\TraitPrototype;
use Cyan\Framework\TraitSingleton;

/**
 * Class User
 * @package Cyan\CMS
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
     * Default role id
     *
     * @var int
     */
    protected $default_role_id = 1;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->Cyan = \Cyan::initialize();
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
            $table = $this->getContainer('table_profile')->getConfig();

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

            $table = $this->getContainer('table_user')->getConfig();

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
     * Return user email and profile data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($_SESSION['user_id'])) {
            $user_table = $this->getContainer('table_user')->getConfig();
            $profile_table = $this->getContainer('table_profile')->getConfig();
            /** @var Database $Dbo */
            $Dbo = $this->getContainer('application')->Database->connect();

            $Schema = $Dbo->Schema();
            $Schema->setBackReference($user_table['table_name'],$profile_table['table_name'],'user_profile_id');
            $Schema->setReference($profile_table['table_name'],$user_table['table_name'],'id');

            $userInfo = $Dbo->table($user_table['table_name'])
                            ->where($user_table['table_name'].'.'.$user_table['table_key'].' = ?')
                            ->select('user.email')->selectRaw('SUBSTRING_INDEX(user.email,\'@\',1) as email_name')
                            ->parameters([$this->getID()])->fetch();

            return $userInfo;
        } else {
            return [];
        }
    }

    /**
     * Confirm current password is still valid
     *
     * @param $password
     * 
     * @return bool
     * 
     * 
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
     *
     * @since 1.0.0
     */
    public function getUsername()
    {
        return isset($_SESSION['username']) ? $_SESSION['username'] : $this->getContainer('application')->Text->translate('USERNAME_GUEST') ;
    }

    /**
     * User Roles
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getRoles()
    {
        /** @var ApplicationWeb $App */
        $App = $this->getContainer('application');
        $Dbo = $App->Database->connect();

        $table = $this->getContainer('table_role')->getConfig();

        /** @var DatabaseTable $userInfo */
        $user_roles = $Dbo->table('user_role')->select('role_id')->leftJoin('role','role.id = user_role.role_id')->where('state_id = 1')->where('user_id = ?')->parameters([$this->getID()])->fetchAll(\PDO::FETCH_COLUMN);
        return empty($user_roles) ? [$this->default_role_id] : $user_roles;
    }

    /**
     * @param $default_role_id
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function setDefaultRole($default_role_id)
    {
        $this->default_role_id = $default_role_id;

        return $this;
    }

    /**
     * Check if user can do a action base on plan
     *
     * @param $identifier
     * @param $default
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function can($identifier, $default = false)
    {
        $parts = explode('.', $identifier);

        $acl = $this->getPermissions();

        if (empty($acl)) {
            return $default;
        }

        return $acl->get($identifier, $default);
    }

    /**
     * Plan Details
     *
     * @return array|\ArrayObject|mixed
     */
    public function getPermissions()
    {
        /** @var ApplicationWeb $App */
        $App = $this->getContainer('application');
        $Dbo = $App->Database->connect();

        $Dbo->schema()->setBackReference('extension','rule','id');
        $Dbo->schema()->setBackReference('rule_action','rule','id');

        $in_groups = implode(',',$this->getRoles());
        $sql = $Dbo->getDatabaseQuery()->from('rule')
            ->select('access')
            ->select('extension.name AS extension_name')
            ->select('rule_action.name AS action_name')
            ->where('role_id IN ('.$in_groups.')')
            ->where('application_instance_id = '.$App->getID());

        $sth = $Dbo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $acl = [];
        foreach ($rows as $row) {
            $acl[$row['extension_name']][$row['action_name']] = $row['access'];
        }

        $configAcl = new Config();
        $configAcl->loadArray($acl);
        return $configAcl;
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

        $sql = $Dbo->getDatabaseQuery()->from('user_login_attempt')->where('user_id = ?')->andWhere('time >= ?')->andWhere('time <= ?')->parameters([$user_id,$two_hours_ago,$now]);

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